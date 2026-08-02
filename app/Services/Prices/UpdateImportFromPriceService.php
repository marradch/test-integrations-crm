<?php
namespace App\Services\Prices;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Exception;

class UpdateImportFromPriceService
{
    protected string $priceListPath;
    protected string $importFilePath;
    protected array $priceMap = [];

    public function __construct() {
        $this->priceListPath = resource_path('excel/Price.xlsx');
        $this->importFilePath = resource_path('excel/Import.xlsx');
    }

    /**
     * Основний метод оновлення файлу імпорту на основі прайс-листа.
     *
     * @throws Exception
     */
    public function update(): void 
    {
        if (!file_exists($this->priceListPath)) {
            throw new Exception("Файл прайс-листа не найден: {$this->priceListPath}");
        }
        if (!file_exists($this->importFilePath)) {
            throw new Exception("Файл импорта не найден: {$this->importFilePath}");
        }

        echo "Зчитуємо прайс-лист і будуємо карту артикулів та цін...\n";

        echo "Зчитуємо Enerpia Cable прайс-лист...\n";
        $this->parseEnerpiaPriceList();

        if (empty($this->priceMap)) {
            throw new Exception("Карта цін пуста. Перевірте файл прайс-листа на наявність даних.");
        }

        echo "Побудова індексу імпорту...\n";

        $importSpreadsheet = IOFactory::load($this->importFilePath);
        echo "Файл імпорту зчитано успішно.\n";
        $sheet = $importSpreadsheet->getActiveSheet();
        $importRowMap = $this->buildImportIndex($sheet);

        $currentDate = date('Y-m-d H:i:s');
        $lightBlueColor = 'FFE0F2FE';

        echo "Оновлюємо ціни у файлі імпорту...\n";
        foreach ($this->priceMap as $searchKey => $basePrice) {
            // Перевіряємо, чи існує артикул у файлі імпорту
            if (isset($importRowMap[$searchKey])) {
                $row = $importRowMap[$searchKey]; // Беремо номер рядка з індексу
                
                $newPrice = round($basePrice * 1.10, 2);

                // Записуємо нову ціну в колонку H
                $sheet->setCellValue("H{$row}", $newPrice);

                // Фарбуємо клітинку в колонці H у світло-блакитний колір
                $sheet->getStyle("H{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB($lightBlueColor);

                // Записуємо поточну дату в колонку CS
                $sheet->setCellValue("CS{$row}", $currentDate);
            }
        }

        // Зберігаємо оновлений файл імпорту
        $savePath = $this->importFilePath;
        $writer = IOFactory::createWriter($importSpreadsheet, pathinfo($savePath, PATHINFO_EXTENSION) === 'xls' ? 'Xls' : 'Xlsx');
        $writer->save($savePath);

        echo "Файл імпорту успішно оновлено та збережено: {$savePath}\n";
    }

    private function parseEnerpiaPriceList(): array
    {
        $spreadsheet = IOFactory::load($this->priceListPath);
        $sheet = $spreadsheet->getSheetByName('Enerpia Cable');

        if ($sheet === null) {
            throw new \Exception("Лист з назвою 'Enerpia Cable' не знайдено у файлі прайс-листа.");
        }
        
        $highestRow = $sheet->getHighestRow();

        for ($row = 1; $row <= $highestRow; $row++) {
            $skuCell = $sheet->getCell("A{$row}")->getValue();

            if (empty($skuCell)) {
                continue;
            }

            $skuString = trim((string)$skuCell);

            // 1. ^DW-\d+[\s\-]*UT$                      => DW-10 UT, DW-20-UT
            // 2. ^DW-\d+W[\s\-]*\d+(?:\.\d+)?[\s\-]*m2?$ => DW-20W 1m2, DW-20W 1.5m2, DW-20W-1.5m2
            $isUtPattern = preg_match('/^DW-\d+[\s\-]*UT$/i', $skuString);
            $isWM2Pattern = preg_match('/^DW-\d+W[\s\-]*\d+(?:\.\d+)?[\s\-]*m2?$/i', $skuString);

            if ($isUtPattern || $isWM2Pattern) {
                $priceRaw = $sheet->getCell("E{$row}")->getValue();
                $price = $this->parsePrice($priceRaw);

                if ($price !== null) {
                    // Приводим артикул к конечному виду
                    $normalizedKey = $this->normalizeEnerpiaSku($skuString);
                    $this->priceMap[$normalizedKey] = $price;
                }
            }
        }

        return $this->priceMap;
    }

    /** Нормалізує артикул для Enerpia */
    private function normalizeEnerpiaSku(string $sku): string
    {
        $sku = trim($sku);

        // 1. Преобразование для серии с сечением: DW-20W 1.5m2 -> DW-20W-1.5, DW-20W 1m2 -> DW-20W-1
        if (preg_match('/^(DW-\d+W)[\s\-]*(\d+(?:\.\d+)?)[\s\-]*m2?$/i', $sku, $matches)) {
            $prefix = strtoupper($matches[1]); // DW-20W
            $size = $matches[2];               // 1.5 или 1

            // Если вдруг написано 1.0, убираем лишний ноль (1.0 -> 1)
            if (str_ends_with($size, '.0')) {
                $size = substr($size, 0, -2);
            }

            $formattedSku = "{$prefix}-{$size}"; // DW-20W-1.5 или DW-20W-1

            return $formattedSku;
        }

        // 2. Преобразование для UT серии (например, DW-10 UT -> DW-10-UT)
        if (preg_match('/^(DW-\d+)[\s\-]*UT$/i', $sku, $matches)) {
            $prefix = strtoupper($matches[1]);
            $formattedSku = "{$prefix}-UT";

            return $formattedSku;
        }

        return $sku;
    }

    /**
     * оптиміззована побудова індексу імпорту для швидкого доступу до рядків за артикулом.
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @return array<string, int>
     */
    private function buildImportIndex($sheet): array
    {
        $index = [];

        // викоростовуємо getRowIterator() для ітерації по рядках, що є більш ефективним для великих файлів
        foreach ($sheet->getRowIterator() as $row) {
            $rowIndex = $row->getRowIndex();
            
            $cellIterator = $row->getCellIterator('B', 'B'); // Читаем ТОЛЬКО колонку B
            $cellIterator->setIterateOnlyExistingCells(true); // Пропускаем пустые виртуальные ячейки

            foreach ($cellIterator as $cell) {
                $skuRaw = $cell->getValue();

                if (!empty($skuRaw)) {
                    // зберігаємо лише перший рядок для кожного артикулу, щоб уникнути дублювання
                    if (!isset($index[$skuRaw])) {
                        $index[$skuRaw] = $rowIndex;
                    }
                }
            }
        }

        return $index;
    }


    /**
     * Форматування ціни з рядка у float. Повертає null, якщо ціна не валідна.
     */
    private function parsePrice($priceRaw): ?float
    {
        if ($priceRaw === null || $priceRaw === '') {
            return null;
        }

        if (is_numeric($priceRaw)) {
            return (float)$priceRaw;
        }

        $cleaned = str_replace(',', '.', (string)$priceRaw);
        $cleaned = preg_replace('/[^\d.]/', '', $cleaned);

        return is_numeric($cleaned) ? (float)$cleaned : null;
    }

    /**
     * Вспомогательный метод для корректной работы с абсолютными и относительными путями.
     */
    private function resolvePath(string $path): string
    {
        // Если путь уже является абсолютным (начинается с / или диска C:\)
        if (str_starts_with($path, '/') || preg_match('~^[a-zA-Z]:[\\\\/]~', $path)) {
            return $path;
        }

        return resource_path($path);
    }
}