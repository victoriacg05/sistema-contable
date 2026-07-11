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

        /* Barra de carga global para dar respuesta inmediata al usuario */
        #barra-carga {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0;
            background: #b71c1c;
            z-index: 9999;
            opacity: 0;
            transition: width 0.2s ease, opacity 0.3s ease;
            pointer-events: none;
        }
        #barra-carga.activa { opacity: 1; }

        /* Evita que un boton en proceso se vuelva a presionar */
        [data-procesando] {
            opacity: 0.7;
            cursor: progress;
        }
    </style>

    <div id="barra-carga"></div>

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

    <script>
        (function () {
            const barra = document.getElementById('barra-carga');
            let progreso = 0;
            let temporizador = null;

            function iniciarCarga() {
                if (!barra) return;
                progreso = 15;
                barra.classList.add('activa');
                barra.style.width = progreso + '%';
                clearInterval(temporizador);
                temporizador = setInterval(function () {
                    progreso = Math.min(progreso + Math.random() * 12, 90);
                    barra.style.width = progreso + '%';
                }, 300);
            }

            function terminarCarga() {
                if (!barra) return;
                clearInterval(temporizador);
                barra.style.width = '100%';
                setTimeout(function () {
                    barra.classList.remove('activa');
                    barra.style.width = '0';
                }, 300);
            }

            // Navegacion por enlaces: feedback inmediato con un solo clic
            document.addEventListener('click', function (e) {
                const enlace = e.target.closest('a[href]');
                if (!enlace) return;
                const href = enlace.getAttribute('href') || '';
                if (
                    e.defaultPrevented ||
                    e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey ||
                    enlace.target === '_blank' ||
                    enlace.hasAttribute('download') ||
                    href.startsWith('#') ||
                    href.startsWith('javascript:') ||
                    href.startsWith('mailto:') ||
                    href.startsWith('tel:')
                ) {
                    return;
                }
                iniciarCarga();
            });

            // Envio de formularios: barra de carga + prevencion de doble envio
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (form.hasAttribute('data-sin-bloqueo')) {
                    iniciarCarga();
                    return;
                }
                iniciarCarga();
                const botones = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                botones.forEach(function (btn) {
                    // Se difiere para no interferir con el envio nativo del formulario
                    setTimeout(function () {
                        btn.setAttribute('data-procesando', '1');
                        btn.disabled = true;
                    }, 0);
                });
            });

            // Restaura el estado al volver con el boton "atras" (cache del navegador)
            window.addEventListener('pageshow', function () {
                terminarCarga();
                document.querySelectorAll('[data-procesando]').forEach(function (btn) {
                    btn.removeAttribute('data-procesando');
                    btn.disabled = false;
                });
            });
        })();
    </script>

</body>

</html>