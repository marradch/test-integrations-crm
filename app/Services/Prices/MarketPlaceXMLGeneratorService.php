<?php

namespace App\Services\Prices;

use OpenSpout\Reader\XLSX\Reader as XLSXReader;
use XMLWriter;
use Exception;
use Illuminate\Support\Facades\File;

class MarketPlaceXMLGeneratorService
{
    protected string $importFilePath;
    protected string $outputFilePath;
    
    // Розмір чанку для періодичного скидання буфера на диск (flush)
    protected int $chunkSize = 500;

    public function __construct() {
        $this->importFilePath = resource_path('excel/Output.xlsx');
        $this->outputFilePath = public_path('xml/Output.xml');
    }

    public function generateXML(): void
    {
        if (!file_exists($this->importFilePath)) {
            throw new Exception("Файл імпорту не знайдено: {$this->importFilePath}");
        }

        $directory = public_path('xml');

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // 1. Ініціалізуємо XMLWriter для потокового запису у файл
        $xmlWriter = new XMLWriter();
        $xmlWriter->openURI($this->outputFilePath);
        $xmlWriter->startDocument('1.0', 'UTF-8');
        $xmlWriter->setIndent(true);

        // Відкриваємо кореневий тег <products>
        $xmlWriter->startElement('products');

        // 2. Ініціалізуємо OpenSpout для потокового зчитування Excel
        $reader = new XLSXReader();
        $reader->open($this->importFilePath);

        $processedCount = 0;

        // Зчитуємо аркуші та рядки по одному, не завантажуючи файл у пам'ять
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCells();

                // Отримуємо значення з потрібних колонок за індексами (0 = A, 1 = B, 2 = C, і так далі)
                $id           = isset($cells[0]) ? trim((string)$cells[0]->getValue()) : '';
                $sku          = isset($cells[1]) ? trim((string)$cells[1]->getValue()) : '';
                $name         = isset($cells[2]) ? trim((string)$cells[2]->getValue()) : '';
                $price        = isset($cells[6]) ? trim((string)$cells[6]->getValue()) : '';  // Колонка G (індекс 6)
                $oldPrice     = isset($cells[7]) ? trim((string)$cells[7]->getValue()) : '';  // Колонка H (індекс 7)
                $link         = isset($cells[91]) ? trim((string)$cells[91]->getValue()) : ''; // Колонка CN (індекс 91)
                $lastModified = isset($cells[96]) ? trim((string)$cells[96]->getValue()) : ''; // Колонка CS (індекс 96)

                // Валідація
                if (!intval($id) || empty($sku) || empty($name) || !is_numeric($price)) {
                    continue;
                }

                // Записуємо вузол <product> у потік
                $xmlWriter->startElement('product');
                $xmlWriter->writeElement('id', $id);
                $xmlWriter->writeElement('sku', $sku);
                $xmlWriter->writeElement('name', $name);
                $xmlWriter->writeElement('price', $price);
                $xmlWriter->writeElement('old_price', $oldPrice);
                $xmlWriter->writeElement('link', $link);
                
                if (!empty($lastModified)) {
                    $xmlWriter->writeElement('last_modified', $lastModified);
                }
                
                $xmlWriter->endElement(); // </product>

                $processedCount++;

                // Чанкування: скидаємо буфер у файл кожні N записів, щоб не забивати RAM
                if ($processedCount % $this->chunkSize === 0) {
                    $xmlWriter->flush();
                }
            }
            
            // Обробляємо лише перший активний аркуш
            break; 
        }

        $reader->close();

        // Закриваємо кореневий тег </products> та завершуємо документ
        $xmlWriter->endElement(); 
        $xmlWriter->endDocument();
        $xmlWriter->flush();
    }
}