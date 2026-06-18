<x-app-layout>
    <div class="max-w-3xl mx-auto">
        <h1 class="text-4xl font-extrabold text-[#1f2937] mb-8">Nuevo Método de Pago</h1>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 p-10">
            <form method="POST" action="{{ route('metodos-pago.store') }}">
                @csrf
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nombre</label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" required
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent">
                        @error('nombre') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Descripción</label>
                        <textarea name="descripcion" rows="3"
                                  class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#b71c1c] focus:border-transparent">{{ old('descripcion') }}</textarea>
                    </div>
                </div>
                <div class="mt-8 flex justify-end space-x-4">
                    <a href="{{ route('metodos-pago.index') }}" class="px-6 py-3 border border-gray-300 rounded-xl font-semibold text-gray-700 hover:bg-gray-50 transition">Cancelar</a>
                    <button type="submit" class="bg-[#b71c1c] hover:bg-red-700 text-white px-8 py-3 rounded-xl font-bold shadow-md transition">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
