<?php

namespace App\Services\Prices\PriceSheetsParsers;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ArnoldRakStandartSheetParser extends BaseSheetParser
{
    protected const SHEET_NAME = 'Arnold Rak Standart';

    /**
     * Основний метод обробки аркуша Arnold Rak Standart.
     */
    protected function processSheet(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();

        for ($row = 1; $row <= $highestRow; $row++) {
            $colA = (string) $sheet->getCell("A{$row}")->getValue();
            $colD = (string) $sheet->getCell("D{$row}")->getValue();

            if (empty($colA) || empty($colD) || !str_contains($colA, "FH-EC")) {
                continue;
            }

            $skuString = trim((string)$colA);

            // Намагаємося сформувати артикул
            $formattedSku = $this->parseSku($colD);

            if ($formattedSku !== null) {
                // Ціну беремо з колонки D
                $price = $this->parsePrice($sheet->getCell("E{$row}")->getValue());

                if ($price !== null) {
                    $this->priceMap[$formattedSku] = $price;
                }
            }
        }        
    }

    private function parseSku(string $colD): ?string
    {
        // Очищаємо параметри з колонки (площа): "0,5" -> "0.5", "10.0" -> "10"
        $paramCleaned = $this->cleanNumericParam($colD);

        if ($paramCleaned === null) {
            return null;
        }

        $baseCode = "FH-EC";

        // Формуємо фінальний артикул
        return "{$baseCode}-{$paramCleaned}";
    }

}