<x-guest-layout>

<div class="min-h-screen flex items-center justify-center bg-gray-100">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-10">

        <!-- Logo -->
        <div class="flex flex-col items-center mb-8">

            <img src="{{ asset('logo.png') }}"
                 alt="Logo"
                 class="w-28 mb-4">

            <h1 class="text-2xl font-bold text-[#2b2b2b]">
                Distribuidora Ipacaraí
            </h1>

            <p class="text-gray-700 mt-2">
                Crear cuenta
            </p>

        </div>

        <form method="POST" action="{{ route('register') }}">

            @csrf

            <!-- Name -->
            <div class="mb-5">

                <x-input-label for="name" :value="__('Nombre')" />

                <x-text-input
                    id="name"
                    class="block mt-2 w-full rounded-xl border-gray-300 focus:border-[#b71c1c] focus:ring-[#b71c1c]"
                    type="text"
                    name="name"
                    :value="old('name')"
                    required
                    autofocus />

                <x-input-error :messages="$errors->get('name')" class="mt-2" />

            </div>

            <!-- Email -->
            <div class="mb-5">

                <x-input-label for="email" :value="__('Correo')" />

                <x-text-input
                    id="email"
                    class="block mt-2 w-full rounded-xl border-gray-300 focus:border-[#b71c1c] focus:ring-[#b71c1c]"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required />

                <x-input-error :messages="$errors->get('email')" class="mt-2" />

            </div>

            <!-- Password -->
            <div class="mb-5">

                <x-input-label for="password" :value="__('Contraseña')" />

                <x-text-input
                    id="password"
                    class="block mt-2 w-full rounded-xl border-gray-300 focus:border-[#b71c1c] focus:ring-[#b71c1c]"
                    type="password"
                    name="password"
                    required />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />

            </div>

            <!-- Confirm Password -->
            <div class="mb-5">

                <x-input-label
                    for="password_confirmation"
                    :value="__('Confirmar contraseña')" />

                <x-text-input
                    id="password_confirmation"
                    class="block mt-2 w-full rounded-xl border-gray-300 focus:border-[#b71c1c] focus:ring-[#b71c1c]"
                    type="password"
                    name="password_confirmation"
                    required />

            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-between mt-8">

                <a href="{{ route('login') }}"
                   class="text-sm text-gray-600 hover:text-[#b71c1c]">

                    Ya tengo cuenta

                </a>

                <x-primary-button class="bg-[#b71c1c] hover:bg-red-700 rounded-xl px-6 py-3">

                    Registrarse

                </x-primary-button>

            </div>

        </form>

    </div>

</div>

</x-guest-layout>