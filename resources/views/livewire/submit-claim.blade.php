<div class="max-w-4xl mx-auto">
    <div class="bg-white border border-[#e3e3e0] rounded-xl shadow-sm p-6 md:p-8">
        <div class="mb-6">
            <img src="{{ asset('images/sitandgo-logo.png') }}" alt="Sit&Go Logo" class="h-10 w-auto">
        </div>

        <h1 class="text-2xl font-semibold">Registracija</h1>

        @if ($submitted)
            <div class="mt-6 p-6 bg-green-50 text-green-800 rounded-lg border border-green-200">
                <p class="text-base leading-relaxed">
                    Dėkojame, Jūsų duomenys sėkmingai gauti mūsų sistemoje. Pakaitinio automobilio atsiėmimo dieną su Jumis susisieksime ir informuosime apie tolesnius automobilio atsiėmimo veiksmus.
                </p>
            </div>
        @else
            <p class="mt-2 text-sm text-gray-600">Prašome užpildyti formą ir pateikti duomenis, reikalingus pakaitinio automobilio nuomos sutarčiai parengti.</p>

            <form wire:submit.prevent="create" class="mt-6">
                {{ $this->form }}

                <button
                    type="submit"
                    class="mt-6 inline-flex items-center justify-center px-5 py-2 rounded-md bg-[rgb(31,52,70)] text-white font-semibold hover:bg-[rgb(41,62,80)] transition-colors"
                    wire:loading.attr="disabled"
                    wire:target="create"
                >
                    <span wire:loading.remove wire:target="create">Pateikti duomenis</span>
                    <span wire:loading wire:target="create">Siunčiama...</span>
                </button>
            </form>
        @endif
    </div>

    <x-filament-actions::modals />
</div>
