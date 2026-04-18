<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Получить список категорий магазина
     */
    public function index($shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        
        $categories = $shop->categories()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Создать новую категорию
     */
    public function store(Request $request, $shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Генерируем slug из названия
        $slug = Str::slug($request->name);
        
        // Проверяем уникальность slug для этого магазина
        $originalSlug = $slug;
        $counter = 1;
        while (Category::where('shop_id', $shop->id)->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $category = $shop->categories()->create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Категория успешно создана',
            'category' => $category
        ], 201);
    }

    /**
     * Получить информацию о категории
     */
    public function show($shopId, $categoryId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        $category = $shop->categories()->findOrFail($categoryId);
        
        return response()->json([
            'success' => true,
            'category' => $category
        ]);
    }

    /**
     * Обновить категорию
     */
    public function update(Request $request, $shopId, $categoryId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        $category = $shop->categories()->findOrFail($categoryId);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'description', 'sort_order', 'is_active']);
        
        // Если меняется название, обновляем slug
        if ($request->has('name') && $request->name !== $category->name) {
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;
            while (Category::where('shop_id', $shop->id)->where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $data['slug'] = $slug;
        }
        
        $category->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Категория успешно обновлена',
            'category' => $category
        ]);
    }

    /**
     * Удалить категорию
     */
    public function destroy($shopId, $categoryId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);
        $category = $shop->categories()->findOrFail($categoryId);
        
        // У товаров этой категории сбрасываем primary category_id
        $category->products()->update(['category_id' => null]);
        // Убираем связь в many-to-many
        $category->productsMany()->detach();
        
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Категория успешно удалена'
        ]);
    }

    /**
     * Массовое обновление порядка сортировки
     */
    public function reorder(Request $request, $shopId)
    {
        $user = Auth::user();
        $shop = $user->shops()->findOrFail($shopId);

        $validator = Validator::make($request->all(), [
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->categories as $item) {
            Category::where('id', $item['id'])
                ->where('shop_id', $shop->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Порядок категорий обновлен'
        ]);
    }
}
