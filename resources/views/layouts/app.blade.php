<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="csrf-refresh-url" content="{{ route('csrf.refresh') }}">

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

        @php
            $totalMorosas = (int) ($alertaMorosasCobrar->cantidad ?? 0)
                + (int) ($alertaMorosasPagar->cantidad ?? 0);
        @endphp

        @if($totalMorosas > 0)
            <div class="sticky top-[57px] z-30 border-b border-amber-300 bg-amber-50 px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="flex items-center gap-2 text-sm font-bold text-amber-800">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        Morosidad detectada:
                    </span>

                    @if(($alertaMorosasCobrar->cantidad ?? 0) > 0)
                        <a href="{{ route('cuentas-cobrar.index') }}"
                           class="inline-flex items-center gap-2 rounded-full border border-amber-300 bg-amber-100 px-4 py-1.5 text-sm font-semibold text-amber-900 transition hover:bg-amber-200">
                            Cuentas por cobrar morosas:
                            {{ $alertaMorosasCobrar->cantidad }}
                            {{ $alertaMorosasCobrar->cantidad == 1 ? 'documento' : 'documentos' }}
                            (₡{{ number_format($alertaMorosasCobrar->monto, 2) }})
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    @endif

                    @if(($alertaMorosasPagar->cantidad ?? 0) > 0)
                        <a href="{{ route('cuentas-pagar.index') }}"
                           class="inline-flex items-center gap-2 rounded-full border border-red-300 bg-red-100 px-4 py-1.5 text-sm font-semibold text-red-900 transition hover:bg-red-200">
                            Cuentas por pagar morosas:
                            {{ $alertaMorosasPagar->cantidad }}
                            {{ $alertaMorosasPagar->cantidad == 1 ? 'documento' : 'documentos' }}
                            (₡{{ number_format($alertaMorosasPagar->monto, 2) }})
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

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