<x-app-layout>
    <div class="max-w-3xl mx-auto">

        <h1 class="text-4xl font-extrabold text-[#1f2937] mb-8">Nueva Cuenta Contable</h1>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 p-10">
            <form method="POST" action="{{ route('contabilidad.cuentas.store') }}" x-data="{ esBanco: false }">
                @csrf

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Código de Cuenta</label>
                        <input type="text" name="codigo_cuenta" value="{{ old('codigo_cuenta') }}" required
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent"
                               placeholder="Ej: 1.1.5">
                        @error('codigo_cuenta') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tipo de Cuenta</label>
                        <select name="tipo_cuenta_contable_id" required
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent">
                            <option value="">Seleccione...</option>
                            @foreach($tipos as $tipo)
                                <option value="{{ $tipo->id }}" {{ old('tipo_cuenta_contable_id') == $tipo->id ? 'selected' : '' }}>
                                    {{ $tipo->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('tipo_cuenta_contable_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nombre</label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" required
                               x-on:input="esBanco = $event.target.value.toLowerCase().includes('banco')"
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent">
                        @error('nombre') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Campos de banco (visibles cuando el nombre incluye "banco") -->
                    <div x-show="esBanco" x-collapse class="bg-blue-50 border border-blue-200 rounded-xl p-5 space-y-4">
                        <p class="text-sm font-bold text-blue-700 mb-2">Configuración de Banco</p>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Entidad Bancaria</label>
                            <select name="banco_nombre"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent">
                                <option value="">Seleccione un banco...</option>
                                <option value="BAC" {{ old('banco_nombre') == 'BAC' ? 'selected' : '' }}>BAC San José</option>
                                <option value="BCR" {{ old('banco_nombre') == 'BCR' ? 'selected' : '' }}>Banco de Costa Rica (BCR)</option>
                                <option value="BN" {{ old('banco_nombre') == 'BN' ? 'selected' : '' }}>Banco Nacional de Costa Rica (BN)</option>
                                <option value="Scotiabank" {{ old('banco_nombre') == 'Scotiabank' ? 'selected' : '' }}>Scotiabank</option>
                                <option value="Davivienda" {{ old('banco_nombre') == 'Davivienda' ? 'selected' : '' }}>Davivienda</option>
                                <option value="Promerica" {{ old('banco_nombre') == 'Promerica' ? 'selected' : '' }}>Banco Promérica</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Moneda</label>
                            <select name="banco_moneda"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent">
                                <option value="">Seleccione moneda...</option>
                                <option value="CRC" {{ old('banco_moneda') == 'CRC' ? 'selected' : '' }}>Colones (₡)</option>
                                <option value="USD" {{ old('banco_moneda') == 'USD' ? 'selected' : '' }}>Dólares ($)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Descripción</label>
                        <textarea name="descripcion" rows="3"
                                  class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent">{{ old('descripcion') }}</textarea>
                    </div>
                </div>

                <div class="mt-8 flex justify-end space-x-4">
                    <a href="{{ route('contabilidad.cuentas.index') }}"
                       class="px-6 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="bg-[#b71c1c] hover:bg-red-800 text-white px-8 py-3 rounded-xl font-bold shadow-md transition">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
