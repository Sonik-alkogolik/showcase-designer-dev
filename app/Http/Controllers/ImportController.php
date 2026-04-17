<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AdvancedProductsImport;

class ImportController extends Controller
{
    private function getShopProductLimitContext($user, $shop): array
    {
        $limit = $user->getProductsLimit();
        $currentCount = $shop->products()->count();
        $availableSlots = max(0, $limit - $currentCount);

        return [
            'limit' => $limit,
            'current_count_before_import' => $currentCount,
            'available_slots_before_import' => $availableSlots,
        ];
    }

    /**
     * Предпросмотр файла и определение колонок
     */
    public function preview(Request $request, $shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        $limitContext = $this->getShopProductLimitContext($user, $shop);

        if (! $user->canImportExcel()) {
            return response()->json([
                'success' => false,
                'message' => 'Импорт из Excel доступен только на платном тарифе',
                'can_import_excel' => false,
                ...$limitContext,
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain|max:10240',
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

            // Определяем дополнительные колонки (которые не попали в стандартный маппинг)
            $extraColumns = [];
            foreach ($headers as $index => $header) {
                if (!in_array($index, array_values($columnMapping))) {
                    $extraColumns[] = [
                        'index' => $index,
                        'name' => $header,
                        'sample' => $sampleData[$index] ?? ''
                    ];
                }
            }
            
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
                'extra_columns' => $extraColumns,
                'preview' => $previewData,
                'can_import_excel' => true,
                ...$limitContext,
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
        $limitContext = $this->getShopProductLimitContext($user, $shop);

        if (! $user->canImportExcel()) {
            return response()->json([
                'success' => false,
                'message' => 'Импорт из Excel доступен только на платном тарифе',
                'can_import_excel' => false,
                ...$limitContext,
            ], 403);
        }

        if ($limitContext['available_slots_before_import'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => "Вы достигли лимита товаров для вашего тарифа ({$limitContext['limit']} шт.)",
                'can_import_excel' => true,
                ...$limitContext,
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,text/plain|max:10240',
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
            $import = new AdvancedProductsImport(
                $shopId,
                $mapping,
                $limitContext['available_slots_before_import']
            );
            
            // Выполняем импорт
            Excel::import($import, $file);

            $failures = $import->getFailures();
            $successCount = $import->getSuccessCount();
            $totalRows = $import->getRowCount();
            $skippedDueToLimit = $import->getSkippedDueToLimit();
            $responseMeta = [
                'success_count' => $successCount,
                'imported_count' => $successCount,
                'total_rows' => $totalRows,
                'skipped_due_to_limit' => $skippedDueToLimit,
                'can_import_excel' => true,
                ...$limitContext,
            ];

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
                    ...$responseMeta,
                    'errors' => $errors,
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Успешно импортировано {$successCount} товаров",
                'count' => $successCount,
                ...$responseMeta,
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
                'errors' => $errors,
                'can_import_excel' => true,
                ...$limitContext,
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
            'image' => null,
        ];

        $priorityMap = [
            'name' => [
                'name(ru-ru)',
                'name',
                'название',
                'наименование',
                'товар',
                'product',
            ],
            'price' => [
                'price',
                'цена',
                'стоимость',
                'cost',
                'amount',
            ],
            'description' => [
                'description(ru-ru)',
                'description',
                'описание',
                'desc',
                'meta_description(ru-ru)',
            ],
            'category' => [
                'category',
                'категория',
                'categories',
                'main_category',
                'раздел',
            ],
            'in_stock' => [
                'quantity',
                'in_stock',
                'наличие',
                'stock_status_id',
                'stock_status',
            ],
            'image' => [
                'image_name',
                'image',
                'img',
                'picture',
                'фото',
                'картинка',
                'url',
            ],
        ];

        $normalizedHeaders = [];

        foreach ($headers as $index => $header) {
            $normalizedHeaders[$index] = mb_strtolower(trim((string) $header));
        }

        foreach ($priorityMap as $field => $variants) {
            foreach ($variants as $variant) {
                $variant = mb_strtolower($variant);

                foreach ($normalizedHeaders as $index => $header) {
                    if ($header === $variant) {
                        $mapping[$field] = $index;
                        break 2;
                    }
                }
            }
        }

        return $mapping;
    }
}
