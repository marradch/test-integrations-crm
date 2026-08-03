<?php

namespace App\Services\Prices\PriceSheetsParsers;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ArnoldRakPremiumSheetParser extends BaseSheetParser
{
    protected const SHEET_NAME = 'Arnold Rak Premium';

    /**
     * Основний метод обробки аркуша Arnold Rak Premium.
     */
    protected function processSheet(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();

        for ($row = 1; $row <= $highestRow; $row++) {
            $colB = (string) $sheet->getCell("B{$row}")->getValue();
            $colE = (string) $sheet->getCell("E{$row}")->getValue();

            if (empty($colB) || empty($colE) || !str_contains($colB, "FH L")) {
                continue;
            }

            $skuString = trim((string)$colB);

            // Намагаємося сформувати артикул
            $formattedSku = $this->parseSku($colE);

            if ($formattedSku !== null) {
                // Ціну беремо з колонки D
                $price = $this->parsePrice($sheet->getCell("F{$row}")->getValue());

                if ($price !== null) {
                    $this->priceMap[$formattedSku] = $price;
                }
            }
        }
    }

    private function parseSku(string $colE): ?string
    {
        // Очищаємо параметри з колонки (площа): "0,5" -> "0.5", "10.0" -> "10"
        $paramCleaned = $this->cleanNumericParam($colE);

        if ($paramCleaned === null) {
            return null;
        }

        $baseCode = "fhl"; // Базовий код для Arnold Rak Premium

        // Формуємо фінальний артикул
        return "{$baseCode}-{$paramCleaned}";
    }

}