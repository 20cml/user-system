<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="h-screen bg-gray-700 flex overflow-hidden">
            @include('layouts.navigation')

            <div class="flex-1 min-w-0 flex flex-col min-h-0">
                <!-- Page Heading (sits on the dark frame) -->
                @isset($header)
                    <header>
                        <div class="pt-3 pb-0 pl-8 sm:pl-10 pr-0">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- thin gray gap between the dark frame and the content card, all corners curved -->
                <div class="flex-1 bg-gray-200 rounded-tl-2xl min-h-0 flex flex-col">
                    <main class="ml-4 mt-4 mr-4 mb-4 bg-gray-50 rounded-xl flex-1 min-h-0 flex flex-col overflow-y-auto">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </body>
</html>
