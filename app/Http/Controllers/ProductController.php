<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    private function imageFieldRules(?int $shopId = null): array
    {
        return [
            'nullable',
            'string',
            'max:2048',
            function (string $attribute, mixed $value, \Closure $fail) use ($shopId): void {
                if ($value === null || $value === '') {
                    return;
                }

                $image = (string) $value;
                if (filter_var($image, FILTER_VALIDATE_URL)) {
                    return;
                }

                if ($shopId !== null && str_starts_with($image, '/storage/products/shop-' . $shopId . '/')) {
                    return;
                }

                if ($shopId !== null && str_starts_with($image, '/demo-images/shop-' . $shopId . '/')) {
                    return;
                }

                $fail('Поле image должно быть корректным URL или путём вашего магазина: /storage/products/shop-{shopId}/... или /demo-images/shop-{shopId}/...');
            },
        ];
    }

    private function normalizeAttributes(Request $request): void
    {
        $raw = $request->input('attributes');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge(['attributes' => $decoded]);
            }
        }
    }

    private function normalizeCategoryIds(Request $request): void
    {
        $raw = $request->input('category_ids');

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $request->merge(['category_ids' => $decoded]);
            }
        }
    }

    private function handleProductImageUpload(Request $request, int $shopId, ?string $existingImage = null): ?string
    {
        if (! $request->hasFile('image_file')) {
            return null;
        }

        $path = $request->file('image_file')->store('products/shop-' . $shopId, 'public');

        if ($existingImage && str_starts_with($existingImage, '/storage/')) {
            $oldPath = ltrim(str_replace('/storage/', '', $existingImage), '/');
            if ($oldPath !== '' && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        return Storage::url($path);
    }

    /**
     * Resolve category payload for product create/update.
     *
     * Приоритет:
     * 1) category_ids (many-to-many)
     * 2) category_id (legacy primary)
     * 3) category (legacy text)
     */
    private function resolveCategoryPayload(Request $request, Shop $shop, bool $allowNoChanges = false): ?array
    {
        $hasCategoryIds = $request->has('category_ids');
        $hasCategoryId = $request->has('category_id');
        $hasCategoryText = $request->has('category');

        if (!$hasCategoryIds && !$hasCategoryId && !$hasCategoryText) {
            return $allowNoChanges ? null : ['category_id' => null, 'category' => null, 'category_ids' => []];
        }

        if ($hasCategoryIds) {
            $rawCategoryIds = $request->input('category_ids', []);
            $categoryIds = collect($rawCategoryIds)
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($categoryIds->isEmpty()) {
                return ['category_id' => null, 'category' => null, 'category_ids' => []];
            }

            $validIds = $shop->categories()
                ->whereIn('id', $categoryIds->all())
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $missing = array_values(array_diff($categoryIds->all(), $validIds));
            if (!empty($missing)) {
                throw ValidationException::withMessages([
                    'category_ids' => ['Одна или несколько категорий не принадлежат этому магазину.'],
                ]);
            }

            $firstCategory = $shop->categories()->find($validIds[0]);

            return [
                'category_id' => $firstCategory?->id,
                'category' => $firstCategory?->name,
                'category_ids' => $validIds,
            ];
        }

        if ($hasCategoryId) {
            $rawCategoryId = $request->input('category_id');
            if ($rawCategoryId === null || $rawCategoryId === '') {
                return ['category_id' => null, 'category' => null, 'category_ids' => []];
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
                'category_ids' => [$category->id],
            ];
        }

        $categoryName = trim((string) $request->input('category', ''));
        if ($categoryName === '') {
            return ['category_id' => null, 'category' => null, 'category_ids' => []];
        }

        $matchedCategory = $shop->categories()
            ->where('name', $categoryName)
            ->first();

        if ($matchedCategory) {
            return [
                'category_id' => $matchedCategory->id,
                'category' => $matchedCategory->name,
                'category_ids' => [$matchedCategory->id],
            ];
        }

        return ['category_id' => null, 'category' => $categoryName, 'category_ids' => []];
    }

    private function syncProductCategories(Product $product, ?array $categoryIds): void
    {
        if ($categoryIds === null) {
            return;
        }

        $product->categories()->sync($categoryIds);
    }

    /**
     * Получить список товаров магазина
     */
    public function index(Request $request, $shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);

        $products = $shop->products()
            ->with(['category', 'categories:id,name'])
            ->when($request->category, function ($query, $category) {
                if (is_numeric($category)) {
                    $categoryId = (int) $category;
                    return $query->where(function ($subQuery) use ($categoryId) {
                        $subQuery->where('category_id', $categoryId)
                            ->orWhereHas('categories', function ($categoriesQuery) use ($categoryId) {
                                $categoriesQuery->where('categories.id', $categoryId);
                            });
                    });
                }

                return $query->where('category', $category);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy($request->sort ?? 'created_at', $request->order ?? 'desc')
            ->paginate($request->per_page ?? 20);

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
        $this->normalizeAttributes($request);
        $this->normalizeCategoryIds($request);

        // Проверка лимита товаров по тарифу
        $productsCount = $shop->products()->count();
        $limit = $user->getProductsLimit();
        $availableSlots = max(0, $limit - $productsCount);

        if ($availableSlots <= 0) {
            return response()->json([
                'success' => false,
                'message' => "Вы достигли лимита товаров для вашего тарифа ({$limit} шт.)",
                'limit' => $limit,
                'current_count' => $productsCount,
                'available_slots' => 0,
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('shop_id', $shop->id)),
            ],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('shop_id', $shop->id)),
            ],
            'category' => 'nullable|string|max:100', // Для обратной совместимости
            'in_stock' => 'boolean',
            'show_in_slider' => 'boolean',
            'image' => $this->imageFieldRules((int) $shop->id),
            'image_file' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
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
            'name' => html_entity_decode(strip_tags((string)$request->name), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'price' => $request->price,
            'description' => $request->description ? html_entity_decode(strip_tags((string)$request->description), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
            'category' => $categoryPayload['category'],
            'category_id' => $categoryPayload['category_id'],
            'in_stock' => $request->in_stock ?? true,
            'show_in_slider' => $request->boolean('show_in_slider', false),
            'image' => $request->image,
            'attributes' => $request->attributes,
        ];

        $uploadedImageUrl = $this->handleProductImageUpload($request, (int) $shop->id);
        if ($uploadedImageUrl) {
            $data['image'] = $uploadedImageUrl;
        }

        $product = $shop->products()->create($data);
        $this->syncProductCategories($product, $categoryPayload['category_ids'] ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Товар успешно создан',
            'product' => $product->load(['category', 'categories']),
            'remaining' => $availableSlots - 1,
            'limit' => $limit,
            'current_count' => $productsCount + 1,
            'available_slots' => max(0, $availableSlots - 1),
        ], 201);
    }

    /**
     * Получить информацию о товаре
     */
    public function show($shopId, $productId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        $product = $shop->products()->with(['category', 'categories'])->findOrFail($productId);
        
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
        $this->normalizeAttributes($request);
        $this->normalizeCategoryIds($request);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('shop_id', $shop->id)),
            ],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('shop_id', $shop->id)),
            ],
            'category' => 'nullable|string|max:100',
            'in_stock' => 'boolean',
            'show_in_slider' => 'boolean',
            'image' => $this->imageFieldRules((int) $shop->id),
            'image_file' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
            'attributes' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'price', 'in_stock', 'show_in_slider', 'image', 'attributes'
        ]);

        if ($request->has('name')) {
            $data['name'] = html_entity_decode(strip_tags((string)$request->name), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if ($request->has('description')) {
            $data['description'] = $request->description ? html_entity_decode(strip_tags((string)$request->description), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
        }

        $categoryPayload = $this->resolveCategoryPayload($request, $shop, true);
        if ($categoryPayload !== null) {
            $data = array_merge($data, [
                'category' => $categoryPayload['category'],
                'category_id' => $categoryPayload['category_id'],
            ]);
        }

        $uploadedImageUrl = $this->handleProductImageUpload($request, (int) $shop->id, $product->image);
        if ($uploadedImageUrl) {
            $data['image'] = $uploadedImageUrl;
        }
        
        $product->update($data);
        $this->syncProductCategories($product, $categoryPayload['category_ids'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Товар успешно обновлен',
            'product' => $product->load(['category', 'categories'])
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
            ->with(['category', 'categories:id,name'])
            ->where('in_stock', true)
            ->when($request->category, function ($query, $category) {
                return $query->where(function ($subQuery) use ($category) {
                    $subQuery->where('category_id', $category)
                        ->orWhereHas('categories', function ($categoriesQuery) use ($category) {
                            $categoriesQuery->where('categories.id', $category);
                        });
                });
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
