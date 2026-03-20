<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Log;

class AdvancedProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithChunkReading
{
    use Importable;

    // Стандартные поля товара
    const STANDARD_FIELDS = ['name', 'price', 'description', 'category', 'in_stock', 'image'];

    protected $shopId;
    protected $mapping;
    protected $failures = [];
    protected $rowCount = 0;
    protected $successCount = 0;

    public function __construct($shopId, array $mapping)
    {
        $this->shopId = $shopId;
        $this->mapping = $mapping;
    }

    /**
     * Принудительная конвертация в UTF-8
     */
    private function convertToUtf8($value)
    {
        if (empty($value) || is_numeric($value)) {
            return $value;
        }
        
        // Пробуем разные варианты
        $converted = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        
        // Если после конвертации получилась битая строка, пробуем другие кодировки
        if (strpos($converted, '�') !== false || preg_match('/[^\x20-\x7E\x80-\xFF]/', $converted)) {
            // Пробуем из Windows-1251
            $converted = mb_convert_encoding($value, 'UTF-8', 'Windows-1251');
        }
        
        if (strpos($converted, '�') !== false || preg_match('/[^\x20-\x7E\x80-\xFF]/', $converted)) {
            // Пробуем из KOI8-R
            $converted = mb_convert_encoding($value, 'UTF-8', 'KOI8-R');
        }
        
        if (strpos($converted, '�') !== false || preg_match('/[^\x20-\x7E\x80-\xFF]/', $converted)) {
            // Пробуем из ISO-8859-5
            $converted = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-5');
        }
        
        return $converted;
    }

 /**
 * @param array $row
 * @return \Illuminate\Database\Eloquent\Model|null
 */
public function model(array $row)
{
    $this->rowCount++;
    
    // Получаем ключи (заголовки) строки
    $keys = array_keys($row);
    
    // Применяем маппинг колонок для стандартных полей
    $data = [];
    $attributes = []; // здесь будут дополнительные атрибуты
    
    foreach ($this->mapping as $field => $columnIndex) {
        if ($columnIndex !== null && isset($keys[$columnIndex])) {
            $key = $keys[$columnIndex];
            $value = $row[$key] ?? null;
            
            // Конвертируем текстовые поля в UTF-8
            if ($value && in_array($field, ['name', 'description', 'category'])) {
                $value = $this->convertToUtf8($value);
            }
            
            $data[$field] = $value;
        }
    }
    
    // Собираем все колонки, которые не попали в маппинг стандартных полей
    foreach ($keys as $index => $key) {
        // Проверяем, используется ли эта колонка в маппинге
        $isMapped = false;
        foreach ($this->mapping as $field => $columnIndex) {
            if ($columnIndex === $index) {
                $isMapped = true;
                break;
            }
        }
        
        // Если колонка не используется и в ней есть данные - сохраняем как атрибут
        if (!$isMapped && isset($row[$key]) && !empty($row[$key])) {
            $attributeName = $this->convertToUtf8($key); // название колонки
            $attributeValue = $this->convertToUtf8($row[$key]); // значение
            
            // Очищаем название атрибута (убираем спецсимволы, делаем читаемым)
            $attributeName = trim($attributeName);
            $attributeName = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $attributeName);
            
            $attributes[$attributeName] = $attributeValue;
        }
    }

    // Проверяем обязательные поля
    if (empty($data['name']) || empty($data['price'])) {
        return null;
    }

    // Преобразование наличия
    if (isset($data['in_stock']) && !empty($data['in_stock'])) {
        $value = strtolower(trim($data['in_stock']));
        $data['in_stock'] = in_array($value, ['1', 'да', 'yes', 'true', '+', 'on']);
    } else {
        $data['in_stock'] = true;
    }

    // Преобразование цены
    $price = preg_replace('/[^0-9,.]/', '', $data['price']);
    $price = str_replace(',', '.', $price);
    $price = (float) $price;

    // Обработка категории
    $categoryId = null;
    $categoryName = $data['category'] ?? null;
    
    if ($categoryName) {
        // Пытаемся найти существующую категорию
        $category = \App\Models\Category::where('shop_id', $this->shopId)
            ->where('name', $categoryName)
            ->first();
        
        if ($category) {
            $categoryId = $category->id;
        } else {
            // Создаём новую категорию
            $category = \App\Models\Category::create([
                'shop_id' => $this->shopId,
                'name' => $categoryName,
                'slug' => \Illuminate\Support\Str::slug($categoryName),
                'sort_order' => 0,
                'is_active' => true,
            ]);
            $categoryId = $category->id;
        }
    } else {
        // Если категория не указана, ищем "Без категории"
        $miscCategory = \App\Models\Category::where('shop_id', $this->shopId)
            ->where('name', 'Без категории')
            ->first();
        
        if ($miscCategory) {
            $categoryId = $miscCategory->id;
        }
    }

    $this->successCount++;
    
    return new Product([
        'shop_id' => $this->shopId,
        'category_id' => $categoryId,
        'name' => $data['name'],
        'price' => $price,
        'description' => $data['description'] ?? null,
        'category' => $data['category'] ?? null, // пока оставляем для обратной совместимости
        'in_stock' => $data['in_stock'],
        'image' => $data['image'] ?? null,
        'attributes' => !empty($attributes) ? $attributes : null,
    ]);
}

    /**
     * Валидация строк
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Обработка ошибок валидации
     */
    public function onFailure(Failure ...$failures)
    {
        $this->failures = array_merge($this->failures, $failures);
    }

    /**
     * Чтение чанками для оптимизации
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Получить ошибки
     */
    public function getFailures()
    {
        return $this->failures;
    }

    /**
     * Получить количество обработанных строк
     */
    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    /**
     * Получить количество успешно импортированных
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }
}