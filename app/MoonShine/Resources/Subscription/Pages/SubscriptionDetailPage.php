<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Subscription\Pages;

use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\Contracts\UI\FieldContract;
use App\MoonShine\Resources\Subscription\SubscriptionResource;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Switcher;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use Throwable;


/**
 * @extends DetailPage<SubscriptionResource>
 */
class SubscriptionDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            BelongsTo::make('Пользователь', 'user', resource: \App\MoonShine\Resources\User\UserResource::class),
            Select::make('Тариф', 'plan')->options([
                'starter' => 'Бесплатный',
                'business' => 'Платный',
                'premium' => 'Премиум',
            ]),
            Select::make('Статус', 'status')->options([
                'active' => 'Активна',
                'expired' => 'Истекла',
                'cancelled' => 'Отменена',
            ]),
            Date::make('Действует до', 'expires_at')->withTime(),
            Switcher::make('Автопродление', 'auto_renew'),
            Number::make('Цена', 'price'),
            Text::make('Метод оплаты', 'payment_method'),
            Text::make('ID платежа YooKassa', 'yookassa_payment_id'),
            Date::make('Создано', 'created_at')->withTime(),
            Date::make('Обновлено', 'updated_at')->withTime(),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @param  TableBuilder  $component
     *
     * @return TableBuilder
     */
    protected function modifyDetailComponent(ComponentContract $component): ComponentContract
    {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer()
        ];
    }

    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer()
        ];
    }
}
