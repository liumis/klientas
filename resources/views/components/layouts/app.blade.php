<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Žalos registracija</title>
        <link rel="icon" type="image/png" href="{{ asset('images/brand/favicon.png') }}">

        <style>[x-cloak] { display: none !important; }</style>

        <script src="https://cdn.tailwindcss.com"></script>

        @filamentStyles
        @livewireStyles
    </head>
    <body class="antialiased bg-[#FDFDFC] text-[#1b1b18] min-h-screen p-8">
        <div class="max-w-4xl mx-auto">
            {{ $slot }}
        </div>

        @livewireScripts
        @filamentScripts
    </body>
</html>