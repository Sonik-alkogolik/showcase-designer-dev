<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Validators\Failure;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithStartRow
{
    use Importable;

    protected $shopId;
    protected $failures = [];
    protected $rowCount = 0;

    public function __construct($shopId)
    {
        $this->shopId = $shopId;
    }

    /**
     * Начинаем со второй строки (пропускаем заголовок)
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $this->rowCount++;
        
        return new Product([
            'shop_id' => $this->shopId,
            'name' => $row['название'] ?? $row['name'] ?? null,
            'price' => $row['цена'] ?? $row['price'] ?? null,
            'description' => $row['описание'] ?? $row['description'] ?? null,
            'category' => $row['категория'] ?? $row['category'] ?? null,
            'in_stock' => ($row['наличие'] ?? $row['in_stock'] ?? '1') == '1' || 
                          ($row['наличие'] ?? $row['in_stock'] ?? '1') == 'да' || 
                          ($row['наличие'] ?? $row['in_stock'] ?? '1') == 'yes',
        ]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'название' => 'required_without:name|string|max:255',
            'name' => 'required_without:название|string|max:255',
            'цена' => 'required_without:price|numeric|min:0',
            'price' => 'required_without:цена|numeric|min:0',
            'описание' => 'nullable|string',
            'description' => 'nullable|string',
            'категория' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'наличие' => 'nullable|in:0,1,да,нет,yes,no',
            'in_stock' => 'nullable|in:0,1,да,нет,yes,no',
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'название.required_without' => 'Поле "Название" обязательно',
            'name.required_without' => 'Поле "Name" обязательно',
            'цена.required_without' => 'Поле "Цена" обязательно',
            'price.required_without' => 'Поле "Price" обязательно',
            'цена.numeric' => 'Цена должна быть числом',
            'price.numeric' => 'Price must be a number',
            'наличие.in' => 'Наличие должно быть: 0,1,да,нет,yes,no',
            'in_stock.in' => 'In stock must be: 0,1,yes,no',
        ];
    }

    /**
     * @param Failure ...$failures
     */
    public function onFailure(Failure ...$failures)
    {
        $this->failures = array_merge($this->failures, $failures);
    }

    /**
     * @return array
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
}