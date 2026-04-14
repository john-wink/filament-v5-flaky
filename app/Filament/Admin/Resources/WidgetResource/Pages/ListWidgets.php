<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WidgetResource\Pages;

use App\Filament\Admin\Resources\WidgetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWidgets extends ListRecords
{
    protected static string $resource = WidgetResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
