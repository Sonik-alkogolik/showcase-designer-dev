<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class AdvancedProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithChunkReading
{
    use Importable;

    const STANDARD_FIELDS = ['name', 'price', 'description', 'category', 'in_stock', 'image'];

    /**
     * Поля, которые не стоит показывать пользователю как attributes.
     * Они служебные и только засоряют карточку товара.
     */
    private const HIDDEN_ATTRIBUTE_KEYS = [
        'product_id',
        'categories',
        'main_category',
        'upc',
        'ean',
        'jan',
        'isbn',
        'mpn',
        'location',
        'points',
        'date_added',
        'date_modified',
        'date_available',
        'weight',
        'weight_unit',
        'length',
        'width',
        'height',
        'length_unit',
        'status',
        'tax_class_id',
        'seo_keyword',
        'meta_title',
        'meta_title(ru-ru)',
        'meta_description',
        'meta_description(ru-ru)',
        'meta_h1',
        'meta_h1(ru-ru)',
        'meta_keywords',
        'meta_keywords(ru-ru)',
        'stock_status',
        'stock_status_id',
        'store_ids',
        'layout',
        'related_ids',
        'sort_order',
        'subtract',
        'minimum',
        'descriptionru_ru',
        'description(ru-ru)',
        'name(ru-ru)',
        'tags(ru-ru)',
    ];

    protected $shopId;
    protected $mapping;
    protected $failures = [];
    protected $rowCount = 0;
    protected $successCount = 0;
    protected $availableSlots = 0;
    protected $importedWithinLimit = 0;
    protected $skippedDueToLimit = 0;

    public function __construct($shopId, array $mapping, int $availableSlots = 0)
    {
        $this->shopId = $shopId;
        $this->mapping = $mapping;
        $this->availableSlots = max(0, $availableSlots);
    }

    /**
     * Безопасная нормализация строки в UTF-8.
     * Не перекодируем повторно, если строка уже валидная UTF-8.
     */
    private function convertToUtf8($value)
    {
        if ($value === null || $value === '' || is_numeric($value) || is_bool($value)) {
            return $value;
        }

        $value = (string) $value;

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $encoding = mb_detect_encoding($value, ['UTF-8', 'Windows-1251', 'KOI8-R', 'ISO-8859-5'], true);

        if ($encoding) {
            return mb_convert_encoding($value, 'UTF-8', $encoding);
        }

        return $value;
    }

    /**
     * Нормализация текстовых полей.
     */
    private function normalizeText($value, bool $stripHtml = true): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = $this->convertToUtf8($value);
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($stripHtml) {
            $value = strip_tags($value);
        }

        $value = preg_replace('/[\x{00AD}\x{200B}-\x{200D}\x{FEFF}]/u', '', $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * Нормализация ключа атрибута для сравнения/фильтрации.
     */
    private function normalizeAttributeKey(string $key): string
    {
        $key = $this->convertToUtf8($key);
        $key = mb_strtolower(trim($key));
        $key = preg_replace('/\s+/u', ' ', $key);

        return $key;
    }

    /**
     * Красивое имя атрибута для хранения/показа.
     */
    private function cleanAttributeName(string $key): ?string
    {
        $key = $this->convertToUtf8($key);
        $key = trim($key);
        $key = preg_replace('/[^\p{L}\p{N}\s\-_()]/u', '', $key);
        $key = preg_replace('/\s+/u', ' ', $key);
        $key = trim($key);

        return $key !== '' ? $key : null;
    }

    /**
     * Надо ли скрыть служебный атрибут.
     */
    private function shouldSkipAttribute(string $key): bool
    {
        $normalizedKey = $this->normalizeAttributeKey($key);

        if (in_array($normalizedKey, self::HIDDEN_ATTRIBUTE_KEYS, true)) {
            return true;
        }

        if (Str::startsWith($normalizedKey, ['meta_', 'oc_', '_'])) {
            return true;
        }

        return false;
    }

    /**
     * Нормализует значение "наличие" из CSV/XLSX в boolean.
     * Пустое значение трактуем как true.
     */
    private function normalizeInStock($rawValue): bool
    {
        if (is_bool($rawValue)) {
            return $rawValue;
        }

        if ($rawValue === null) {
            return true;
        }

        $value = trim((string) $rawValue);
        if ($value === '') {
            return true;
        }

        if (is_numeric($value)) {
            return ((float) $value) > 0;
        }

        $normalized = mb_strtolower($value);

        if (in_array($normalized, ['да', 'yes', 'true', '+', 'on', 'y', 'есть', 'available', 'instock'], true)) {
            return true;
        }

        if (in_array($normalized, ['нет', 'no', 'false', '-', 'off', 'n', 'нет в наличии', 'outofstock'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Нормализует цену.
     */
    private function normalizePrice($rawPrice): ?float
    {
        if ($rawPrice === null || $rawPrice === '') {
            return null;
        }

        $price = preg_replace('/[^0-9,.\-]/', '', (string) $rawPrice);

        if ($price === '' || $price === '-' || $price === '.' || $price === ',') {
            return null;
        }

        // Если и точка, и запятая встречаются, считаем запятую разделителем тысяч.
        if (str_contains($price, ',') && str_contains($price, '.')) {
            $price = str_replace(',', '', $price);
        } else {
            $price = str_replace(',', '.', $price);
        }

        return is_numeric($price) ? (float) $price : null;
    }

    /**
     * Получить mapped-значение по индексу колонки.
     */
    private function getMappedValue(array $row, array $keys, $columnIndex)
    {
        if ($columnIndex === null || !isset($keys[$columnIndex])) {
            return null;
        }

        $key = $keys[$columnIndex];

        return $row[$key] ?? null;
    }

    public function model(array $row)
    {
        $this->rowCount++;

        if ($this->rowCount > 200) {
            return null;
        }

        $keys = array_keys($row);

        $data = [];
        $attributes = [];

        foreach (self::STANDARD_FIELDS as $field) {
            $columnIndex = $this->mapping[$field] ?? null;
            $value = $this->getMappedValue($row, $keys, $columnIndex);

            if (in_array($field, ['name', 'description', 'category'], true)) {
                $value = $this->normalizeText($value);
            }

            $data[$field] = $value;
        }

        foreach ($keys as $index => $key) {
            $isMapped = false;

            foreach (self::STANDARD_FIELDS as $field) {
                if (($this->mapping[$field] ?? null) === $index) {
                    $isMapped = true;
                    break;
                }
            }

            if ($isMapped) {
                continue;
            }

            $rawValue = $row[$key] ?? null;
            if ($rawValue === null || $rawValue === '') {
                continue;
            }

            if ($this->shouldSkipAttribute((string) $key)) {
                continue;
            }

            $attributeName = $this->cleanAttributeName((string) $key);
            if (!$attributeName) {
                continue;
            }

            $attributeValue = $this->normalizeText($rawValue, false);
            if ($attributeValue === null || $attributeValue === '') {
                continue;
            }

            $attributes[$attributeName] = $attributeValue;
        }

        if (empty($data['name'])) {
            return null;
        }

        $price = $this->normalizePrice($data['price'] ?? null);
        if ($price === null) {
            return null;
        }

        if ($this->importedWithinLimit >= $this->availableSlots) {
            $this->skippedDueToLimit++;
            return null;
        }

        $data['in_stock'] = $this->normalizeInStock($data['in_stock'] ?? null);

        $categoryId = null;
        $categoryName = $data['category'] ?? null;

        if ($categoryName) {
            $category = Category::where('shop_id', $this->shopId)
                ->where('name', $categoryName)
                ->first();

            if (!$category) {
                $category = Category::create([
                    'shop_id' => $this->shopId,
                    'name' => $categoryName,
                    'slug' => Str::slug($categoryName),
                    'sort_order' => 0,
                    'is_active' => true,
                ]);
            }

            $categoryId = $category->id;
        } else {
            $miscCategory = Category::where('shop_id', $this->shopId)
                ->where('name', 'Без категории')
                ->first();

            if ($miscCategory) {
                $categoryId = $miscCategory->id;
            }
        }

        $this->successCount++;
        $this->importedWithinLimit++;

        return new Product([
            'shop_id' => $this->shopId,
            'category_id' => $categoryId,
            'name' => $data['name'],
            'price' => $price,
            'description' => $data['description'] ?? null,
            'category' => $data['category'] ?? null,
            'in_stock' => $data['in_stock'],
            'image' => $data['image'] ?? null,
            'attributes' => !empty($attributes) ? $attributes : null,
        ]);
    }

    public function rules(): array
    {
        return [];
    }

    public function onFailure(Failure ...$failures)
    {
        $this->failures = array_merge($this->failures, $failures);
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getFailures()
    {
        return $this->failures;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getSkippedDueToLimit(): int
    {
        return $this->skippedDueToLimit;
    }
}
