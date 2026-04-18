<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Product;

use App\Models\Product;
use App\MoonShine\Resources\Shop\ShopResource;
use App\MoonShine\Resources\Category\CategoryResource;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Textarea;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;

class ProductResource extends ModelResource
{
    protected string $model = Product::class;

    protected string $title = 'Товары';

    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Магазин', 'shop', resource: ShopResource::class)->required(),
            BelongsTo::make('Категория (новая)', 'category', resource: CategoryResource::class)->nullable(),
            Text::make('Название', 'name')->required()->sortable(),
            Number::make('Цена', 'price')->step(0.01)->required()->sortable(),
            Switcher::make('В наличии', 'in_stock')->default(true)->updateOnPreview(),
            Switcher::make('В слайдере', 'show_in_slider')->default(false)->updateOnPreview(),
            Text::make('Категория (legacy)', 'category')->nullable()->hideOnDetail(),
            Text::make('Картинка URL', 'image')->nullable()->hideOnIndex(),
            Textarea::make('Описание', 'description')->nullable()->hideOnIndex(),
            Textarea::make('Атрибуты JSON', 'attributes')
                ->hideOnIndex()
                ->hideOnForm()
                ->nullable()
                ->changeFill(fn($value) => \is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $value),
            Date::make('Создан', 'created_at')->withTime()->hideOnForm(),
            Date::make('Обновлен', 'updated_at')->withTime()->hideOnForm()->hideOnIndex(),
        ];
    }

    protected function search(): array
    {
        return ['id', 'name', 'description', 'category', 'shop.name'];
    }

    protected function rules(mixed $item): array
    {
        return [
            'shop_id' => ['required', 'exists:shops,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'in_stock' => ['boolean'],
            'show_in_slider' => ['boolean'],
            'image' => ['nullable', 'url'],
            'attributes' => ['nullable'],
        ];
    }
}
