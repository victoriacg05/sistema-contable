<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado - Sistema Contable Ipacaraí</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-[2rem] shadow-lg p-10 max-w-md text-center">
        <div class="text-[#c62828] text-6xl font-bold mb-4">403</div>
        <h1 class="text-2xl font-semibold text-gray-800 mb-2">Acceso Denegado</h1>
        <p class="text-gray-600 mb-6">
            No tiene permisos para acceder a esta sección del sistema.
            Contacte al administrador si cree que esto es un error.
        </p>
        <div class="flex justify-center gap-4">
            <a href="{{ route('dashboard') }}"
               class="bg-[#c62828] text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                Ir al Inicio
            </a>
            <a href="{{ url()->previous() }}"
               class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                Volver
            </a>
        </div>
    </div>
</body>
</html>
