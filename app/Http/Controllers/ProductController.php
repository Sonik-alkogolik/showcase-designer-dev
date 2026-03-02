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
        ->with('category')
        ->when($request->category, function ($query, $category) {
            // Проверяем, является ли category числом (ID) или строкой (название)
            if (is_numeric($category)) {
                return $query->where('category_id', $category);
            } else {
                // Для обратной совместимости ищем по названию
                return $query->where('category', $category);
            }
        })
        ->when($request->search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%");
        })
        ->orderBy($request->sort ?? 'created_at', $request->order ?? 'desc')
        ->paginate($request->per_page ?? 20);
    
    // Получаем категории из таблицы categories
    $categories = $shop->categories()
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get(['id', 'name']);
    
    return response()->json([
        'success' => true,
        'products' => $products,
        'categories' => $categories
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
            'category_id' => 'nullable|exists:categories,id',
            'category' => 'nullable|string|max:100', // Для обратной совместимости
            'in_stock' => 'boolean',
            'image' => 'nullable|url',
            'attributes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = [
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
            'category' => $request->category, // Временно сохраняем
            'in_stock' => $request->in_stock ?? true,
            'image' => $request->image,
            'attributes' => $request->attributes,
        ];

        // Если передан category_id, используем его
        if ($request->has('category_id')) {
            $data['category_id'] = $request->category_id;
            
            // Также сохраняем название категории для обратной совместимости
            if (!$request->has('category') && $request->category_id) {
                $category = \App\Models\Category::find($request->category_id);
                $data['category'] = $category ? $category->name : null;
            }
        }

        $product = $shop->products()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Товар успешно создан',
            'product' => $product->load('category'),
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
        $product = $shop->products()->with('category')->findOrFail($productId);
        
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
            'category_id' => 'nullable|exists:categories,id',
            'category' => 'nullable|string|max:100',
            'in_stock' => 'boolean',
            'image' => 'nullable|url',
            'attributes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'name', 'price', 'description', 'in_stock', 'image', 'attributes'
        ]);

        // Обработка категории
        if ($request->has('category_id')) {
            $data['category_id'] = $request->category_id;
            
            // Обновляем текстовое поле category для обратной совместимости
            if ($request->category_id) {
                $category = \App\Models\Category::find($request->category_id);
                $data['category'] = $category ? $category->name : null;
            } else {
                $data['category'] = null;
            }
        } elseif ($request->has('category')) {
            $data['category'] = $request->category;
            
            // Пытаемся найти соответствующую категорию
            if ($request->category) {
                $category = $shop->categories()
                    ->where('name', $request->category)
                    ->first();
                if ($category) {
                    $data['category_id'] = $category->id;
                }
            } else {
                $data['category_id'] = null;
            }
        }
        
        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Товар успешно обновлен',
            'product' => $product->load('category')
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
            ->with('category')
            ->where('in_stock', true)
            ->when($request->category, function ($query, $category) {
                return $query->where('category_id', $category);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();
        
        // Получаем активные категории для фильтрации
        $categories = $shop->categories()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return response()->json([
            'success' => true,
            'shop' => [
                'name' => $shop->name,
                'delivery_name' => $shop->delivery_name,
                'delivery_price' => $shop->delivery_price,
            ],
            'products' => $products,
            'categories' => $categories
        ]);
    }
}
