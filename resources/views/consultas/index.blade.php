<x-app-layout>
    <div class="max-w-5xl mx-auto">

        <h1 class="text-4xl font-extrabold text-[#1f2937] mb-2">Consultas</h1>
        <p class="text-gray-500 text-lg mb-8">Búsqueda avanzada en los módulos del sistema</p>

        <div class="bg-white rounded-[2rem] shadow-lg border border-gray-100 p-10">
            <form method="GET" action="{{ route('consultas.buscar') }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Módulo</label>
                        <select name="modulo" required
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c62828] focus:border-transparent">
                            <option value="">Seleccione módulo...</option>
                            <option value="facturas">Facturas</option>
                            <option value="compras">Compras</option>
                            <option value="ingresos">Ingresos</option>
                            <option value="gastos">Gastos</option>
                            <option value="clientes">Clientes</option>
                            <option value="proveedores">Proveedores</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Término de búsqueda</label>
                        <input type="text" name="termino"
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c62828] focus:border-transparent"
                               placeholder="Nombre, número, referencia...">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Fecha desde</label>
                        <input type="date" name="fecha_desde"
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c62828] focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Fecha hasta</label>
                        <input type="date" name="fecha_hasta"
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-[#c62828] focus:border-transparent">
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit"
                            class="bg-[#c62828] hover:bg-red-700 text-white px-8 py-3 rounded-xl font-bold shadow-md transition">
                        Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
