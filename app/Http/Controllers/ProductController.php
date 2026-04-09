<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    /**
     * Resolve category payload for product create/update.
     *
     * Rules:
     * - category_id has priority over category text
     * - category_id must belong to current shop
     * - category text is mapped to existing shop category by name (if found)
     * - if category text does not match existing category, keep text for backward compatibility
     *
     * Returns null when no category fields were provided and $allowNoChanges is true.
     */
    private function resolveCategoryPayload(Request $request, Shop $shop, bool $allowNoChanges = false): ?array
    {
        $hasCategoryId = $request->has('category_id');
        $hasCategoryText = $request->has('category');

        if (!$hasCategoryId && !$hasCategoryText) {
            return $allowNoChanges ? null : ['category_id' => null, 'category' => null];
        }

        if ($hasCategoryId) {
            $rawCategoryId = $request->input('category_id');
            if ($rawCategoryId === null || $rawCategoryId === '') {
                return ['category_id' => null, 'category' => null];
            }

            $category = $shop->categories()->find($rawCategoryId);
            if (!$category) {
                throw ValidationException::withMessages([
                    'category_id' => ['Выбранная категория не принадлежит этому магазину.'],
                ]);
            }

            return [
                'category_id' => $category->id,
                'category' => $category->name,
            ];
        }

        $categoryName = trim((string) $request->input('category', ''));
        if ($categoryName === '') {
            return ['category_id' => null, 'category' => null];
        }

        $matchedCategory = $shop->categories()
            ->where('name', $categoryName)
            ->first();

        if ($matchedCategory) {
            return [
                'category_id' => $matchedCategory->id,
                'category' => $matchedCategory->name,
            ];
        }

        return ['category_id' => null, 'category' => $categoryName];
    }

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
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('shop_id', $shop->id)),
            ],
            'category' => 'nullable|string|max:100', // Для обратной совместимости
            'in_stock' => 'boolean',
            'show_in_slider' => 'boolean',
            'image' => 'nullable|url',
            'attributes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $categoryPayload = $this->resolveCategoryPayload($request, $shop);

        $data = [
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
            'category' => $categoryPayload['category'],
            'category_id' => $categoryPayload['category_id'],
            'in_stock' => $request->in_stock ?? true,
            'show_in_slider' => $request->boolean('show_in_slider', false),
            'image' => $request->image,
            'attributes' => $request->attributes,
        ];

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
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('shop_id', $shop->id)),
            ],
            'category' => 'nullable|string|max:100',
            'in_stock' => 'boolean',
            'show_in_slider' => 'boolean',
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
            'name', 'price', 'description', 'in_stock', 'show_in_slider', 'image', 'attributes'
        ]);

        $categoryPayload = $this->resolveCategoryPayload($request, $shop, true);
        if ($categoryPayload !== null) {
            $data = array_merge($data, $categoryPayload);
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
