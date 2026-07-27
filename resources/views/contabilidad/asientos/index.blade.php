<x-app-layout>
    <div class="mx-auto max-w-7xl">
        <div class="mb-8">
            <h1 class="text-4xl font-extrabold text-[#1f2937]">Asientos Contables</h1>
            <p class="mt-2 text-lg text-gray-700">
                Consulte el resumen y despliegue el detalle cuando necesite revisar las cuentas afectadas.
            </p>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-2xl border border-green-300 bg-green-100 px-6 py-4 font-semibold text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-5">
            @forelse($asientos as $asiento)
                <article class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-md">
                    <div class="flex flex-col gap-4 border-b border-gray-200 bg-gray-50 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-[#b71c1c]">
                                    {{ $asiento->origen }}
                                </span>
                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                                    {{ $asiento->estado_nombre }}
                                </span>
                                <span class="text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($asiento->fecha)->format('d/m/Y') }}
                                </span>
                            </div>

                            <h2 class="mt-3 text-lg font-extrabold text-gray-900">
                                {{ $asiento->descripcion_visible }}
                            </h2>
                            <p class="mt-1 font-mono text-xs text-gray-500">
                                {{ $asiento->numero_asiento }} · Registrado por {{ $asiento->usuario_nombre }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-right">
                                <p class="text-xs font-bold uppercase text-gray-500">Debe</p>
                                <p class="font-mono font-extrabold text-blue-700">₡{{ number_format($asiento->total_debe, 2) }}</p>
                            </div>
                            <div class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-right">
                                <p class="text-xs font-bold uppercase text-gray-500">Haber</p>
                                <p class="font-mono font-extrabold text-amber-700">₡{{ number_format($asiento->total_haber, 2) }}</p>
                            </div>
                            @if(abs((float) $asiento->total_debe - (float) $asiento->total_haber) <= 0.01)
                                <span class="rounded-full bg-green-100 px-3 py-2 text-xs font-bold text-green-700">
                                    Balanceado
                                </span>
                            @else
                                <span class="rounded-full bg-red-100 px-3 py-2 text-xs font-bold text-red-700">
                                    Requiere revisión
                                </span>
                            @endif
                        </div>
                    </div>

                    <details class="group">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 border-t border-gray-200 px-6 py-4 font-bold text-[#b71c1c] transition hover:bg-red-50">
                            <span>
                                <span class="group-open:hidden">Ver detalle del asiento</span>
                                <span class="hidden group-open:inline">Ocultar detalle del asiento</span>
                                <span class="ml-1 text-sm font-medium text-gray-500">
                                    ({{ $asiento->movimientos->count() }} movimientos)
                                </span>
                            </span>
                            <svg class="h-5 w-5 shrink-0 transition-transform group-open:rotate-180"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>

                        <div class="space-y-3 border-t border-gray-200 p-6">
                            @foreach($asiento->movimientos as $movimiento)
                                @php
                                    $esDebe = (float) $movimiento->debe > 0;
                                    $monto = $esDebe ? $movimiento->debe : $movimiento->haber;
                                    $grupoCuenta = explode('.', $movimiento->codigo_cuenta)[0];
                                    $naturalezaAcreedora = in_array($grupoCuenta, ['2', '3', '4'], true);
                                    $aumenta = $naturalezaAcreedora ? ! $esDebe : $esDebe;
                                @endphp

                                <div class="grid gap-4 rounded-2xl border px-5 py-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-center
                                    {{ $esDebe ? 'border-blue-200 bg-blue-50' : 'border-amber-200 bg-amber-50' }}">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-sm font-extrabold text-gray-800">
                                                {{ $movimiento->codigo_cuenta }}
                                            </span>
                                            <span class="font-bold text-gray-900">{{ $movimiento->cuenta_nombre }}</span>
                                        </div>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $movimiento->descripcion ?: $asiento->descripcion_visible }}
                                        </p>
                                    </div>

                                    <div class="text-left md:min-w-48 md:text-right">
                                        <div class="flex flex-wrap gap-2 md:justify-end">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold uppercase
                                                {{ $esDebe ? 'bg-blue-200 text-blue-800' : 'bg-amber-200 text-amber-800' }}">
                                                {{ $esDebe ? 'Movimiento al Debe' : 'Movimiento al Haber' }}
                                            </span>
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold
                                                {{ $aumenta ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800' }}">
                                                {{ $aumenta ? 'Aumenta la cuenta' : 'Disminuye la cuenta' }}
                                            </span>
                                        </div>
                                        <p class="mt-2 font-mono text-xl font-extrabold {{ $esDebe ? 'text-blue-800' : 'text-amber-800' }}">
                                            ₡{{ number_format($monto, 2) }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-gray-200 px-6 py-4 text-right">
                            <a href="{{ route('contabilidad.asientos.show', [$asiento->numero_asiento, $asiento->fecha]) }}"
                               class="font-bold text-[#b71c1c] hover:text-red-800">
                                Ver comprobante completo →
                            </a>
                        </div>
                    </details>
                </article>
            @empty
                <div class="rounded-3xl border border-gray-200 bg-white px-6 py-12 text-center text-gray-600">
                    No hay asientos registrados.
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $asientos->links() }}
        </div>
    </div>
</x-app-layout>
