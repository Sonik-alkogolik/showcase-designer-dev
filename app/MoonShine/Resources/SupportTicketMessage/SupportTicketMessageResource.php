<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SupportTicketMessage;

use App\MoonShine\Resources\SupportTicketMessage\Pages\SupportTicketMessageDetailPage;
use App\Models\SupportTicketMessage;
use App\Models\SupportTicket;
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

        if (! $ticket instanceof SupportTicket) {
            return '<div class="text-sm text-slate-500">История переписки недоступна.</div>';
        }

        $ticketsQuery = SupportTicket::query()
            ->with(['messages.user', 'user'])
            ->when(
                filled($ticket->user_id),
                static fn ($query) => $query->where('user_id', $ticket->user_id),
                static fn ($query) => $query->where('user_email', $ticket->user_email),
            )
            ->orderBy('created_at')
            ->orderBy('id');

        $tickets = $ticketsQuery->get();

        $userLabel = e($ticket->user?->name ?: $ticket->user_email ?: 'Пользователь');

        $html = [];
        $html[] = <<<HTML
            <div class="mb-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="text-sm text-slate-500">Пользователь</div>
                <div class="mt-1 text-lg font-semibold text-slate-900">{$userLabel}</div>
                <div class="mt-2 text-sm text-slate-600">
                    История собрана только по тикетам этого пользователя.
                </div>
            </div>
        HTML;

        $html[] = '<div class="space-y-6">';

        foreach ($tickets as $userTicket) {
            $ticketStatus = e((string) (SupportTicketResource::STATUSES[$userTicket->status] ?? $userTicket->status));
            $ticketSubject = e($userTicket->subject);

            $html[] = <<<HTML
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-sm text-slate-500">Тикет #{$userTicket->id}</div>
                            <div class="mt-1 text-base font-semibold text-slate-900">{$ticketSubject}</div>
                        </div>
                        <div class="text-sm text-slate-600">{$ticketStatus}</div>
                    </div>
                    <div class="mt-4 space-y-4">
            HTML;

            foreach ($userTicket->messages->sortBy([
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])->values() as $item) {
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
                        : e($userTicket->user?->name ?: $userTicket->user_email ?: 'Пользователь'));

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

            $html[] = '</div></div>';
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
