<?php

namespace App\Livewire;

use App\Mail\FormFilledNotificationMail;
use App\Models\Claim;
use App\Models\Garage;
use App\Models\Partner;
use App\Models\User;
use App\Jobs\GenerateClaimPdf;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class SubmitClaim extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Asmeninė informacija ir žalų registracija')
                    ->description('Prašome užpildyti visus privalomus laukus.')
                    ->columns(2)
                    ->schema([
                        Select::make('garage_id')
                            ->label('Servisas')
                            ->options(function (): array {
                                if (! Schema::hasTable('garages')) {
                                    return [];
                                }

                                return Garage::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
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

                        TextInput::make('address')->label('Registracijos adresas')->required()->columnSpanFull(),

                        TextInput::make('phone')->label('Telefonas')->tel()->required(),
                        TextInput::make('email')->label('El. paštas')->email()->required(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('claim_number')->label('Žalos numeris')->required(),

                                Select::make('partner_id')
                                    ->label('Kaltininko draudimo bendrovė')
                                    ->options(function (): array {
                                        if (! Schema::hasTable('partners')) {
                                            return [];
                                        }

                                        return Partner::query()
                                    ->orderBy('short_name')
                                    ->pluck('short_name', 'id')
                                    ->all();
                                    })
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
//                                    ->required()
                                    ->after('rental_start')
                                    ->validationMessages([
                                        'after' => 'Pabaigos data turi būti vėlesnė už pradžios datą.',
                                    ]),
                            ]),

                        FileUpload::make('documents')
                            ->label('Dokumentų nuotraukos')
                            ->multiple()
                            ->image()
                            ->directory('claims')
                            ->columnSpanFull()
                            ->nullable(),
                    ]),
            ])
            ->statePath('data')
            ->model(Claim::class);
    }

    public function create(): void
    {

        $data = $this->form->getState();

        $claim = Claim::create($data);


        GenerateClaimPdf::dispatch($claim);

        session()->flash('message', 'Forma sėkmingai pateikta! Dokumentai ruošiami.');

        $notificationRecipients = User::query()
            ->where('send_notifications', true)
            ->pluck('email')
            ->filter()
            ->values()
            ->all();

        if ($notificationRecipients !== []) {
            app(MicrosoftGraphMailService::class)->send(
                new FormFilledNotificationMail($claim),
                $notificationRecipients,
            );
        }

        $this->form->fill();
    }

    public function render()
    {
        return view('livewire.submit-claim');
    }
}
