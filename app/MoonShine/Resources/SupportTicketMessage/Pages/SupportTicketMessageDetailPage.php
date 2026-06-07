<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SupportTicketMessage\Pages;

use App\MoonShine\Resources\SupportTicketMessage\SupportTicketMessageResource;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Box;

/**
 * @extends DetailPage<SupportTicketMessageResource>
 */
class SupportTicketMessageDetailPage extends DetailPage
{
    /**
     * @return list<ComponentContract>
     */
    protected function mainLayer(): array
    {
        return [
            Box::make([
                Heading::make('История переписки')->class('mb-4'),
                FlexibleRender::make(fn (): string => $this->renderConversation()),
            ]),
            ...parent::mainLayer(),
        ];
    }

    private function renderConversation(): string
    {
        /** @var SupportTicketMessage|null $message */
        $message = $this->getItem();

        if (! $message instanceof SupportTicketMessage) {
            return '<div class="text-sm text-slate-500">История переписки недоступна.</div>';
        }

        $message->loadMissing([
            'ticket.user',
            'ticket.messages.user',
            'user',
        ]);

        /** @var SupportTicket|null $ticket */
        $ticket = $message->ticket;

        if (! $ticket instanceof SupportTicket) {
            return '<div class="text-sm text-slate-500">История переписки недоступна.</div>';
        }

        $messages = $ticket->messages
            ->sortBy([
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $userLabel = e($ticket->user?->name ?: $ticket->user_email ?: 'Пользователь');
        $subject = e($ticket->subject);
        $status = e((string) (SupportTicket::STATUSES[$ticket->status] ?? $ticket->status));

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
            /** @var SupportTicketMessage $item */
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
}
