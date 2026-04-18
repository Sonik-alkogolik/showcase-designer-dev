<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Shop;

use App\Models\Shop;
use App\MoonShine\Resources\User\UserResource;
use App\MoonShine\Resources\Product\ProductResource;
use App\MoonShine\Resources\Order\OrderResource;
use App\MoonShine\Resources\Category\CategoryResource;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Textarea;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;

class ShopResource extends ModelResource
{
    protected string $model = Shop::class;

    protected string $title = 'Магазины';

    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Владелец', 'user', resource: UserResource::class)->required(),
            Text::make('Название', 'name')->required()->sortable(),
            Text::make('Bot token', 'bot_token')->hideOnIndex()->nullable(),
            Text::make('Notification chat ID', 'notification_chat_id')->nullable(),
            Text::make('Notification username', 'notification_username')->nullable(),
            Text::make('Название доставки', 'delivery_name')->required(),
            Number::make('Цена доставки', 'delivery_price')->step(0.01)->required(),
            Textarea::make('Webhook URL', 'webhook_url')->hideOnIndex()->nullable(),
            Date::make('Создан', 'created_at')->withTime()->hideOnForm(),
            Date::make('Обновлен', 'updated_at')->withTime()->hideOnForm()->hideOnIndex(),
            HasMany::make('Товары', 'products', resource: ProductResource::class),
            HasMany::make('Категории', 'categories', resource: CategoryResource::class),
            HasMany::make('Заказы', 'orders', resource: OrderResource::class),
        ];
    }

    protected function search(): array
    {
        return ['id', 'name', 'notification_chat_id', 'notification_username', 'user.name', 'user.email'];
    }

    protected function rules(mixed $item): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'delivery_name' => ['required', 'string', 'max:255'],
            'delivery_price' => ['required', 'numeric', 'min:0'],
            'webhook_url' => ['nullable', 'url'],
        ];
    }
}

