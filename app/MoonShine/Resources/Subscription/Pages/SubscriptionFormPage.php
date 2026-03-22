<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Subscription\Pages;

use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Switcher;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\UI\Components\Layout\Box;

class SubscriptionFormPage extends FormPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make()->sortable(),
                
                BelongsTo::make(
                    'Пользователь',
                    'user',
                    resource: UserResource::class,
                )->required(),
                
                Select::make('Тариф', 'plan')
                    ->options([
                        'starter' => 'Бесплатный (0 ₽/мес)',
                        'business' => 'Платный (500 ₽/мес)',
                    ])
                    ->required(),
                
                Select::make('Статус', 'status')
                    ->options([
                        'active' => 'Активна',
                        'expired' => 'Истекла',
                        'cancelled' => 'Отменена',
                    ])
                    ->default('active')
                    ->required(),
                
                Date::make('Действует до', 'expires_at')
                    ->required()
                    ->format('d.m.Y'),
                
                Switcher::make('Автопродление', 'auto_renew')
                    ->default(false),
                
                Number::make('Цена', 'price')
                    ->step(0.01)
                    ->required(),
                
                Select::make('Метод оплаты', 'payment_method')
                    ->options([
                        'yookassa' => 'ЮKassa',
                        'manual' => 'Вручную',
                        'test' => 'Тестовый',
                    ])
                    ->nullable(),
                
                Select::make('ID платежа YooKassa', 'yookassa_payment_id')
                    ->nullable(),
            ]),
        ];
    }
}
