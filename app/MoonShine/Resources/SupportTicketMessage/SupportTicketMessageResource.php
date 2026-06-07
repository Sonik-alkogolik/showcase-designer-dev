<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SupportTicketMessage;

use App\MoonShine\Resources\SupportTicketMessage\Pages\SupportTicketMessageDetailPage;
use App\Models\SupportTicketMessage;
use App\MoonShine\Resources\SupportTicket\SupportTicketResource;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
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

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            IndexPage::class,
            SupportTicketMessageDetailPage::class,
        ];
    }

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

    protected function detailFields(): iterable
    {
        return [
            Text::make('Переписка', 'body')
                ->changePreview(fn (): string => $this->renderConversationHtml())
                ->withoutWrapper(),
        ];
    }

    private function renderConversationHtml(): string
    {
        /** @var SupportTicketMessage|null $message */
        $message = $this->getItem();

        if (! $message instanceof SupportTicketMessage) {
            return '<div class="text-sm text-slate-500">История переписки недоступна.</div>';
        }

        $message->loadMissing([
            'ticket.user',
            'ticket.messages.user',
        ]);

        $ticket = $message->ticket;

        if (! $ticket instanceof \App\Models\SupportTicket) {
            return '<div class="text-sm text-slate-500">История переписки недоступна.</div>';
        }

        $messages = $ticket->messages->sortBy([
            ['created_at', 'asc'],
            ['id', 'asc'],
        ])->values();

        $userLabel = e($ticket->user?->name ?: $ticket->user_email ?: 'Пользователь');
        $subject = e($ticket->subject);
        $status = e((string) (SupportTicketResource::STATUSES[$ticket->status] ?? $ticket->status));

        $html = [];
        $html[] = <<<HTML
            <div class="mb-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-sm text-slate-500">Тикет #{$ticket->id}</div>
                <div class="mt-1 text-lg font-semibold text-slate-900">{$subject}</div>
                <div class="mt-2 flex flex-wrap gap-3 text-sm text-slate-600">
                    <span>Пользователь: {$userLabel}</span>
                    <span>Статус: {$status}</span>
                </div>
            </div>
        HTML;

        $html[] = '<div class="space-y-4">';

        foreach ($messages as $item) {
            $isAdmin = $item->sender_type === 'admin';
            $isSystem = $item->sender_type === 'system';
            $align = $isAdmin ? 'justify-end' : ($isSystem ? 'justify-center' : 'justify-start');
            $bubble = $isAdmin
                ? 'bg-indigo-600 text-white shadow-sm'
                : ($isSystem
                    ? 'bg-slate-200 text-slate-700 border border-slate-300'
                    : 'bg-white text-slate-900 border border-slate-200 shadow-sm');

            $sender = $isAdmin
                ? 'Администратор'
                : ($isSystem
                    ? 'Система'
                    : e($ticket->user?->name ?: $ticket->user_email ?: 'Пользователь'));

            $createdAt = $item->created_at?->format('d.m.Y H:i');
            $body = nl2br(e($item->body));
            $senderType = e($item->sender_type);

            $html[] = <<<HTML
                <div class="flex {$align}">
                    <div class="max-w-3xl rounded-2xl px-4 py-3 {$bubble}">
                        <div class="flex items-center justify-between gap-3 text-xs uppercase tracking-wide opacity-70">
                            <span>{$sender}</span>
                            <span>{$senderType}</span>
                        </div>
                        <div class="mt-2 whitespace-pre-wrap leading-6">{$body}</div>
                        <div class="mt-3 text-xs opacity-60">{$createdAt}</div>
                    </div>
                </div>
            HTML;
        }

        $html[] = '</div>';

        return implode("\n", $html);
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
