<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\SupportTicket\Pages;

use App\MoonShine\Resources\SupportTicket\SupportTicketResource;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Components\Heading;
use MoonShine\UI\Components\Layout\Box;
use Throwable;

/**
 * @extends DetailPage<SupportTicketResource>
 */
class SupportTicketDetailPage extends DetailPage
{
    /**
     * @return list<ComponentContract>
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            Box::make([
                Heading::make('История переписки')->class('mb-4'),
                $this->getResource()->messagesHistory(),
            ]),
        ];
    }
}
