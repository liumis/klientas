<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Partner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Insuarance companies';

    protected static ?string $modelLabel = 'Insuarance company';
    protected static ?string $pluralModelLabel = 'Insuarance companies';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Draudimo bendrovės informacija')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Pilnas bendrovės pavadinimas')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('short_name')
                            ->label('Trumpas bendrovės pavadinimas')
                            ->placeholder('Pvz.: LD, ERGO, BTA')
                            ->required()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('company_code')
                            ->label('Įmonės kodas')
                            ->required(),

                        Forms\Components\TextInput::make('address')
                            ->label('Adresas')
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Pavadinimas')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('company_code')
                    ->label('Įmonės kodas')
                    ->searchable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Adresas')
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
