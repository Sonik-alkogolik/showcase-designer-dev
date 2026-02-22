<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Subscription\Pages;

use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Switcher;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use App\MoonShine\Resources\User\UserResource;


class SubscriptionIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            
            BelongsTo::make('Пользователь', 'user', resource: \App\MoonShine\Resources\User\UserResource::class)->required(),
            
            Select::make('Тариф', 'plan')
                ->options([
                    'starter' => 'Starter',
                    'business' => 'Business',
                    'premium' => 'Premium',
                ])
                ->badge(fn($value) => match($value) {
                    'starter' => 'info',
                    'business' => 'warning',
                    'premium' => 'success',
                    default => 'default',
                }),
            
            Select::make('Статус', 'status')
                ->options([
                    'active' => 'Активна',
                    'expired' => 'Истекла',
                    'cancelled' => 'Отменена',
                ])
                ->badge(fn($value) => match($value) {
                    'active' => 'success',
                    'expired' => 'default',
                    'cancelled' => 'error',
                }),
                Date::make('Действует до', 'expires_at')
                ->format('d.m.Y')
                ->sortable(),
            
            Switcher::make('Автопродление', 'auto_renew')
                ->updateOnPreview(),
            
                Number::make('Цена', 'price')
                ->sortable(),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            Select::make('Тариф', 'plan')
                ->options([
                    'starter' => 'Starter',
                    'business' => 'Business',
                    'premium' => 'Premium',
                ])
                ->nullable(),
            
            Select::make('Статус', 'status')
                ->options([
                    'active' => 'Активна',
                    'expired' => 'Истекла',
                    'cancelled' => 'Отменена',
                ])
                ->nullable(),
        ];
    }
}