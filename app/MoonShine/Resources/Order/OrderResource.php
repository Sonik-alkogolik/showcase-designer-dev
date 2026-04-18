<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Order;

use App\Models\Order;
use App\MoonShine\Resources\Shop\ShopResource;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Textarea;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;

class OrderResource extends ModelResource
{
    protected string $model = Order::class;

    protected string $title = 'Заказы';

    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Магазин', 'shop', resource: ShopResource::class)->required(),
            Text::make('Клиент', 'customer_name')->required()->sortable(),
            Text::make('Телефон', 'phone')->required(),
            Number::make('Сумма', 'total')->required()->step(0.01)->sortable(),
            Text::make('Доставка', 'delivery_name')->required(),
            Number::make('Цена доставки', 'delivery_price')->required()->step(0.01),
            Select::make('Статус', 'status')
                ->options([
                    'pending' => 'Ожидает',
                    'paid' => 'Оплачен',
                    'cancelled' => 'Отменен',
                ])
                ->default('pending')
                ->required(),
            Text::make('YooKassa Payment ID', 'yookassa_payment_id')->nullable(),
            Textarea::make('Состав заказа (JSON)', 'items')
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
        return ['id', 'customer_name', 'phone', 'status', 'yookassa_payment_id', 'shop.name'];
    }

    protected function filters(): iterable
    {
        return [
            Select::make('Статус', 'status')->options([
                'pending' => 'Ожидает',
                'paid' => 'Оплачен',
                'cancelled' => 'Отменен',
            ])->nullable(),
        ];
    }

    protected function rules(mixed $item): array
    {
        return [
            'shop_id' => ['required', 'exists:shops,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'total' => ['required', 'numeric', 'min:0'],
            'delivery_name' => ['required', 'string', 'max:255'],
            'delivery_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:pending,paid,cancelled'],
        ];
    }
}
