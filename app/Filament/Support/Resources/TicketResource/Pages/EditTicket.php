<?php

declare(strict_types=1);

namespace App\Filament\Support\Resources\TicketResource\Pages;

use App\Filament\Support\Resources\TicketResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
