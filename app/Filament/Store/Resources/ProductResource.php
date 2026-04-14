<?php

declare(strict_types=1);

namespace App\Filament\Store\Resources;

use App\Filament\Store\Resources\ProductResource\Pages;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            TextInput::make('name')->required(),
            TextInput::make('sku')->required()->unique(ignoreRecord: true),
            TextInput::make('price')->numeric()->required()->minValue(0),
            TextInput::make('stock')->numeric()->integer()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('sku'),
                TextColumn::make('price')->money('eur')->sortable(),
                TextColumn::make('stock')->sortable(),
                TextColumn::make('coupons_count')->counts('coupons'),
            ])
            ->filters([TrashedFilter::make()])
            ->actions([EditAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            ProductResource\RelationManagers\CouponsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
