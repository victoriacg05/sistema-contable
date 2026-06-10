<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <div class="mb-8">
            <span class="inline-block bg-[#c62828] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                Administración
            </span>

            <h1 class="text-4xl font-extrabold text-[#1f2937]">
                Nuevo Usuario
            </h1>

            <p class="mt-2 text-gray-500 text-lg">
                Registrar nuevo usuario del sistema
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-semibold">
                <p class="font-bold mb-2">No se pudo guardar el usuario:</p>

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
                    Información del Usuario
                </h2>
            </div>

            <form action="{{ route('usuarios.store') }}" method="POST" class="p-8">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Nombre
                        </label>

                        <input type="text"
                               name="name"
                               value="{{ old('name') }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Correo
                        </label>

                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Contraseña
                        </label>

                        <input type="password"
                               name="password"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                               required>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Rol
                        </label>

                        <select name="rol_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#c62828] focus:ring-2 focus:ring-[#c62828]/20 outline-none transition"
                                required>
                            <option value="">Seleccione un rol</option>

                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('rol_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-100">
                    <a href="{{ route('usuarios.index') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#c62828] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Guardar Usuario
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>