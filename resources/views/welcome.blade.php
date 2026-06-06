<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/favicon.png') }}">
            @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
<body class="antialiased bg-[#FDFDFC] text-[#1b1b18]">
    <div class="min-h-screen flex flex-col items-center justify-center p-8">
        <img src="{{ asset('images/sitandgo-logo.png') }}" alt="Sit&Go Logo" class="h-12 w-auto">
        <p class="mt-6 text-gray-600">Open secure area:</p>
        <a
            href="{{ url('/secure/login') }}"
            class="mt-3 inline-flex items-center justify-center rounded-md bg-[rgb(31,52,70)] px-5 py-2 font-semibold text-white transition-colors hover:bg-[rgb(41,62,80)]"
        >
            Sign in
        </a>
        </div>
    </body>
</html>
