<?php

declare(strict_types=1);

namespace App\Filament\Support\Resources;

use App\Filament\Support\Resources\TicketResource\Pages;
use App\Models\Ticket;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-lifebuoy';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            TextInput::make('subject')->required(),
            Textarea::make('body')->required()->rows(5),
            Select::make('status')
                ->options(['open' => 'Open', 'pending' => 'Pending', 'closed' => 'Closed'])
                ->default('open')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')->searchable()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('user.name')->label('Reporter'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['open' => 'Open', 'pending' => 'Pending', 'closed' => 'Closed']),
            ])
            ->actions([EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
