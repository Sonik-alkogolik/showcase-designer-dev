<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\MoonShine\Resources\UserResource;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use MoonShine\Laravel\Actions\Action;
use MoonShine\UI\Fields\Textarea as ActionTextarea;
use MoonShine\Laravel\Http\Requests\MoonShineFormRequest;
use Illuminate\Support\Facades\DB;

class SupportTicketManager extends ModelResource
{
    protected string $model = SupportTicket::class;
    protected string $title = 'Управление тикетами';

    // Поля для списка
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Тема', 'subject'),
            Text::make('Email', 'user_email'),
            Select::make('Статус', 'status')->options(SupportTicket::STATUSES),
            Date::make('Создан', 'created_at'),
        ];
    }

    // Поля для формы (создание/редактирование)
    protected function fields(): iterable
    {
        return [
            ID::make(),
            BelongsTo::make('Пользователь', 'user', resource: UserResource::class),
            Text::make('Email', 'user_email'),
            Select::make('Категория', 'category')->options(SupportTicket::CATEGORIES),
            Text::make('Тема', 'subject'),
            Textarea::make('Сообщение', 'message'),
            Select::make('Статус', 'status')->options(SupportTicket::STATUSES),
        ];
    }

    // КНОПКА "ОТВЕТИТЬ" (будет в списке)
    protected function actions(): array
    {
        return [
            Action::make('Ответить')
                ->icon('heroicons.outline.chat-bubble-left-right')
                ->method('sendReply')
                ->form(fn() => [
                    ActionTextarea::make('Ваш ответ', 'reply_text')
                        ->required()
                        ->rows(5),
                ])
                ->successToast('Ответ отправлен!'),
        ];
    }

    // Логика отправки ответа
    public function sendReply(MoonShineFormRequest $request, SupportTicket $ticket): void
    {
        $data = $request->validate([
            'reply_text' => 'required|string|min:1',
        ]);

        DB::transaction(function () use ($data, $ticket, $request) {
            $ticket->messages()->create([
                'body' => $data['reply_text'],
                'sender_type' => 'admin',
                'sender_name' => $request->user()?->name ?? 'Администратор',
                'user_id' => $request->user()?->id,
            ]);

            $ticket->update([
                'last_admin_replied_at' => now(),
                'status' => 'in_progress',
            ]);
        });
    }

    // Поля для просмотра
    protected function detailFields(): iterable
    {
        return [
            ID::make(),
            BelongsTo::make('Пользователь', 'user'),
            Text::make('Email', 'user_email'),
            Text::make('Тема', 'subject'),
            Textarea::make('Сообщение', 'message'),
            Select::make('Статус', 'status')->options(SupportTicket::STATUSES),
        ];
    }
}