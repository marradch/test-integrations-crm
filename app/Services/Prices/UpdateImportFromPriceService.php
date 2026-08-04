<?php
namespace App\Services\Prices;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Exception;
use App\Services\Prices\PriceSheetsParsers\{
    EnerpiaSheetParser, DeviSheetParser, ArnoldRakStandartSheetParser, ArnoldRakPremiumSheetParser
};
use OpenSpout\Reader\XLSX\Reader as XLSXReader;

class UpdateImportFromPriceService
{
    protected string $priceListPath;
    protected string $importFilePath;
    protected string $outputFilePath;
    protected array $priceMap = [];

    public function __construct(
        private EnerpiaSheetParser $enerpiaSheetParser,
        private DeviSheetParser $deviSheetParser,
        private ArnoldRakStandartSheetParser $arnoldRakStandartSheetParser,
        private ArnoldRakPremiumSheetParser $arnoldRakPremiumSheetParser
    ) {
        $this->priceListPath = resource_path('excel/Price.xlsx');
        $this->importFilePath = resource_path('excel/Import.xlsx');
        $this->outputFilePath = resource_path('excel/Output.xlsx');
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

        $this->runParsers();

        if (empty($this->priceMap)) {
            throw new Exception("Карта цін пуста. Перевірте файл прайс-листа на наявність даних.");
        }

        $this->updateImportFile();        
    }

    private function runParsers(): void
    {
        echo "Зчитуємо прайс-лист і будуємо карту артикулів та цін...\n";

        echo "Завантаження Excel файлу прайс-листа...\n";
        $spreadsheet = IOFactory::load($this->priceListPath);

        $parsers = [
            $this->enerpiaSheetParser,
            $this->deviSheetParser,
            $this->arnoldRakStandartSheetParser,
            $this->arnoldRakPremiumSheetParser,
        ];

        foreach ($parsers as $parser) {
            echo "Зчитуємо " . get_class($parser) . " прайс-лист...\n";
            
            // Передаємо завантажений $spreadsheet у кожен парсер
            $parsedPrices = $parser->parse($spreadsheet);
            
            // Оптимізований аналог array_merge без створення зайвих проміжних масивів
            foreach ($parsedPrices as $sku => $price) {
                $this->priceMap[$sku] = $price;
            }
        }
        
        // Звільняємо пам'ять від важкого об'єкта Excel
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    private function updateImportFile(): void
    {
        $importRowMap = $this->buildImportIndex();

        $currentDate = date('Y-m-d H:i:s');
        $lightBlueColor = 'FFE0F2FE';

        echo "Оновлюємо файл імпорту на основі карти цін...\n";
        $importSpreadsheet = IOFactory::load($this->importFilePath);
        $sheet = $importSpreadsheet->getActiveSheet();

        foreach ($this->priceMap as $searchKey => $basePrice) {
            if (isset($importRowMap[$searchKey])) {
                $row = $importRowMap[$searchKey];
                $newPrice = round($basePrice * 1.10, 2);

                // Update price in column H
                $sheet->setCellValue("H{$row}", $newPrice);
                // Highlight the cell in light blue
                $sheet->getStyle("H{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB($lightBlueColor);
                // Update date in column CS
                $sheet->setCellValue("CS{$row}", $currentDate);
            }
        }

        // Save the updated import file
        $savePath = $this->outputFilePath;
        $writer = IOFactory::createWriter($importSpreadsheet, pathinfo($savePath, PATHINFO_EXTENSION) === 'xls' ? 'Xls' : 'Xlsx');
        $writer->save($savePath);

        echo "Файл імпорту успішно оновлено та збережено: {$savePath}\n";
    }    

    /**
     * оптиміззована побудова індексу імпорту для швидкого доступу до рядків за артикулом.
     * 
     * @return array<string, int>
     */
    private function buildImportIndex(): array
    {
        echo "Побудова індексу імпорту через OpenSpout...\n";

        $index = [];

        $reader = new XLSXReader();
        $reader->open($this->importFilePath);

        foreach ($reader->getSheetIterator() as $sheet) {
            $rowIndex = 1;

            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();

                // Колонка B має індекс 1 (0 = A, 1 = B)
                if (isset($cells[1])) {
                    $skuRaw = trim((string)$cells[1]->getValue());

                    if (!empty($skuRaw) && !isset($index[$skuRaw])) {
                        $index[$skuRaw] = $rowIndex;
                    }
                }

                $rowIndex++;
            }

            // Обробляємо лише перший активний аркуш
            break;
        }

        $reader->close();

        return $index;
    }
}