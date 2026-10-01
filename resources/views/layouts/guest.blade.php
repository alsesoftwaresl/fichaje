<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-50">
            <a href="/" class="flex items-center gap-2">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
                        <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="font-semibold text-lg text-slate-900">{{ config('app.name') }}</span>
            </a>

            <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white border border-slate-200 shadow-sm sm:rounded-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
