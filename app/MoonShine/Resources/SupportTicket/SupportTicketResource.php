<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SupportTicket;

use App\Models\SupportTicket;
use App\MoonShine\Resources\SupportTicketMessage\SupportTicketMessageResource;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Textarea;
use MoonShine\UI\Fields\Select;

class SupportTicketResource extends ModelResource
{
    protected string $model = SupportTicket::class;
    protected string $title = 'Техподдержка';
    protected string $column = 'subject';
    protected array $with = ['user', 'messages'];

    private function replyFields(): array
    {
        return [
            Textarea::make('Ваш ответ', 'admin_response')
                ->required()
                ->customAttributes([
                    'rows' => 6,
                    'placeholder' => 'Введите ответ администратора...',
                ]),
        ];
    }

    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Тема', 'subject'),
            Text::make('Email', 'user_email'),
            Date::make('Создан', 'created_at'),
        ];
    }

    protected function fields(): iterable
    {
        return $this->replyFields();
    }

    protected function formFields(): iterable
    {
        return $this->replyFields();
    }

    protected function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Тема', 'subject'),
            Text::make('Email', 'user_email'),
            Select::make('Категория', 'category')->options(SupportTicket::CATEGORIES),
            Select::make('Статус', 'status')->options(SupportTicket::STATUSES),
            Text::make('URL', 'current_url'),
            Text::make('Браузер', 'browser'),
            Textarea::make('Первое сообщение', 'message'),
            HasMany::make('История тикета', 'messages', resource: SupportTicketMessageResource::class),
            Date::make('Создан', 'created_at')->withTime()->sortable(),
            Date::make('Последний ответ админа', 'last_admin_replied_at')->withTime()->nullable(),
        ];
    }

    protected function rules(mixed $item): array
    {
        return [
            'admin_response' => ['required', 'string', 'max:5000'],
        ];
    }
}
