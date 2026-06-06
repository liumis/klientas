<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClaimResource\Pages;
use App\Enums\ClaimStatus;
use App\Models\Claim;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Jobs\GenerateClaimPdf;
use App\Mail\DocumentSigningRequestMail;
use App\Services\MicrosoftGraphMailService;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use App\Services\MarkSignService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
                Section::make('Asmeninė informacija ir žalų registracija')
                    ->columns(2)
                    ->schema([
                        Select::make('garage_id')
                            ->label('Servisas')
                            ->relationship('garage', 'name', fn ($query) => $query->orderBy('name'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('first_name')->label('Vardas')->required(),
                        TextInput::make('last_name')->label('Pavardė')->required(),
                        TextInput::make('personal_code')->label('Asmens kodas')->required(),
                        DatePicker::make('birth_date')->label('Gimimo data')->required(),

                        TextInput::make('license_number')->label('Vairuotojo pažymėjimo Nr.')->required(),
                        DatePicker::make('license_expires_at')->label('Pažymėjimo galiojimas')->required(),

                        TextInput::make('address')
                            ->label('Registracijos adresas')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('phone')->label('Telefonas')->tel()->required(),
                        TextInput::make('email')->label('El. paštas')->email()->required(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('claim_number')->label('Žalos numeris')->required(),

                                Select::make('partner_id')
                                    ->label('Kaltininko draudimo bendrovė')
                                    ->relationship('partner', 'short_name', fn ($query) => $query->orderBy('short_name'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),

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
                            ->label('Dokumentų nuotraukos')
                            ->multiple()
                            ->image()
                            ->directory('claims')
                            ->disk('public')
                            ->columnSpanFull()
                            ->nullable(),
                    ]),

                Section::make('MarkSign ir statusas')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Statusas')
                            ->options(ClaimStatus::class)
                            ->required(),

                        TextInput::make('marksign_uuid')
                            ->label('MarkSign UUID')
                            ->disabled(),

                        TextInput::make('signing_url')
                            ->label('Pasirašymo nuoroda')
                            ->url()
                            ->columnSpanFull(),

                        TextInput::make('signed_pdf_path')
                            ->label('Pasirašyto PDF kelias')
                            ->disabled()
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
                 TextColumn::make('partner.name')
                    ->label('Kaltininko draudimo bendrovė')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('rental_start')
                    ->label('Nuoma nuo')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rental_end')
                    ->label('Nuoma iki')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statusas')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (mixed $state): string => match ($state instanceof \App\Enums\ClaimStatus ? $state->value : $state) {
                        'signed' => 'success',
                        'awaiting_signature' => 'warning',
                        'error' => 'danger',
                        default => 'gray',
                    })
            ])

            ->actions([
                Tables\Actions\ActionGroup::make([
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
                ]),
            ])
        ->actions([
            Tables\Actions\Action::make('checkStatus')
                ->label('Patikrinti statusą')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn ($record) => $record->marksign_uuid && $record->status !== ClaimStatus::Signed)
                ->action(function ($record, MarkSignService $service) {
                    $data = $service->getDocumentStatus($record->marksign_uuid);

                    $signers = $data['signers'] ?? [];
                    $firstSigner = $signers[0] ?? null;

                    if ($firstSigner && isset($firstSigner['signStatus']) && $firstSigner['signStatus'] === 'signed') {

                        try {
                            $path = $service->downloadSignedDocument($record->marksign_uuid);

                            if ($path) {
                                $record->update([
                                    'status' => \App\Enums\ClaimStatus::Signed,
                                    'signed_pdf_path' => $path,
                                ]);

                                Notification::make()
                                    ->success()
                                    ->title('Dokumentas pasirašytas!')
                                    ->body('Failas parsiųstas ir statusas atnaujintas.')
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Log::error('Nepavyko parsiųsti dokumento: ' . $e->getMessage());

                            Notification::make()
                                ->danger()
                                ->title('Statusas pasirašytas, bet įvyko klaida siunčiantis failą.')
                                ->send();
                        }
                    } else {
                        $currentStatus = $firstSigner['signStatus'] ?? 'nežinomas';

                        Notification::make()
                            ->warning()
                            ->title('Dokumentas dar nepasirašytas.')
                            ->body("Dabartinis pasirašytojo statusas: {$currentStatus}")
                            ->send();
                    }
                }),

            Action::make('resendSigningEmail')
                ->label('Resend')
                ->icon('heroicon-o-envelope')
                ->color('success')
                ->visible(fn ($record) => filled($record->signing_url) && filled($record->email))
                ->requiresConfirmation()
                ->modalHeading('Resend signing email?')
                ->modalDescription(fn ($record) => "Email will be sent to: {$record->email}")
                ->action(function ($record) {
                    try {
                        $record->refresh();

                        app(MicrosoftGraphMailService::class)->send(
                            new DocumentSigningRequestMail($record),
                            $record->email,
                        );

                        Notification::make()
                            ->success()
                            ->title('Email sent')
                            ->body($record->email)
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Failed to send email')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Tables\Actions\Action::make('downloadSigned')
                ->label('Parsiųsti pasirašytą')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->visible(fn ($record) => $record->status === ClaimStatus::Signed && $record->signed_pdf_path)
                ->action(function ($record) {
                    if (!Storage::exists($record->signed_pdf_path)) {
                        Notification::make()->danger()->title('Failas nerastas serveryje.')->send();
                        return;
                    }

                    return Storage::download(
                        $record->signed_pdf_path,
                        "pasirasytas_dokumentas_{$record->id}.pdf"
                    );
                }),

        Action::make('retry_pdf')
            ->label('Pergeneruoti')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->size(ActionSize::Small)
            ->visible(fn ($record) => in_array($record->status, [
                ClaimStatus::Pending,
                ClaimStatus::Error,
                ClaimStatus::AwaitingSignature,
            ], true))
            ->requiresConfirmation()
            ->modalHeading('Pergeneruoti dokumentą?')
            ->modalDescription('Ar tikrai norite iš naujo sugeneruoti PDF ir nusiųsti užklausą į Mark Sign?')
            ->action(function ($record) {
                GenerateClaimPdf::dispatch($record);
                Notification::make()
                    ->title('Procesas paleistas')
                    ->body('PDF generavimas ir Mark Sign integracija sėkmingai pridėti į eilę.')
                    ->success()
                    ->send();
            }),

        Tables\Actions\EditAction::make(),
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
