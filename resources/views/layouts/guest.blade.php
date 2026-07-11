<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Distribuidora Ipacaraí</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="font-sans antialiased bg-gray-100 min-h-screen flex flex-col">

    <div class="flex-1">
        {{ $slot }}
    </div>

    <footer class="border-t border-gray-200 bg-white px-6 py-4 text-center text-sm text-gray-500">
        &copy; {{ date('Y') }} Distribuidora Ipacarai S.A.
    </footer>

</body>

</html>