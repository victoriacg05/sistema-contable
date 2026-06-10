<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Ipacaraí - Sistema Contable</title>

    @vite(['resources/css/app.css'])
</head>

<body class="font-sans antialiased bg-[#f4f5f7]">

    @include('layouts.navigation')

    <div class="ml-[320px] min-h-screen bg-[#f4f5f7]">

        @isset($header)
            <header class="bg-white border-b border-gray-200 shadow-sm px-10 py-6">
                {{ $header }}
            </header>
        @endisset

        <main class="p-10">
            {{ $slot }}
        </main>

    </div>

</body>

</html>