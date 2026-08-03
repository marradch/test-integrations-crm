<?php

namespace App\Services\Prices\PriceSheetsParsers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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

    public function parse(Spreadsheet $spreadsheet = null): array
    {
        if (empty(static::SHEET_NAME)) {
            throw new Exception("Назву аркуша (SHEET_NAME) не визначено у класі " . static::class);
        }

        // Якщо об'єкт Excel не передано, завантажуємо його з диска
        if ($spreadsheet === null) {
            if (!file_exists($this->priceListPath)) {
                throw new Exception("Файл прайс-листа не знайдено: {$this->priceListPath}");
            }
            $spreadsheet = IOFactory::load($this->priceListPath);
        }

        $sheet = $spreadsheet->getSheetByName(static::SHEET_NAME);

        if ($sheet === null) {
            // Можна замість помилки просто повертати порожній масив або логувати попередження
            return [];
        }

        $this->priceMap = [];
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
     * "2,50 м²" -> "2.5", "2,00 м²" -> "2", "0,50" -> "0.5"
     */
    protected function cleanNumericParam(string $val): ?string
    {
        // 1. Замінюємо кому на крапку
        $cleaned = str_replace(',', '.', trim($val));

        // 2. Видаляємо все, крім цифр і крапки (пробели, м², символи)
        $cleaned = preg_replace('/[^\d.]/', '', $cleaned);

        // 3. Перевіряємо, чи залишилося число
        if ($cleaned === '' || !is_numeric($cleaned)) {
            return null;
        }

        // 4. Приведення до float видаляє усі незначащі нулі наприкінці (2.50 -> 2.5, 2.00 -> 2)
        return (string)(float)$cleaned;
    }
}