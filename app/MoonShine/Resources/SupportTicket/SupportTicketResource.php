<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SupportTicket;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage; // Добавь эту модель
use App\MoonShine\Resources\SupportTicketMessage\SupportTicketMessageResource;
use App\MoonShine\Resources\User\UserResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\Laravel\Actions\Action; // <-- Импортируем Action
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Fields\Textarea as FormTextarea;
use MoonShine\Laravel\Http\Requests\MoonShineFormRequest;
use Illuminate\Support\Facades\DB;

class SupportTicketResource extends ModelResource
{
    protected string $model = SupportTicket::class;

    protected string $title = 'Техподдержка';

    protected string $column = 'subject';

    protected array $with = ['user', 'messages'];

    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Пользователь', 'user', resource: UserResource::class)->nullable(),
            Text::make('Email', 'user_email')->nullable()->sortable(),
            Select::make('Категория', 'category')->options(SupportTicket::CATEGORIES)->required(),
            Text::make('Готовая тема', 'preset')->nullable(),
            Text::make('Тема', 'subject')->required()->sortable(),
            Select::make('Статус', 'status')->options(SupportTicket::STATUSES)->default('open')->required(),
            Textarea::make('Первое сообщение', 'message')->hideOnIndex()->required(),
            // Удаляем поле 'admin_response' - оно не нужно
            // Textarea::make('Новый ответ администратора', 'admin_response')... 
            Text::make('Текущий URL', 'current_url')->hideOnIndex()->nullable(),
            Text::make('Браузер', 'browser')->hideOnIndex()->nullable(),
            Text::make('Скриншот', 'screenshot_path')
                ->hideOnIndex()
                ->nullable(),
            HasMany::make('История тикета', 'messages', resource: SupportTicketMessageResource::class),
            Date::make('Создан', 'created_at')->withTime()->sortable()->hideOnForm(),
            Date::make('Ответ админа', 'last_admin_replied_at')->withTime()->hideOnForm()->nullable(),
        ];
    }

    protected function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Тема', 'subject')->sortable(),
            Text::make('Email', 'user_email'),
            Select::make('Категория', 'category')->options(SupportTicket::CATEGORIES),
            Select::make('Статус', 'status')->options(SupportTicket::STATUSES),
            Date::make('Создан', 'created_at')->withTime()->sortable(),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Select::make('Статус', 'status')->options(SupportTicket::STATUSES)->nullable(),
            Select::make('Категория', 'category')->options(SupportTicket::CATEGORIES)->nullable(),
            BelongsTo::make('Пользователь', 'user', resource: UserResource::class)
                ->valuesQuery(static fn (Builder $q) => $q->select(['id', 'name', 'email'])),
        ];
    }

    protected function search(): array
    {
        return ['id', 'subject', 'message', 'user_email', 'current_url', 'user.email'];
    }

    protected function rules(mixed $item): array
    {
        return [
            'category' => ['required', 'in:' . implode(',', array_keys(SupportTicket::CATEGORIES))],
            'subject' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:' . implode(',', array_keys(SupportTicket::STATUSES))],
            'message' => ['required', 'string'],
            // Правило для admin_response больше не нужно
        ];
    }

  
    protected function actions(): array
        {
            return [
                Action::make('Ответить')
                    ->icon('heroicons.outline.chat-bubble-left-right')
                    ->method('replyToTicket')
                    ->form(fn() => [
                        Textarea::make('Текст ответа', 'admin_reply')
                            ->required()
                            ->rows(5)
                            ->placeholder('Введите ваш ответ...'),
                    ])
                    ->successToast('Ответ отправлен!'),
            ];
        }

 
    public function replyToTicket(MoonShineFormRequest $request, SupportTicket $item): void
{
    $data = $request->validate([
        'admin_reply' => 'required|string|min:1',
    ]);

    DB::transaction(function () use ($data, $item) {
        // Используем существующую связь messages()
        $item->messages()->create([
            'user_id' => $request->user()?->id,
            'body' => $data['admin_reply'],
            'sender_type' => 'admin',
            'sender_name' => 'Администратор',
        ]);

        $item->update([
            'last_admin_replied_at' => now(),
            'status' => 'in_progress', // Замените на свою логику статуса
        ]);
    });
}

protected function detailFields(): iterable
{
    return [
        ID::make()->sortable(),
        BelongsTo::make('Пользователь', 'user', resource: UserResource::class)->nullable(),
        Text::make('Email', 'user_email')->nullable(),
        Text::make('Тема', 'subject'),
        Select::make('Категория', 'category')->options(SupportTicket::CATEGORIES),
        Select::make('Статус', 'status')->options(SupportTicket::STATUSES),
        Textarea::make('Первое сообщение', 'message'),
        HasMany::make('История тикета', 'messages', resource: SupportTicketMessageResource::class),
        Date::make('Создан', 'created_at')->withTime(),
        Date::make('Ответ админа', 'last_admin_replied_at')->withTime()->nullable(),
    ];
}

}