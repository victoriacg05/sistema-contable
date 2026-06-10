<x-guest-layout>

<div class="min-h-screen flex items-center justify-center bg-[#f4f5f7]">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl p-10">

        <!-- Logo -->
        <div class="flex flex-col items-center mb-8">

            <img src="{{ asset('logo.png') }}"
                 alt="Logo"
                 class="w-28 mb-4">

            <h1 class="text-2xl font-bold text-[#2b2b2b]">
                Distribuidora Ipacaraí
            </h1>

            <p class="text-gray-500 mt-2">
                Sistema Contable
            </p>

        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}">

            @csrf

            <!-- Email -->
            <div class="mb-5">

                <x-input-label for="email" :value="__('Correo')" />

                <x-text-input
                    id="email"
                    class="block mt-2 w-full rounded-xl border-gray-300 focus:border-[#c62828] focus:ring-[#c62828]"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autofocus />

                <x-input-error :messages="$errors->get('email')" class="mt-2" />

            </div>

            <!-- Password -->
            <div class="mb-5">

                <x-input-label for="password" :value="__('Contraseña')" />

                <x-text-input
                    id="password"
                    class="block mt-2 w-full rounded-xl border-gray-300 focus:border-[#c62828] focus:ring-[#c62828]"
                    type="password"
                    name="password"
                    required />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />

            </div>

            <!-- Remember -->
            <div class="block mt-4">

                <label for="remember_me" class="inline-flex items-center">

                    <input
                        id="remember_me"
                        type="checkbox"
                        class="rounded border-gray-300 text-[#c62828] shadow-sm focus:ring-[#c62828]"
                        name="remember">

                    <span class="ms-2 text-sm text-gray-600">
                        Recordarme
                    </span>

                </label>

            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-between mt-8">

                <a href="{{ route('register') }}"
                   class="text-sm text-gray-600 hover:text-[#c62828] transition">

                    Crear cuenta

                </a>

                <x-primary-button class="bg-[#c62828] hover:bg-red-700 rounded-xl px-6 py-3">

                    Ingresar

                </x-primary-button>

            </div>

        </form>

    </div>

</div>

</x-guest-layout>