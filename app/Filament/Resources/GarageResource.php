<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GarageResource\Pages;
use App\Filament\Resources\GarageResource\RelationManagers;
use App\Models\Garage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GarageResource extends Resource
{
    protected static ?string $model = Garage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationLabel = 'Auto services';

    protected static ?string $modelLabel = 'Auto service';

    protected static ?string $pluralModelLabel = 'Auto services';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Serviso informacija')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Pavadinimas')
                            ->required(),

                        Forms\Components\TextInput::make('wheels_agent')
                            ->label('Wheels agent'),

                        Forms\Components\TextInput::make('wheels_source')
                            ->label('Wheels source'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Pavadinimas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('wheels_agent')
                    ->label('Wheels agent'),
                Tables\Columns\TextColumn::make('wheels_source')
                    ->label('Wheels source'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGarages::route('/'),
            'create' => Pages\CreateGarage::route('/create'),
            'edit' => Pages\EditGarage::route('/{record}/edit'),
        ];
    }
}
