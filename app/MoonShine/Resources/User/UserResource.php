<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\User;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\MoonShine\Resources\User\Pages\UserIndexPage;
use App\MoonShine\Resources\User\Pages\UserFormPage;
use App\MoonShine\Resources\User\Pages\UserDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Password;
use MoonShine\Laravel\Fields\Relationships\HasMany;

class UserResource extends ModelResource
{
    protected string $model = User::class;

    protected string $title = 'Пользователи';

    protected string $column = 'name';
    
    protected function pages(): array
    {
        return [
            UserIndexPage::class,
            UserFormPage::class,
            UserDetailPage::class,
        ];
    }

    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Имя', 'name')->required(),
            Email::make('Email', 'email')->required(),
            Text::make('Telegram ID', 'telegram_id')->sortable()->nullable(),
            Text::make('Telegram username', 'telegram_username')->nullable(),
            Text::make('Telegram avatar URL', 'telegram_avatar_url')->hideOnIndex()->nullable(),
            Date::make('Telegram linked at', 'telegram_linked_at')->withTime()->hideOnIndex()->hideOnForm(),
            Password::make('Пароль', 'password')
                ->hideOnIndex()
                ->hideOnDetail()
                ->when(
                    fn($field) => request()->routeIs('*.form-page') && !request()->route('resourceItem'),
                    fn($field) => $field->required()
                ),
            Date::make('Email подтвержден', 'email_verified_at')->withTime()->hideOnIndex(),
            Date::make('Создан', 'created_at')->withTime()->hideOnForm(),
            Date::make('Обновлен', 'updated_at')->withTime()->hideOnForm()->hideOnIndex(),
            HasMany::make('Подписки', 'subscriptions', resource: \App\MoonShine\Resources\Subscription\SubscriptionResource::class),
            HasMany::make('Оплаты', 'subscriptionPayments', resource: \App\MoonShine\Resources\SubscriptionPayment\SubscriptionPaymentResource::class),
            HasMany::make('Магазины', 'shops', resource: \App\MoonShine\Resources\Shop\ShopResource::class),
        ];
    }

    protected function search(): array
    {
        return ['id', 'name', 'email', 'telegram_id', 'telegram_username'];
    }
}
