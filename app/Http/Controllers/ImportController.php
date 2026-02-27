<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AdvancedProductsImport;

class ImportController extends Controller
{
    /**
     * Предпросмотр файла и определение колонок
     */
    public function preview(Request $request, $shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $file = $request->file('file');
            
            // Для CSV файлов пробуем конвертировать кодировку
            if ($file->getClientOriginalExtension() == 'csv') {
                $content = file_get_contents($file->getPathname());
                // Пробуем определить текущую кодировку
                $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1251', 'KOI8-R'], true);
                if ($encoding && $encoding != 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                    file_put_contents($file->getPathname(), $content);
                }
            }
            
            // Загружаем первую строку для определения заголовков
            $rows = Excel::toArray([], $file);
            
            if (empty($rows) || empty($rows[0])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Файл пуст или имеет неверный формат'
                ], 422);
            }

            $headers = $rows[0][0] ?? []; // Первая строка первого листа
            $sampleData = $rows[0][1] ?? []; // Вторая строка для примера данных

            // Автоматическое определение соответствия колонок
            $columnMapping = $this->detectColumns($headers);
            
            // Формируем пример данных для предпросмотра
            $previewData = [];
            foreach ($columnMapping as $field => $columnIndex) {
                if ($columnIndex !== null && isset($headers[$columnIndex])) {
                    $previewData[$field] = [
                        'column_name' => $headers[$columnIndex],
                        'sample' => $sampleData[$columnIndex] ?? ''
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'headers' => $headers,
                'sample_data' => $sampleData,
                'detected_mapping' => $columnMapping,
                'preview' => $previewData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обработке файла: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Импорт с пользовательским маппингом колонок
     */
    public function import(Request $request, $shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);

        // Проверка лимита товаров
        $productsCount = $shop->products()->count();
        $limit = $user->getProductsLimit();
        
        if ($productsCount >= $limit) {
            return response()->json([
                'success' => false,
                'message' => "Вы достигли лимита товаров для вашего тарифа ({$limit} шт.)"
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'mapping' => 'required',
        ]);

        try {
            $file = $request->file('file');
            
            // Для CSV файлов пробуем конвертировать кодировку
            if ($file->getClientOriginalExtension() == 'csv') {
                $content = file_get_contents($file->getPathname());
                // Пробуем определить текущую кодировку
                $encoding = mb_detect_encoding($content, ['UTF-8', 'Windows-1251', 'KOI8-R'], true);
                if ($encoding && $encoding != 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                    file_put_contents($file->getPathname(), $content);
                }
            }
            
            // Декодируем mapping из JSON
            $mapping = json_decode($request->mapping, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Неверный формат mapping: ' . json_last_error_msg()
                ], 422);
            }

            // Проверяем, что после декодирования получился массив
            if (!is_array($mapping)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mapping должен быть массивом'
                ], 422);
            }

            // Создаем импорт с маппингом
            $import = new AdvancedProductsImport($shopId, $mapping);
            
            // Выполняем импорт
            Excel::import($import, $file);

            $failures = $import->getFailures();
            $successCount = $import->getSuccessCount();
            $totalRows = $import->getRowCount();

            if (count($failures) > 0) {
                $errors = [];
                foreach ($failures as $failure) {
                    $errors[] = [
                        'row' => $failure->row(),
                        'attribute' => $failure->attribute(),
                        'errors' => $failure->errors(),
                        'values' => $failure->values(),
                    ];
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Импорт завершен с ошибками',
                    'success_count' => $successCount,
                    'total_rows' => $totalRows,
                    'errors' => $errors
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Успешно импортировано {$successCount} товаров",
                'count' => $successCount,
                'total_rows' => $totalRows
            ]);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                ];
            }

            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации файла',
                'errors' => $errors
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при импорте: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Автоматическое определение соответствия колонок
     */
    private function detectColumns($headers)
    {
        $mapping = [
            'name' => null,
            'price' => null,
            'description' => null,
            'category' => null,
            'in_stock' => null,
            'image' => null
        ];

        $keywords = [
            'name' => ['название', 'name', 'товар', 'product', 'наименование'],
            'price' => ['цена', 'price', 'стоимость', 'cost', 'amount'],
            'description' => ['описание', 'description', 'desc', 'опис'],
            'category' => ['категория', 'category', 'cat', 'раздел'],
            'in_stock' => ['наличие', 'in stock', 'stock', 'количество', 'quantity', 'in_stock'],
            'image' => ['фото', 'image', 'img', 'picture', 'картинка', 'url']
        ];

        foreach ($headers as $index => $header) {
            if (empty($header)) continue;
            
            $headerLower = strtolower(trim($header));
            
            foreach ($keywords as $field => $variants) {
                foreach ($variants as $variant) {
                    if (str_contains($headerLower, $variant)) {
                        $mapping[$field] = $index;
                        break 2;
                    }
                }
            }
        }

        return $mapping;
    }
}