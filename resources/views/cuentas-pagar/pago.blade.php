<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <div class="mb-8">
            <span class="inline-block bg-[#b71c1c] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                Cuentas por Pagar
            </span>

            <h1 class="text-4xl font-extrabold text-[#1f2937]">
                Registrar Pago
            </h1>

            <p class="mt-2 text-gray-500 text-lg">
                Compra {{ $cuenta->numero_compra }} - {{ $cuenta->proveedor->nombre ?? 'Sin proveedor' }}
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-semibold">
                <p class="font-bold mb-2">No se pudo registrar el pago:</p>

                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden">

            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-white text-xl font-bold">
                    Información del Pago
                </h2>
            </div>

            <form action="{{ route('cuentas-pagar.pago.store', [$cuenta->numero_compra, $cuenta->proveedor_id]) }}"
                  method="POST"
                  class="p-8">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Saldo pendiente
                        </label>

                        <input type="text"
                               value="₡{{ number_format($cuenta->saldo_pendiente, 2) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-100"
                               readonly>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Monto pagado
                        </label>

                        <input type="number"
                               step="0.01"
                               name="monto_pagado"
                               value="{{ old('monto_pagado') }}"
                               min="1"
                               max="{{ $cuenta->saldo_pendiente }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Método de pago
                        </label>

                        <select name="metodo_pago_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                required>
                            <option value="">Seleccione método de pago</option>

                            @foreach($metodosPago as $metodo)
                                <option value="{{ $metodo->id }}" {{ old('metodo_pago_id') == $metodo->id ? 'selected' : '' }}>
                                    {{ $metodo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Observación
                        </label>

                        <textarea name="observacion"
                                  rows="3"
                                  class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition">{{ old('observacion') }}</textarea>
                    </div>

                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-100">
                    <a href="{{ route('cuentas-pagar.index') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#b71c1c] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Guardar Pago
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>