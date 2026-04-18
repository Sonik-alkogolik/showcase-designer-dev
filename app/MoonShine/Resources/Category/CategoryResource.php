<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Category;

use App\Models\Category;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\MoonShine\Resources\Shop\ShopResource;
use App\MoonShine\Resources\Product\ProductResource;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Textarea;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;

class CategoryResource extends ModelResource
{
    protected string $model = Category::class;

    protected string $title = 'Категории';

    protected string $column = 'name';

    protected array $with = ['shop'];

    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Магазин', 'shop', resource: ShopResource::class)->required(),
            Text::make('Название', 'name')->required()->sortable(),
            Text::make('Slug', 'slug')->required()->sortable(),
            Textarea::make('Описание', 'description')->hideOnIndex()->nullable(),
            Number::make('Сортировка', 'sort_order')->default(0)->sortable(),
            Switcher::make('Активна', 'is_active')->default(true)->updateOnPreview(),
            Date::make('Создана', 'created_at')->withTime()->hideOnForm(),
            Date::make('Обновлена', 'updated_at')->withTime()->hideOnForm()->hideOnIndex(),
            HasMany::make('Товары', 'products', resource: ProductResource::class),
        ];
    }

    protected function search(): array
    {
        return ['id', 'name', 'slug', 'shop.name'];
    }

    protected function filters(): iterable
    {
        return [
            BelongsTo::make('Магазин', 'shop', resource: ShopResource::class)
                ->valuesQuery(static fn (Builder $q) => $q->select(['id', 'name'])),
            Text::make('Название', 'name'),
        ];
    }

    protected function rules(mixed $item): array
    {
        $categoryId = \is_object($item) ? $item->id : null;

        return [
            'shop_id' => ['required', 'exists:shops,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug,' . ($categoryId ?? 'NULL') . ',id,shop_id,' . request('shop_id')],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ];
    }
}
