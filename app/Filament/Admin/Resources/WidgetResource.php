<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WidgetResource\Pages;
use App\Models\Widget;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WidgetResource extends Resource
{
    protected static ?string $model = Widget::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    // Race-trigger: getEloquentQuery dependet auf filament()->getTenant()
    // bei JEDER Query — nicht nur beim Mount. Wenn Filament-Manager-State
    // zwischen Tests im selben Worker race't → Query sieht inkonsistenten State.
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Optional tenant scoping via live Filament state lookup.
        if ($tenant = filament()->getTenant()) {
            $query->where('team_id', $tenant->id);
        }

        return $query;
    }

    public static function form(Schema $form): Schema
    {
        // Race-trigger: Form-Schema liest filament()-State zur Panel-ID.
        $isAdmin = filament()->getCurrentPanel()?->getId() === 'admin';

        return $form->schema([
            TextInput::make('name')->required(),
            Textarea::make('description')
                ->visible($isAdmin),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('description'),
                TextColumn::make('user.name')->label('Creator'),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('user_id')->relationship('user', 'name'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            WidgetResource\RelationManagers\TagsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWidgets::route('/'),
            'create' => Pages\CreateWidget::route('/create'),
            'edit' => Pages\EditWidget::route('/{record}/edit'),
        ];
    }
}
