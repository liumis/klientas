<?php

namespace App\Livewire;

use App\Mail\FormFilledNotificationMail;
use App\Models\Claim;
use App\Models\User;
use App\Rules\ValidBankCardNumber;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use App\Services\MicrosoftGraphMailService;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class SubmitClaim extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public bool $submitted = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->description('Prašome užpildyti visus privalomus laukus.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')->label('Vardas')->required(),
                        TextInput::make('last_name')->label('Pavardė')->required(),

                        TextInput::make('repair_vehicle_plates')
                            ->label('Remontuojamo automobilio valstybiniai numeriai')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('personal_code')
                            ->label('Asmens kodas')
                            ->required()
                            ->length(11)
                            ->regex('/^\d{11}$/')
                            ->validationMessages([
                                'length' => 'Asmens kodas turi būti 11 skaitmenų.',
                                'regex' => 'Asmens kodas turi būti 11 skaitmenų.',
                            ]),

                        DatePicker::make('birth_date')
                            ->label('Gimimo data')
                            ->required()
                            ->maxDate(now()->subYears(18))
                            ->validationMessages([
                                'before_or_equal' => 'Amžius turi būti ne mažesnis nei 18 metų.',
                            ]),

                        TextInput::make('license_number')
                            ->label('Vairuotojo pažymėjimo Nr.')
                            ->required()
                            ->regex('/^\d+$/')
                            ->validationMessages([
                                'regex' => 'Vairuotojo pažymėjimo numerį gali sudaryti tik skaitmenys.',
                            ]),
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

                        TextInput::make('address')->label('Gyvenamosios vietos adresas')->required()->columnSpanFull(),

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
//                                    ->required()
                                    ->after('rental_start')
                                    ->validationMessages([
                                        'after' => 'Pabaigos data turi būti vėlesnė už pradžios datą.',
                                    ]),
                            ]),

                        FileUpload::make('documents')
                            ->label('Nuotraukos')
                            ->multiple()
                            ->image()
                            ->directory('claims')
                            ->columnSpanFull()
                            ->nullable(),

                        Checkbox::make('privacy_accepted')
                            ->label(new HtmlString(
                                'Sutinku su <a href="http://sitandgo.lt/privatumo-politika" target="_blank" rel="noopener noreferrer" class="underline text-[rgb(31,52,70)] hover:text-[rgb(41,62,80)]">Privatumo politika</a>*'
                            ))
                            ->accepted()
                            ->required()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->validationMessages([
                                'accepted' => 'Privalote sutikti su privatumo politika.',
                            ]),
                    ]),
            ])
            ->statePath('data')
            ->model(Claim::class);
    }

    public function create(): void
    {

        $data = $this->form->getState();

        $claim = Claim::create($data);

        $this->submitted = true;

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
    }

    public function render()
    {
        return view('livewire.submit-claim');
    }
}
