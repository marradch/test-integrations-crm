<?php

namespace App\Services\Prices\PriceSheetsParsers;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EnerpiaSheetParser extends BaseSheetParser
{
    protected const SHEET_NAME = 'Enerpia Cable';

    protected function processSheet(Worksheet $sheet): void
    {
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
}