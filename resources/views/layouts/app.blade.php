<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Ipacaraí - Sistema Contable</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100">

    <style>
        #sidebar { transition: transform 0.3s ease; }
        #main-content {
            margin-left: 320px;
            transition: margin-left 0.3s ease;
        }
        body.sidebar-collapsed #sidebar { transform: translateX(-320px); }
        body.sidebar-collapsed #main-content { margin-left: 0; }

        @media (max-width: 768px) {
            #main-content { margin-left: 0; }
            #sidebar { transform: translateX(-320px); }
            body.sidebar-open #sidebar { transform: translateX(0); }
        }

        /* El contenido aprovecha todo el ancho disponible */
        #main-content main .max-w-7xl {
            max-width: 100%;
        }
    </style>

    @include('layouts.navigation')

    <div id="main-content" class="min-h-screen bg-gray-100 flex flex-col">

        <div class="sticky top-0 z-40 bg-white/90 backdrop-blur border-b border-gray-200 px-6 py-3 flex items-center">
            <button id="sidebar-toggle" type="button" aria-label="Mostrar u ocultar el menú"
                    class="p-2 rounded-xl text-gray-700 hover:bg-gray-100 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <span class="ml-3 text-sm font-semibold text-gray-500">Menú</span>
        </div>

        @isset($header)
            <header class="bg-white border-b border-gray-200 shadow-sm px-10 py-6">
                {{ $header }}
            </header>
        @endisset

        <main class="p-10 flex-1">
            {{ $slot }}
        </main>

        <footer class="border-t border-gray-200 bg-white px-6 py-4 text-center text-sm text-gray-500">
            &copy; {{ date('Y') }} Distribuidora Ipacarai S.A.
        </footer>

    </div>

    <script>
        (function () {
            const boton = document.getElementById('sidebar-toggle');
            const body = document.body;
            const CLAVE = 'sidebar-collapsed';
            const esMovil = () => window.matchMedia('(max-width: 768px)').matches;

            if (localStorage.getItem(CLAVE) === '1') {
                body.classList.add('sidebar-collapsed');
            }

            boton.addEventListener('click', function () {
                if (esMovil()) {
                    body.classList.toggle('sidebar-open');
                    return;
                }
                body.classList.toggle('sidebar-collapsed');
                localStorage.setItem(CLAVE, body.classList.contains('sidebar-collapsed') ? '1' : '0');
            });

            window.addEventListener('resize', function () {
                if (!esMovil()) {
                    body.classList.remove('sidebar-open');
                }
            });
        })();
    </script>

</body>

</html>