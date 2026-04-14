<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WidgetResource\Pages;

use App\Filament\Admin\Resources\WidgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWidget extends CreateRecord
{
    protected static string $resource = WidgetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['team_id'] = filament()->getTenant()?->id ?? auth()->user()->teams->first()->id;
        $data['user_id'] = auth()->id();

        return $data;
    }
}
