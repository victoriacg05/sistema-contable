<x-app-layout>
    <div class="max-w-7xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-extrabold text-[#1f2937]">Movimientos de Inventario</h1>
                <p class="mt-2 text-gray-500 text-lg">Control de entradas y salidas del inventario</p>
            </div>

            <a href="{{ route('inventario.create') }}"
               class="bg-[#b71c1c] hover:bg-red-700 text-white px-8 py-4 rounded-2xl font-bold shadow-md transition">
                Nuevo Movimiento
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-300 text-green-700 px-6 py-4 rounded-2xl font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden">
            <table class="w-full">
                <thead class="bg-[#2b2b2b] text-white">
                    <tr>
                        <th class="px-6 py-5 text-left">Referencia</th>
                        <th class="px-6 py-5 text-left">Producto</th>
                        <th class="px-6 py-5 text-left">Tipo</th>
                        <th class="px-6 py-5 text-center">Cantidad</th>
                        <th class="px-6 py-5 text-left">Fecha</th>
                        <th class="px-6 py-5 text-left">Usuario</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($movimientos as $mov)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="px-6 py-5 font-semibold">{{ $mov->referencia_movimiento }}</td>
                            <td class="px-6 py-5">{{ $mov->producto_nombre }}</td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1 rounded-full text-xs font-bold
                                    {{ in_array(strtolower($mov->tipo_nombre), ['entrada', 'ajuste positivo', 'devolución']) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $mov->tipo_nombre }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-center font-mono">{{ $mov->cantidad }}</td>
                            <td class="px-6 py-5">{{ \Carbon\Carbon::parse($mov->fecha)->format('d/m/Y') }}</td>
                            <td class="px-6 py-5">{{ $mov->usuario_nombre }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-600">No hay movimientos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
