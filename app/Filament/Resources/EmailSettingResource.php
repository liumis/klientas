<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailSettingResource\Pages;
use App\Models\EmailSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmailSettingResource extends Resource
{
    protected static ?string $model = EmailSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Email settings';

    protected static ?string $modelLabel = 'Email settings';

    protected static ?string $pluralModelLabel = 'Email settings';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Microsoft 365 / Graph')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('tenant_id')
                            ->label('Tenant ID')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_id')
                            ->label('Client ID')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('client_secret')
                            ->label('Client secret')
                            ->password()
                            ->revealable()
                            ->columnSpanFull()
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                    ]),

                Forms\Components\Section::make('Outgoing mail')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('mail')
                            ->label('Mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->helperText('Microsoft 365 mailbox that sends mail (Graph user principal name).'),

                        Forms\Components\TextInput::make('from_address')
                            ->label('From')
                            ->maxLength(255)
                            ->helperText('Optional display name, e.g. Sit&Go or Sit&Go <noreply@yourdomain.lt>.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mail')
                    ->label('Mail')
                    ->searchable(),

                Tables\Columns\TextColumn::make('from_address')
                    ->label('From')
                    ->limit(40),

                Tables\Columns\TextColumn::make('tenant_id')
                    ->label('Tenant ID')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailSettings::route('/'),
            'create' => Pages\CreateEmailSetting::route('/create'),
            'edit' => Pages\EditEmailSetting::route('/{record}/edit'),
        ];
    }
}
