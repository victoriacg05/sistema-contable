<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <span class="inline-block bg-[#b71c1c] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                    Proveedores
                </span>

                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Nuevo Proveedor
                </h1>

                <p class="mt-2 text-gray-500 text-lg">
                    Registra los datos generales del proveedor
                </p>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden">

            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-white text-xl font-bold">
                    Información del Proveedor
                </h2>
            </div>

            <form action="{{ route('proveedores.store') }}" method="POST" class="p-8">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Identificación
                        </label>
                        <input type="text" name="identificacion" value="{{ old('identificacion') }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               placeholder="Ej: 3-101-123456" required>
                        @error('identificacion')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Nombre
                        </label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}"
                               pattern="^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.]+$"
                               title="Solo se permiten letras y espacios"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               placeholder="Nombre del proveedor" required>
                        @error('nombre')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Empresa
                        </label>
                        <input type="text" name="empresa" value="{{ old('empresa') }}"
                               pattern="^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.&,\-]+$"
                               title="Solo se permiten letras, espacios y caracteres básicos"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               placeholder="Nombre de la empresa" required>
                        @error('empresa')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Teléfono
                        </label>
                        <input type="text" name="telefono" value="{{ old('telefono') }}"
                               pattern="^[245678]\d{3}-?\d{4}$"
                               title="El teléfono debe tener 8 dígitos y no puede iniciar con 0, 1, 3 o 9"
                               maxlength="9"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               placeholder="Ej: 2222-2222 o 8888-8888" required>
                        <p class="mt-1 text-xs text-gray-500">No puede iniciar con 0, 1, 3 o 9</p>
                        @error('telefono')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Correo
                        </label>
                        <input type="email" name="correo" value="{{ old('correo') }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               placeholder="proveedor@correo.com" required>
                        @error('correo')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-100">
                    <a href="{{ route('proveedores.index') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#b71c1c] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Guardar Proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>