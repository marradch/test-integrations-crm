<?php

namespace App\Services\Prices\PriceSheetsParsers;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DeviSheetParser extends BaseSheetParser
{
    /** Назва аркуша у файлі Excel */
    protected const SHEET_NAME = 'Devi';

    /**
     * Основний метод обробки аркуша Devi.
     */
    protected function processSheet(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();

        for ($row = 1; $row <= $highestRow; $row++) {
            $colA = $sheet->getCell("A{$row}")->getValue();
            $colC = $sheet->getCell("C{$row}")->getValue(); // Площа або довжина

            if (empty($colA) || empty($colC)) {
                continue;
            }

            $skuString = trim((string)$colA);

            // Намагаємося сформувати артикул (для 1-ї або 2-ї таблиці)
            $formattedSku = $this->parseDeviSku($skuString, (string)$colC);

            if ($formattedSku !== null) {
                // Ціну беремо з колонки D
                $price = $this->parsePrice($sheet->getCell("D{$row}")->getValue());

                if ($price !== null) {
                    $this->priceMap[$formattedSku] = $price;
                }
            }
        }
    }

    /**
     * Визначення та формування артикула для товарів Devi.
     * 
     * @param string $colA Значення з колонки A (назва/артикул)
     * @param string $colC Значення з колонки C (площа або довжина)
     * @return string|null Повертає сформований артикул або null
     */
    private function parseDeviSku(string $colA, string $colC): ?string
    {
        // Очищаємо параметри з колонки C (площа/довжина): "0,5" -> "0.5", "10.0" -> "10"
        $paramCleaned = $this->cleanNumericParam($colC);

        if ($paramCleaned === null) {
            return null;
        }

        $baseCode = null;

        // -------------------------------------------------------------
        // ТАБЛИЦЯ 1: Шукаємо код у дужках (наприклад: "DEVIcomfortTM 150T (DTIR-150)")
        // -------------------------------------------------------------
        if (str_contains($colA, "DEVIcomfortTM 150T (DTIR-150)")) {
            $baseCode = "DTIR-150";
        }
        
        // -------------------------------------------------------------
        // ТАБЛИЦЯ 2: Якщо дужок немає (наприклад: "DEVIflexTM 18T")
        // -------------------------------------------------------------
        else if (str_contains($colA, "DEVIflexTM 18T")) {
            $baseCode = "DTIP-18";
        }

        // Якщо базовий код (DTIR-150 або DTIP-18) визначити не вдалося — пропускаємо рядок
        if ($baseCode === null) {
            return null;
        }

        // Формуємо фінальний артикул (наприклад: DTIR-150-0.5 або DTIP-18-105)
        return "{$baseCode}-{$paramCleaned}";
    }
}