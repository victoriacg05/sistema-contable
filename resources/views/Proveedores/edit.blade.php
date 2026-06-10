<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <div class="flex items-center justify-between mb-8">
            <div>
                <span class="inline-block bg-[#c62828] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                    Proveedores
                </span>

                <h1 class="text-4xl font-extrabold text-[#1f2937]">
                    Editar Proveedor
                </h1>

                <p class="mt-2 text-gray-500 text-lg">
                    Actualiza los datos generales del proveedor
                </p>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 overflow-hidden">

            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-white text-xl font-bold">
                    Información del Proveedor
                </h2>
            </div>

            <form action="{{ route('proveedores.update', $proveedor) }}" method="POST" class="p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Identificación
                        </label>
                        <input type="text" name="identificacion" value="{{ old('identificacion', $proveedor->identificacion) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               required>
                        @error('identificacion')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Nombre
                        </label>
                        <input type="text" name="nombre" value="{{ old('nombre', $proveedor->nombre) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               required>
                        @error('nombre')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Empresa
                        </label>
                        <input type="text" name="empresa" value="{{ old('empresa', $proveedor->empresa) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               required>
                        @error('empresa')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Teléfono
                        </label>
                        <input type="text" name="telefono" value="{{ old('telefono', $proveedor->telefono) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               required>
                        @error('telefono')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Correo
                        </label>
                        <input type="email" name="correo" value="{{ old('correo', $proveedor->correo) }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               required>
                        @error('correo')
                            <p class="mt-2 text-sm text-red-600 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-3 mt-2">
                            <input type="checkbox" name="estado" value="1"
                                   class="rounded border-gray-300 text-[#c62828] focus:ring-[#c62828]"
                                   {{ $proveedor->estado ? 'checked' : '' }}>

                            <span class="font-semibold text-gray-700">
                                Proveedor activo
                            </span>
                        </label>
                    </div>

                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-100">

                    <a href="{{ route('proveedores.index') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#c62828] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Actualizar Proveedor
                    </button>

                </div>
            </form>
        </div>
    </div>
</x-app-layout>