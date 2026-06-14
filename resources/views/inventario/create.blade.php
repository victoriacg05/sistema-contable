<x-app-layout>
    <div class="max-w-3xl mx-auto">

        <h1 class="text-4xl font-extrabold text-[#1f2937] mb-8">Nuevo Movimiento de Inventario</h1>

        @if($errors->any())
            <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-semibold">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 p-10">
            <form method="POST" action="{{ route('inventario.store') }}">
                @csrf

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Producto</label>
                        <select name="producto_id" required
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c62828] focus:border-transparent">
                            <option value="">Seleccione producto...</option>
                            @foreach($productos as $producto)
                                <option value="{{ $producto->id }}" {{ old('producto_id') == $producto->id ? 'selected' : '' }}>
                                    {{ $producto->nombre }} (Stock: {{ $producto->stock }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tipo de Movimiento</label>
                        <select name="tipo_movimiento_inventario_id" required
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c62828] focus:border-transparent">
                            <option value="">Seleccione tipo...</option>
                            @foreach($tipos as $tipo)
                                <option value="{{ $tipo->id }}" {{ old('tipo_movimiento_inventario_id') == $tipo->id ? 'selected' : '' }}>
                                    {{ $tipo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Cantidad</label>
                        <input type="number" name="cantidad" value="{{ old('cantidad') }}" min="1" required
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c62828] focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Fecha</label>
                        <input type="date" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}" required
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c62828] focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Descripción</label>
                        <textarea name="descripcion" rows="3"
                                  class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c62828] focus:border-transparent">{{ old('descripcion') }}</textarea>
                    </div>
                </div>

                <div class="mt-8 flex justify-end space-x-4">
                    <a href="{{ route('inventario.index') }}"
                       class="px-6 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="bg-[#c62828] hover:bg-red-700 text-white px-8 py-3 rounded-xl font-bold shadow-md transition">
                        Registrar Movimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
