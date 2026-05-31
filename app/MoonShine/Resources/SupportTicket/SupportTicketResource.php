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

class SupportTicketResource extends ModelResource
{
    protected string $model = SupportTicket::class;
    protected string $title = 'Техподдержка';

    // ПОЛЯ ДЛЯ СПИСКА
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Тема', 'subject'),
            Text::make('Email', 'user_email'),
            Date::make('Создан', 'created_at'),
        ];
    }

    // ПОЛЯ ДЛЯ ПРОСМОТРА (с формой ответа)
    protected function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Тема', 'subject'),
            Text::make('Email', 'user_email'),
            HasMany::make('История тикета', 'messages', resource: SupportTicketMessageResource::class),
            
            // Форма для ответа администратора
            Textarea::make('Ваш ответ', 'admin_reply')
                ->customAttributes(['rows' => 4, 'placeholder' => 'Введите ответ администратора...']),
        ];
    }

    // ПОЛЯ ДЛЯ ФОРМЫ (редактирование)
    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Тема', 'subject'),
            Text::make('Email', 'user_email'),
            Textarea::make('Ваш ответ', 'admin_reply')
                ->customAttributes(['rows' => 4, 'placeholder' => 'Введите ответ администратора...']),
        ];
    }
}