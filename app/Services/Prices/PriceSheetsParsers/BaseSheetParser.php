<?php

namespace App\Services\Prices\PriceSheetsParsers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Exception;

abstract class BaseSheetParser
{
    // Ім'я листа, який буде оброблятися конкретним парсером. Кожен дочірній клас повинен визначити його.
    protected const SHEET_NAME = '';

    protected string $priceListPath;
    protected array $priceMap = [];

    public function __construct(?string $priceListPath = null)
    {
        $this->priceListPath = $priceListPath ?? resource_path('excel/Price.xlsx');
    }

    /**
     * Основний метод для парсингу прайс-листа. Викликає метод обробки конкретного листа.
     *
     * @return array<string, float> [Артикул => Цена]
     * @throws Exception
     */
    public function parse(): array
    {
        if (empty(static::SHEET_NAME)) {
            throw new Exception("Ім'я листа (SHEET_NAME) не визначено в класі " . static::class);
        }

        if (!file_exists($this->priceListPath)) {
            throw new Exception("Файл прайс-листа не знайдено: {$this->priceListPath}");
        }

        $spreadsheet = IOFactory::load($this->priceListPath);
        $sheet = $spreadsheet->getSheetByName(static::SHEET_NAME);

        if ($sheet === null) {
            throw new Exception("Лист с названием '" . static::SHEET_NAME . "' не знайдено в файлі.");
        }

        // Викликаємо метод обробки конкретного листа, який реалізується в дочірніх класах
        $this->processSheet($sheet);

        return $this->priceMap;
    }

    /**
     * метод, який повинен бути реалізований у дочірніх класах для обробки конкретного листа.
     */
    abstract protected function processSheet(Worksheet $sheet): void;

    /**
     * Допоміжний метод для парсингу ціни з рядка. Повертає null, якщо ціна не валідна.
     *
     * @param mixed $priceRaw
     * @return float|null
     */
    protected function parsePrice($priceRaw): ?float
    {
        if ($priceRaw === null || $priceRaw === '') {
            return null;
        }

        if (is_numeric($priceRaw)) {
            return (float)$priceRaw;
        }

        // Заміна коми на крапку та видалення всіх символів, крім цифр та крапки
        $cleaned = str_replace(',', '.', (string)$priceRaw);
        $cleaned = preg_replace('/[^\d.]/', '', $cleaned);

        return is_numeric($cleaned) ? (float)$cleaned : null;
    }

    /**
     * Очистка числового параметра від небажаних символів та приведення його до стандартного формату.
     */
    protected function cleanNumericParam(string $val): ?string
    {
        $cleaned = str_replace(',', '.', trim($val));
        $cleaned = preg_replace('/[^\d.]/', '', $cleaned);

        if ($cleaned === '' || !is_numeric($cleaned)) {
            return null;
        }

        // Якщо число закінчується на .0, видаляємо цей нуль (наприклад, 1.0 -> 1)
        if (str_ends_with($cleaned, '.0')) {
            $cleaned = substr($cleaned, 0, -2);
        }

        return $cleaned;
    }
}