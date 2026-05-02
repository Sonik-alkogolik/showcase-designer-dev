<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SubscriptionPayment\Pages;

use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\File;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Textarea;

class SubscriptionPaymentFormPage extends FormPage
{
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make()->sortable(),
                BelongsTo::make('Пользователь', 'user', resource: \App\MoonShine\Resources\User\UserResource::class)
                    ->valuesQuery(static fn ($q) => $q->select(['id', 'name', 'email']))
                    ->required(),
                BelongsTo::make('Подписка', 'subscription', resource: \App\MoonShine\Resources\Subscription\SubscriptionResource::class)
                    ->valuesQuery(static fn ($q) => $q->select(['id', 'user_id', 'plan', 'status']))
                    ->required(),
                Date::make('Оплаченный месяц', 'paid_for_month')
                    ->required(),
                Date::make('Дата оплаты', 'payment_received_at')
                    ->withTime()
                    ->required(),
                Number::make('Сумма', 'amount')
                    ->step(0.01)
                    ->required(),
                Select::make('Метод оплаты', 'payment_method')
                    ->options([
                        'manual' => 'Вручную',
                        'yookassa' => 'ЮKassa',
                        'test' => 'Тестовый',
                    ])
                    ->nullable(),
                File::make('Чек (img/pdf)', 'receipt_file')
                    ->disk(moonshineConfig()->getDisk())
                    ->dir('subscription-receipts')
                    ->allowedExtensions(['jpg', 'jpeg', 'png', 'webp', 'pdf'])
                    ->nullable(),
                Textarea::make('Комментарий', 'note')
                    ->nullable(),
            ]),
        ];
    }
}
