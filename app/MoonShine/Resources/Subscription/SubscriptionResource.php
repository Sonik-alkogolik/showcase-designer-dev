<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Subscription;

use Illuminate\Database\Eloquent\Model;
use App\Models\Subscription;
use App\MoonShine\Resources\Subscription\Pages\SubscriptionIndexPage;
use App\MoonShine\Resources\Subscription\Pages\SubscriptionFormPage;
use App\MoonShine\Resources\Subscription\Pages\SubscriptionDetailPage;

use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\File;
use MoonShine\UI\Fields\Textarea;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;

/**
 * @extends ModelResource<Subscription, SubscriptionIndexPage, SubscriptionFormPage, SubscriptionDetailPage>
 */
class SubscriptionResource extends ModelResource
{
    protected string $model = Subscription::class;

    protected string $title = 'Подписки';
    
    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            SubscriptionIndexPage::class,
            SubscriptionFormPage::class,
            SubscriptionDetailPage::class,
        ];
    }

    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            
            BelongsTo::make(
                'Пользователь',
                'user',
                resource: \App\MoonShine\Resources\User\UserResource::class,
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
                ->withTime(),
            
            Switcher::make('Автопродление', 'auto_renew')
                ->default(false),
            
            Number::make('Цена', 'price')
                ->step(0.01)
                ->precision(2)
                ->required(),
            
            Text::make('Метод оплаты', 'payment_method')
                ->hideOnIndex(),
            
            Text::make('ID платежа YooKassa', 'yookassa_payment_id')
                ->hideOnIndex(),

            Date::make('Дата оплаты', 'payment_received_at')
                ->withTime()
                ->nullable()
                ->hideOnIndex(),

            File::make('Чек оплаты', 'payment_receipt_file')
                ->disk(moonshineConfig()->getDisk())
                ->dir('subscription-receipts')
                ->allowedExtensions(['jpg', 'jpeg', 'png', 'webp', 'pdf'])
                ->hideOnIndex(),

            Textarea::make('Комментарий по оплате', 'payment_note')
                ->nullable()
                ->hideOnIndex(),

            Date::make('Создано', 'created_at')->withTime()->hideOnForm()->hideOnIndex(),
            Date::make('Обновлено', 'updated_at')->withTime()->hideOnForm()->hideOnIndex(),
        ];
    }

    protected function search(): array
    {
        return [
            'id',
            'user.name',
            'user.email',
            'plan',
            'status',
        ];
    }

    protected function filters(): iterable
    {
        return [
            Select::make('Тариф', 'plan')
                ->options([
                    'starter' => 'Бесплатный',
                    'business' => 'Платный',
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

    protected function rules(mixed $item): array
    {
        $isEdit = $item instanceof Subscription && $item->exists;

        return [
            'user_id' => $isEdit ? 'nullable|exists:users,id' : 'required|exists:users,id',
            'plan' => 'required|in:starter,business',
            'status' => 'required|in:active,expired,cancelled',
            'expires_at' => 'required|date',
            'auto_renew' => 'boolean',
            'price' => 'required|numeric|min:0',
            'payment_received_at' => 'nullable|date',
            'payment_receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:8192',
            'payment_note' => 'nullable|string|max:2000',
        ];
    }
    protected function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Пользователь', 'user', resource: \App\MoonShine\Resources\User\UserResource::class),
            Select::make('Тариф', 'plan')
                ->options([
                    'starter' => 'Бесплатный',
                    'business' => 'Платный',
                ]),
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
            Date::make('Действует до', 'expires_at')->withTime(),
            Switcher::make('Автопродление', 'auto_renew'),
            Number::make('Цена', 'price')->sortable(),
        ];
    }
}
