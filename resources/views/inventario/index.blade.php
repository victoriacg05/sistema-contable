<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">Movimientos de Inventario</h1>
                <p class="mt-2 text-gray-700 text-lg">Control de entradas y salidas del inventario</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-300 text-green-700 px-6 py-4 rounded-2xl font-semibold">
                {{ session('success') }}
            </div>
        @endif

        @php
            $totalEntradas = 0;
            $totalSalidas = 0;
            foreach ($movimientos as $m) {
                if (in_array(strtolower($m->tipo_nombre), ['entrada', 'ajuste positivo', 'devolución'])) {
                    $totalEntradas += $m->cantidad;
                } else {
                    $totalSalidas += $m->cantidad;
                }
            }
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                <p class="text-sm font-bold text-gray-500 uppercase">Total entradas</p>
                <p class="mt-2 text-3xl font-extrabold text-green-700">{{ number_format($totalEntradas) }}</p>
                <p class="text-sm text-gray-500">unidades ingresadas</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                <p class="text-sm font-bold text-gray-500 uppercase">Total salidas</p>
                <p class="mt-2 text-3xl font-extrabold text-red-700">{{ number_format($totalSalidas) }}</p>
                <p class="text-sm text-gray-500">unidades retiradas</p>
            </div>
            <div class="bg-white rounded-2xl shadow-md border border-gray-200 p-6">
                <p class="text-sm font-bold text-gray-500 uppercase">Existencia neta</p>
                <p class="mt-2 text-3xl font-extrabold text-[#1f2937]">{{ number_format($totalEntradas - $totalSalidas) }}</p>
                <p class="text-sm text-gray-500">entradas − salidas</p>
            </div>
        </div>

        <div class="flex gap-2 mb-6">
            <button type="button" data-tab="entrada"
                    class="tab-inventario px-6 py-3 rounded-2xl font-bold transition bg-[#b71c1c] text-white shadow-md">
                Entradas
            </button>
            <button type="button" data-tab="salida"
                    class="tab-inventario px-6 py-3 rounded-2xl font-bold transition bg-gray-100 text-gray-700 hover:bg-gray-200">
                Salidas
            </button>
        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#2b2b2b] text-white">
                    <tr>
                        <th class="px-6 py-5 text-left">Referencia</th>
                        <th class="px-6 py-5 text-left">Código</th>
                        <th class="px-6 py-5 text-left">Producto</th>
                        <th class="px-6 py-5 text-left">Tipo</th>
                        <th class="px-6 py-5 text-center">Cantidad</th>
                        <th class="px-6 py-5 text-left">Fecha</th>
                        <th class="px-6 py-5 text-left">Usuario</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($movimientos as $mov)
                        @php
                            $esEntrada = in_array(strtolower($mov->tipo_nombre), ['entrada', 'ajuste positivo', 'devolución']);
                        @endphp
                        <tr class="fila-movimiento border-b border-gray-200 hover:bg-gray-50 transition"
                            data-tipo="{{ $esEntrada ? 'entrada' : 'salida' }}">
                            <td class="px-6 py-5 font-semibold">{{ $mov->referencia_movimiento }}</td>
                            <td class="px-6 py-5 font-mono text-sm">{{ $mov->producto_codigo }}</td>
                            <td class="px-6 py-5">{{ $mov->producto_nombre }}</td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1 rounded-full text-xs font-bold
                                    {{ $esEntrada ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $mov->tipo_nombre }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-center font-mono">{{ $mov->cantidad }}</td>
                            <td class="px-6 py-5">{{ \Carbon\Carbon::parse($mov->fecha)->format('d/m/Y') }}</td>
                            <td class="px-6 py-5">{{ $mov->usuario_nombre }}</td>
                        </tr>
                    @empty
                    @endforelse

                    <tr id="fila-vacia-entrada">
                        <td colspan="7" class="px-6 py-10 text-center text-gray-600">No hay entradas registradas.</td>
                    </tr>
                    <tr id="fila-vacia-salida" class="hidden">
                        <td colspan="7" class="px-6 py-10 text-center text-gray-600">No hay salidas registradas.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        (function () {
            const tabs = document.querySelectorAll('.tab-inventario');
            const filas = document.querySelectorAll('.fila-movimiento');
            const vacias = {
                entrada: document.getElementById('fila-vacia-entrada'),
                salida: document.getElementById('fila-vacia-salida'),
            };

            function activar(tipo) {
                tabs.forEach(function (btn) {
                    const activo = btn.dataset.tab === tipo;
                    btn.classList.toggle('bg-[#b71c1c]', activo);
                    btn.classList.toggle('text-white', activo);
                    btn.classList.toggle('shadow-md', activo);
                    btn.classList.toggle('bg-gray-100', !activo);
                    btn.classList.toggle('text-gray-700', !activo);
                    btn.classList.toggle('hover:bg-gray-200', !activo);
                });

                let visibles = 0;
                filas.forEach(function (fila) {
                    const mostrar = fila.dataset.tipo === tipo;
                    fila.classList.toggle('hidden', !mostrar);
                    if (mostrar) visibles++;
                });

                vacias.entrada.classList.toggle('hidden', !(tipo === 'entrada' && visibles === 0));
                vacias.salida.classList.toggle('hidden', !(tipo === 'salida' && visibles === 0));
            }

            tabs.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activar(btn.dataset.tab);
                });
            });

            activar('entrada');
        })();
    </script>
</x-app-layout>
