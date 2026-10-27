<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 antialiased">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AG Manage') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8 text-slate-800 bg-[#F8FAFC]">
        <div class="mb-6">
            <a href="/" class="transition hover:opacity-90">
                <x-application-logo />
            </a>
        </div>

        <div class="w-full sm:max-w-md p-8 bg-white border border-slate-200/90 shadow-lg shadow-slate-200/40 rounded-2xl">
            {{ $slot }}
        </div>
    </body>
</html>
