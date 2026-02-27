<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Получить список товаров магазина
     */
    public function index(Request $request, $shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        
        $products = $shop->products()
            ->when($request->category, function ($query, $category) {
                return $query->where('category', $category);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy($request->sort ?? 'created_at', $request->order ?? 'desc')
            ->paginate($request->per_page ?? 20);
        
        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }

    /**
     * Создать новый товар
     */
    public function store(Request $request, $shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);

        // Проверка лимита товаров по тарифу
        $productsCount = $shop->products()->count();
        $limit = $user->getProductsLimit();
        
        if ($productsCount >= $limit) {
            return response()->json([
                'success' => false,
                'message' => "Вы достигли лимита товаров для вашего тарифа ({$limit} шт.)"
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'in_stock' => 'boolean',
            'image' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product = $shop->products()->create([
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
            'category' => $request->category,
            'in_stock' => $request->in_stock ?? true,
            'image' => $request->image,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Товар успешно создан',
            'product' => $product,
            'remaining' => $limit - ($productsCount + 1)
        ], 201);
    }

    /**
     * Получить информацию о товаре
     */
    public function show($shopId, $productId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        $product = $shop->products()->findOrFail($productId);
        
        return response()->json([
            'success' => true,
            'product' => $product
        ]);
    }

    /**
     * Обновить товар
     */
    public function update(Request $request, $shopId, $productId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        $product = $shop->products()->findOrFail($productId);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'in_stock' => 'boolean',
            'image' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($request->only([
            'name', 'price', 'description', 'category', 'in_stock', 'image'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Товар успешно обновлен',
            'product' => $product
        ]);
    }

    /**
     * Удалить товар
     */
    public function destroy($shopId, $productId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        $product = $shop->products()->findOrFail($productId);
        
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Товар успешно удален'
        ]);
    }

    /**
     * Публичный каталог товаров (для Telegram Web App)
     */
    public function publicIndex(Request $request, $shopId)
    {
        $shop = Shop::findOrFail($shopId);
        
        $products = $shop->products()
            ->where('in_stock', true)
            ->when($request->category, function ($query, $category) {
                return $query->where('category', $category);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'success' => true,
            'shop' => [
                'name' => $shop->name,
                'delivery_name' => $shop->delivery_name,
                'delivery_price' => $shop->delivery_price,
            ],
            'products' => $products
        ]);
    }

        /**
     * Импорт товаров из Excel/CSV
     */
    // public function import(Request $request, $shopId)
    // {
    //     $user = Auth::user();
    //     $shop = $user->shops()->findOrFail($shopId);

    //     // Проверка лимита товаров по тарифу
    //     $productsCount = $shop->products()->count();
    //     $limit = $user->getProductsLimit();
        
    //     if ($productsCount >= $limit) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => "Вы достигли лимита товаров для вашего тарифа ({$limit} шт.)"
    //         ], 403);
    //     }

    //     $request->validate([
    //         'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
    //     ]);

    //     try {
    //         $import = new \App\Imports\ProductsImport($shopId);
    //         $import->import($request->file('file'));

    //         $failures = $import->getFailures();
    //         $successCount = $import->getRowCount() - count($failures);

    //         if (count($failures) > 0) {
    //             $errors = [];
    //             foreach ($failures as $failure) {
    //                 $errors[] = [
    //                     'row' => $failure->row(),
    //                     'attribute' => $failure->attribute(),
    //                     'errors' => $failure->errors(),
    //                     'values' => $failure->values(),
    //                 ];
    //             }

    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Импорт завершен с ошибками',
    //                 'success_count' => $successCount,
    //                 'errors' => $errors
    //             ], 422);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => "Успешно импортировано {$successCount} товаров",
    //             'count' => $successCount
    //         ]);

    //     } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
    //         $failures = $e->failures();
    //         $errors = [];
    //         foreach ($failures as $failure) {
    //             $errors[] = [
    //                 'row' => $failure->row(),
    //                 'attribute' => $failure->attribute(),
    //                 'errors' => $failure->errors(),
    //             ];
    //         }

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Ошибка валидации файла',
    //             'errors' => $errors
    //         ], 422);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Ошибка при импорте: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
}