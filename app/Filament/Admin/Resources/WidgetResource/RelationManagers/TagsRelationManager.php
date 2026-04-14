<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WidgetResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TagsRelationManager extends RelationManager
{
    protected static string $relationship = 'tags';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            TextInput::make('name')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('color')->badge(),
            ])
            ->headerActions([AttachAction::make()])
            ->actions([DetachAction::make()])
            ->bulkActions([DetachBulkAction::make()]);
    }
}
