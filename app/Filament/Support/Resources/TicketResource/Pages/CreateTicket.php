<?php

declare(strict_types=1);

namespace App\Filament\Support\Resources\TicketResource\Pages;

use App\Filament\Support\Resources\TicketResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = filament()->getTenant()?->id ?? auth()->user()->teams->first()->id;
        $data['user_id'] = auth()->id();

        return $data;
    }
}
