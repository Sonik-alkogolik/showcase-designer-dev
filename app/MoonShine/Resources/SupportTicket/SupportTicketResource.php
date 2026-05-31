<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SupportTicket;

use App\Models\SupportTicket;
use App\MoonShine\Resources\SupportTicketMessage\SupportTicketMessageResource;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

class SupportTicketResource extends ModelResource
{
    protected string $model = SupportTicket::class;
    protected string $title = 'Техподдержка';

    // Только просмотр сообщений
    protected function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Тема', 'subject'),
            HasMany::make('История тикета', 'messages', resource: SupportTicketMessageResource::class),
        ];
    }
}