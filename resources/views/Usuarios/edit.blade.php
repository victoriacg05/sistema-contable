<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <div class="mb-8">
            <span class="inline-block bg-[#b71c1c] text-white px-6 py-3 rounded-2xl font-bold shadow-md mb-5">
                Administración
            </span>

            <h1 class="text-4xl font-extrabold text-[#1f2937]">
                Editar Usuario
            </h1>

            <p class="mt-2 text-gray-700 text-lg">
                Actualiza la información del usuario
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-6 py-4 rounded-2xl font-semibold">
                <p class="font-bold mb-2">No se pudo actualizar el usuario:</p>

                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-200 overflow-hidden">

            <div class="bg-[#2b2b2b] px-8 py-5">
                <h2 class="text-white text-xl font-bold">
                    Información del Usuario
                </h2>
            </div>

            <form action="{{ route('usuarios.update', $usuario) }}" method="POST" class="p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Nombre
                        </label>

                        <input type="text"
                               name="name"
                               value="{{ old('name', $usuario->name) }}"
                               pattern="^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$"
                               title="Solo se permiten letras y espacios"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>
                        @error('name')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Correo
                        </label>

                        <input type="email"
                               name="email"
                               value="{{ old('email', $usuario->email) }}"
                               pattern="^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$"
                               title="Ingrese un correo válido (ejemplo@dominio.com)"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               required>
                        @error('email')
                            <p class="mt-2 text-sm text-red-800 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Nueva contraseña
                        </label>

                        <input type="password"
                               name="password"
                               class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                               placeholder="Dejar en blanco si no desea cambiarla">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">
                            Rol
                        </label>

                        <select name="rol_id"
                                class="w-full px-5 py-4 rounded-2xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-[#b71c1c] focus:ring-2 focus:ring-[#b71c1c]/20 outline-none transition"
                                required>
                            <option value="">Seleccione un rol</option>

                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('rol_id', $usuario->rol_id) == $role->id ? 'selected' : '' }}>
                                    {{ $role->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="inline-flex items-center gap-3 mt-2">
                            <input type="checkbox"
                                   name="estado"
                                   value="1"
                                   class="rounded border-gray-300 text-[#b71c1c] focus:ring-[#b71c1c]"
                                   {{ $usuario->estado ? 'checked' : '' }}>

                            <span class="font-semibold text-gray-700">
                                Usuario activo
                            </span>
                        </label>
                    </div>

                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('usuarios.index') }}"
                       class="px-7 py-3 rounded-2xl bg-gray-100 text-gray-700 font-bold hover:bg-gray-200 transition">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="px-8 py-3 rounded-2xl bg-[#b71c1c] text-white font-bold hover:bg-red-700 transition shadow-md">
                        Actualizar Usuario
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>