<div class="max-w-4xl mx-auto">
    <div class="bg-white border border-[#e3e3e0] rounded-xl shadow-sm p-6 md:p-8">
        <div class="mb-6">
            <img src="{{ asset('images/sitandgo-logo.png') }}" alt="Sit&Go Logo" class="h-10 w-auto">
        </div>

        <h1 class="text-2xl font-semibold">Žalos registracija</h1>
        <p class="mt-2 text-sm text-gray-600">Užpildykite formą ir pateikite draudiminio įvykio informaciją.</p>

        <form wire:submit.prevent="create" class="mt-6">
        {{ $this->form }}

            <button
                type="submit"
                class="mt-6 inline-flex items-center justify-center px-5 py-2 rounded-md bg-[rgb(31,52,70)] text-white font-semibold hover:bg-[rgb(41,62,80)] transition-colors"
            >
                Pateikti duomenis
            </button>
        </form>

        @if (session()->has('message'))
            <div class="mt-4 p-4 bg-green-50 text-green-700 rounded-lg border border-green-200">
                {{ session('message') }}
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</div>