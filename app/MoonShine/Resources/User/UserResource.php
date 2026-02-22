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
use MoonShine\UI\Fields\DateTime;
use MoonShine\UI\Fields\Password;
use MoonShine\Laravel\Fields\Relationships\HasMany;

class UserResource extends ModelResource
{
    protected string $model = User::class;

    protected string $title = 'Пользователи';
    
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
            Password::make('Пароль', 'password')
                ->hideOnIndex()
                ->hideOnDetail()
                ->when(
                    fn($field) => request()->routeIs('*.form-page') && !request()->route('resourceItem'),
                    fn($field) => $field->required()
                ),
            DateTime::make('Создан', 'created_at')->format('d.m.Y H:i')->hideOnForm(),
            HasMany::make('Подписки', 'subscriptions', resource: \App\MoonShine\Resources\Subscription\SubscriptionResource::class),
        ];
    }

    protected function indexFields(): array
    {
        dd('Метод indexFields вызван', [
            'fields' => [
                ID::make()->sortable(),
                Text::make('Имя', 'name')->sortable(),
                Email::make('Email', 'email')->sortable(),
                DateTime::make('Создан', 'created_at')->format('d.m.Y')->sortable(),
            ]
        ]);
        
        return [
            ID::make()->sortable(),
            Text::make('Имя', 'name')->sortable(),
            Email::make('Email', 'email')->sortable(),
            DateTime::make('Создан', 'created_at')->format('d.m.Y')->sortable(),
        ];
    }
}