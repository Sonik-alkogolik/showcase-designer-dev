<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SubscriptionPayment;

use App\Models\SubscriptionPayment;
use App\MoonShine\Resources\SubscriptionPayment\Pages\SubscriptionPaymentDetailPage;
use App\MoonShine\Resources\SubscriptionPayment\Pages\SubscriptionPaymentFormPage;
use App\MoonShine\Resources\SubscriptionPayment\Pages\SubscriptionPaymentIndexPage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\File;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Text;

class SubscriptionPaymentResource extends ModelResource
{
    protected string $model = SubscriptionPayment::class;

    protected string $title = 'Оплаты';

    protected string $column = 'id';

    protected array $with = ['user', 'subscription'];

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            SubscriptionPaymentIndexPage::class,
            SubscriptionPaymentFormPage::class,
            SubscriptionPaymentDetailPage::class,
        ];
    }

    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Пользователь', 'user', resource: \App\MoonShine\Resources\User\UserResource::class)
                ->required(),
            BelongsTo::make('Подписка', 'subscription', resource: \App\MoonShine\Resources\Subscription\SubscriptionResource::class)
                ->required(),
            Date::make('Оплаченный месяц', 'paid_for_month')
                ->format('m.Y')
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
            Date::make('Создано', 'created_at')->withTime()->hideOnForm(),
        ];
    }

    protected function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Пользователь', 'user', resource: \App\MoonShine\Resources\User\UserResource::class),
            Date::make('Месяц', 'paid_for_month')->format('m.Y')->sortable(),
            Date::make('Оплачено', 'payment_received_at')->withTime()->sortable(),
            Number::make('Сумма', 'amount')->sortable(),
            Text::make('Метод', 'payment_method'),
            File::make('Чек', 'receipt_file'),
        ];
    }

    protected function search(): array
    {
        return ['id', 'note', 'payment_method', 'user.name', 'user.email'];
    }

    protected function filters(): iterable
    {
        return [
            BelongsTo::make('Пользователь', 'user', resource: \App\MoonShine\Resources\User\UserResource::class)
                ->valuesQuery(static fn (Builder $q) => $q->select(['id', 'name', 'email'])),
            Date::make('Месяц', 'paid_for_month'),
            Select::make('Метод оплаты', 'payment_method')
                ->options([
                    'manual' => 'Вручную',
                    'yookassa' => 'ЮKassa',
                    'test' => 'Тестовый',
                ])
                ->nullable(),
        ];
    }

    protected function rules(mixed $item): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'subscription_id' => ['required', 'exists:subscriptions,id'],
            'paid_for_month' => ['required', 'date'],
            'payment_received_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'receipt_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
