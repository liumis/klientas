<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClaimResource\Pages;
use App\Enums\ClaimStatus;
use App\Models\Claim;
use App\Rules\ValidBankCardNumber;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClaimResource extends Resource
{
    protected static ?string $model = Claim::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Requests';

    protected static ?string $modelLabel = 'Request';

    protected static ?string $pluralModelLabel = 'Requests';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')->label('Vardas')->required(),
                        TextInput::make('last_name')->label('Pavardė')->required(),

                        TextInput::make('repair_vehicle_plates')
                            ->label('Remontuojamo automobilio valstybiniai numeriai')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('personal_code')->label('Asmens kodas')->required(),
                        DatePicker::make('birth_date')->label('Gimimo data')->required(),

                        TextInput::make('license_number')->label('Vairuotojo pažymėjimo Nr.')->required(),
                        DatePicker::make('license_expires_at')->label('Pažymėjimo galiojimas')->required(),

                        TextInput::make('id_or_passport_number')
                            ->label('ID arba paso numeris')
                            ->required(),

                        DatePicker::make('id_or_passport_expires_at')
                            ->label('ID arba paso galiojimas (iki)')
                            ->required(),

                        TextInput::make('bank_card_number')
                            ->label('Banko kortelės numeris')
                            ->required()
                            ->rules([new ValidBankCardNumber()])
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                                ? preg_replace('/\D/', '', $state)
                                : null),

                        DatePicker::make('bank_card_expires_at')
                            ->label('Banko kortelės galiojimas')
                            ->required()
                            ->minDate(now()->startOfMonth())
                            ->validationMessages([
                                'after_or_equal' => 'Banko kortelės galiojimas negali būti pasibaigęs.',
                            ]),

                        TextInput::make('address')
                            ->label('Registracijos adresas')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('phone')->label('Telefonas')->tel()->required(),
                        TextInput::make('email')->label('El. paštas')->email()->required(),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('rental_start')
                                    ->label('Nuomos pradžia')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('rental_end', null)),

                                DatePicker::make('rental_end')
                                    ->label('Nuomos pabaiga')
                                    ->after('rental_start'),
                            ]),

                        FileUpload::make('documents')
                            ->label('Nuotraukos')
                            ->multiple()
                            ->image()
                            ->directory('claims')
                            ->disk('public')
                            ->columnSpanFull()
                            ->nullable(),

                        Select::make('status')
                            ->label('Statusas')
                            ->options(ClaimStatus::class)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Pateikta')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('first_name')
                    ->label('Vardas')
                    ->searchable(),
                TextColumn::make('repair_vehicle_plates')
                    ->label('Valst. numeriai')
                    ->searchable(),
                TextColumn::make('rental_start')
                    ->label('Nuoma nuo')
                    ->date()
                    ->sortable(),
                TextColumn::make('rental_end')
                    ->label('Nuoma iki')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statusas')
                    ->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('changeStatus')
                    ->label('Keisti statusą')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->form([
                        Select::make('status')
                            ->label('Naujas statusas')
                            ->options(ClaimStatus::class)
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['status' => $data['status']]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClaims::route('/'),
            'create' => Pages\CreateClaim::route('/create'),
            'edit' => Pages\EditClaim::route('/{record}/edit'),
        ];
    }
}
