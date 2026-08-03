<?php
namespace App\Services\Prices;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Exception;
use App\Services\Prices\PriceSheetsParsers\{EnerpiaSheetParser, DeviSheetParser};

class UpdateImportFromPriceService
{
    protected string $priceListPath;
    protected string $importFilePath;
    protected string $outputFilePath;
    protected array $priceMap = [];

    public function __construct(
        private EnerpiaSheetParser $enerpiaSheetParser,
        private DeviSheetParser $deviSheetParser
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

        echo "Зчитуємо прайс-лист і будуємо карту артикулів та цін...\n";

        echo "Зчитуємо Enerpia Cable прайс-лист...\n";
        $this->priceMap = array_merge($this->priceMap, $this->enerpiaSheetParser->parse());
        echo "Зчитуємо Devi прайс-лист...\n";
        $this->priceMap = array_merge($this->priceMap, $this->deviSheetParser->parse());

        if (empty($this->priceMap)) {
            throw new Exception("Карта цін пуста. Перевірте файл прайс-листа на наявність даних.");
        }

        echo "Побудова індексу імпорту...\n";
        $importSpreadsheet = IOFactory::load($this->importFilePath);
        echo "Файл імпорту зчитано успішно.\n";
        $sheet = $importSpreadsheet->getActiveSheet();
        $importRowMap = $this->buildImportIndex($sheet);
        $this->updateImportFile();        
    }

    private function updateImportFile(): void
    {
        $importSpreadsheet = IOFactory::load($this->importFilePath);
        $sheet = $importSpreadsheet->getActiveSheet();
        $importRowMap = $this->buildImportIndex($sheet);

        $currentDate = date('Y-m-d H:i:s');
        $lightBlueColor = 'FFE0F2FE';

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
}