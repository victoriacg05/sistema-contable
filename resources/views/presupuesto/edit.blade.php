<x-app-layout>
    @php
        $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    @endphp

    <div class="max-w-3xl mx-auto">

        <div class="mb-8">
            <a href="{{ route('presupuesto.show', [$presupuesto->anio, $presupuesto->mes]) }}"
               class="text-sm font-semibold text-gray-500 hover:text-gray-700">← Volver al detalle</a>

            <h1 class="text-4xl font-extrabold text-[#1f2937] mt-2">
                Editar línea presupuestaria
            </h1>

            <p class="mt-2 text-gray-700 text-lg">
                {{ $presupuesto->categoria->nombre ?? 'Sin categoría' }} · {{ $meses[$presupuesto->mes] ?? $presupuesto->mes }} {{ $presupuesto->anio }}
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-semibold">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-amber-50 border border-amber-200 rounded-2xl px-6 py-4 mb-6">
            <p class="text-amber-800 font-semibold">
                Ejecutado en este período:
                <span class="font-bold">₡{{ number_format($presupuesto->ejecutado, 2) }}</span>
            </p>
            <p class="text-amber-700 text-sm mt-1">
                El monto presupuestado no debería quedar por debajo de lo ya ejecutado.
            </p>
        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">
            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-white text-xl font-bold">Datos de la línea</h2>
            </div>

            <form action="{{ route('presupuesto.update', [$presupuesto->anio, $presupuesto->mes, $presupuesto->categoria_gasto_id]) }}"
                  method="POST" class="p-8">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <label class="block mb-2 text-sm font-bold text-gray-700">Monto presupuestado</label>
                    <div class="flex items-center rounded-2xl border border-gray-300 bg-gray-50 overflow-hidden focus-within:border-[#b71c1c]">
                        <span class="px-4 text-gray-500 font-bold">₡</span>
                        <input type="number" step="0.01" min="0"
                               name="monto_presupuestado"
                               value="{{ old('monto_presupuestado', $presupuesto->monto_presupuestado) }}"
                               class="w-full px-2 py-4 bg-gray-50 outline-none"
                               required>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="block mb-2 text-sm font-bold text-gray-700">Descripción</label>
                    <textarea name="descripcion" rows="3"
                              class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">{{ old('descripcion', $presupuesto->descripcion) }}</textarea>
                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('presupuesto.show', [$presupuesto->anio, $presupuesto->mes]) }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#b71c1c] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Actualizar
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
