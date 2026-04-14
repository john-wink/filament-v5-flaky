<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WidgetResource\Pages;

use App\Filament\Admin\Resources\WidgetResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWidget extends EditRecord
{
    protected static string $resource = WidgetResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
