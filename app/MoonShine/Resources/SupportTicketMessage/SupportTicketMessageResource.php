<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SupportTicketMessage;

use App\Models\SupportTicketMessage;
use App\MoonShine\Resources\SupportTicket\SupportTicketResource;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

#[\MoonShine\MenuManager\Attributes\SkipMenu]
class SupportTicketMessageResource extends ModelResource
{
    protected string $model = SupportTicketMessage::class;

    protected string $title = 'История тикетов';

    protected string $column = 'body';

    protected array $with = ['ticket', 'user'];

    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Тикет', 'ticket', resource: SupportTicketResource::class)->required(),
            BelongsTo::make('Пользователь', 'user', resource: UserResource::class)->nullable(),
            Select::make('Отправитель', 'sender_type')->options([
                'user' => 'Пользователь',
                'admin' => 'Администратор',
                'system' => 'Система',
            ])->required(),
            Text::make('Имя', 'sender_name')->nullable(),
            Textarea::make('Сообщение', 'body')->required(),
            Date::make('Создано', 'created_at')->withTime()->sortable()->hideOnForm(),
        ];
    }

    protected function indexFields(): array
    {
        return [
            ID::make()->sortable(),
            Text::make('Имя', 'sender_name'),
            Select::make('Отправитель', 'sender_type')->options([
                'user' => 'Пользователь',
                'admin' => 'Администратор',
                'system' => 'Система',
            ]),
            Text::make('Сообщение', 'body'),
            Date::make('Создано', 'created_at')->withTime()->sortable(),
        ];
    }

    protected function rules(mixed $item): array
    {
        return [
            'support_ticket_id' => ['required', 'exists:support_tickets,id'],
            'sender_type' => ['required', 'in:user,admin,system'],
            'body' => ['required', 'string'],
        ];
    }
}
