<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SupportTicket;

use App\Models\SupportTicket;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

class SupportTicketResource extends ModelResource
{
    protected string $model = SupportTicket::class;
    protected string $title = 'Техподдержка';

    protected function fields(): iterable
    {
        return [
            ID::make(),
            Text::make('Тема', 'subject'),
            Textarea::make('Сообщение', 'message'),
        ];
    }
}