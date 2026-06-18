<x-app-layout>
    <div class="max-w-3xl mx-auto">

        <h1 class="text-4xl font-extrabold text-[#1f2937] mb-8">Editar Cuenta Contable</h1>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-10">
            <form method="POST" action="{{ route('contabilidad.cuentas.update', $cuenta->codigo_cuenta) }}">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Código de Cuenta</label>
                        <input type="text" value="{{ $cuenta->codigo_cuenta }}" disabled
                               class="w-full border border-gray-200 rounded-xl px-4 py-3 bg-gray-100 text-gray-700">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tipo de Cuenta</label>
                        <select name="tipo_cuenta_contable_id" required
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent">
                            @foreach($tipos as $tipo)
                                <option value="{{ $tipo->id }}" {{ $cuenta->tipo_cuenta_contable_id == $tipo->id ? 'selected' : '' }}>
                                    {{ $tipo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nombre</label>
                        <input type="text" name="nombre" value="{{ old('nombre', $cuenta->nombre) }}" required
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Descripción</label>
                        <textarea name="descripcion" rows="3"
                                  class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent">{{ old('descripcion', $cuenta->descripcion) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Estado</label>
                        <select name="estado" required
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent">
                            <option value="1" {{ $cuenta->estado ? 'selected' : '' }}>Activa</option>
                            <option value="0" {{ !$cuenta->estado ? 'selected' : '' }}>Inactiva</option>
                        </select>
                    </div>
                </div>

                <div class="mt-8 flex justify-end space-x-4">
                    <a href="{{ route('contabilidad.cuentas.index') }}"
                       class="px-6 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="bg-[#b71c1c] hover:bg-red-700 text-white px-8 py-3 rounded-xl font-bold shadow-md transition">
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
