<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Subscription\Pages;

use App\Models\User;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Components\Layout\Box;

class SubscriptionFormPage extends FormPage
{
    /**
     * @return array<int|string, string>
     */
    private function userOptions(): array
    {
        return User::query()
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'telegram_username'])
            ->mapWithKeys(function (User $user) {
                $parts = [
                    "ID {$user->id}",
                    $user->name ?: 'без имени',
                    $user->email ?: 'без email',
                ];

                if (!empty($user->telegram_username)) {
                    $parts[] = '@' . ltrim((string) $user->telegram_username, '@');
                }

                return [$user->id => implode(' | ', $parts)];
            })
            ->all();
    }

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        $baseFields = [
            ID::make()->sortable(),
        ];

        $baseFields[] = Select::make('Пользователь', 'user_id')
            ->options($this->userOptions())
            ->required();

        $baseFields = array_merge($baseFields, [
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
                ->withTime(),

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

            Text::make('ID платежа YooKassa', 'yookassa_payment_id')
                ->nullable(),
        ]);

        return [
            Box::make($baseFields),
        ];
    }
}
