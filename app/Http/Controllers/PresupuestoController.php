<?php

namespace App\Http\Controllers;

use App\Models\Presupuesto;
use App\Models\CategoriaGasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PresupuestoController extends Controller
{
    /**
     * Listado de presupuestos agrupados por período (mes/año) con sus
     * indicadores de ejecución: asignado, ejecutado, disponible y % utilizado.
     */
    public function index()
    {
        $periodos = DB::table('presupuesto')
            ->select(
                'anio',
                'mes',
                DB::raw('SUM(monto_presupuestado) as total_presupuestado'),
                DB::raw('COUNT(*) as lineas'),
                DB::raw('MAX(created_at) as ultimo_registro')
            )
            ->groupBy('anio', 'mes')
            ->orderByDesc('ultimo_registro')
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get()
            ->map(function ($periodo) {
                $ejecutado = $this->ejecutadoDelPeriodo($periodo->anio, $periodo->mes);

                $periodo->total_ejecutado = $ejecutado;
                $periodo->disponible = $periodo->total_presupuestado - $ejecutado;
                $periodo->porcentaje = $periodo->total_presupuestado > 0
                    ? round(($ejecutado / $periodo->total_presupuestado) * 100, 1)
                    : 0;

                return $periodo;
            });

        return view('presupuesto.index', compact('periodos'));
    }

    /**
     * Formulario para crear un presupuesto completo (una línea por categoría).
     * Admite copiar los montos de un período anterior con ?desde_anio&desde_mes.
     */
    public function create(Request $request)
    {
        $categorias = CategoriaGasto::orderBy('nombre')->get();

        // Períodos existentes para el selector "Copiar desde".
        $periodosExistentes = DB::table('presupuesto')
            ->select('anio', 'mes')
            ->distinct()
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();

        // Montos precargados al copiar un período anterior.
        $montos = [];
        $copiadoDe = null;

        if ($request->filled('desde_anio') && $request->filled('desde_mes')) {
            $montos = DB::table('presupuesto')
                ->where('anio', $request->desde_anio)
                ->where('mes', $request->desde_mes)
                ->pluck('monto_presupuestado', 'categoria_gasto_id')
                ->toArray();

            $copiadoDe = [
                'anio' => (int) $request->desde_anio,
                'mes' => (int) $request->desde_mes,
            ];
        }

        return view('presupuesto.create', compact(
            'categorias',
            'periodosExistentes',
            'montos',
            'copiadoDe'
        ));
    }

    /**
     * Guarda todas las líneas de un presupuesto para un período.
     */
    public function store(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer|min:2020|max:2100',
            'mes' => 'required|integer|min:1|max:12',
            'montos' => 'required|array',
            'montos.*' => 'nullable|numeric|min:0',
        ], [
            'montos.required' => 'Debe asignar al menos un monto a una categoría.',
        ]);

        $lineas = 0;

        foreach ($request->montos as $categoriaId => $monto) {
            if ($monto === null || $monto === '' || (float) $monto <= 0) {
                continue;
            }

            DB::table('presupuesto')->updateOrInsert(
                [
                    'anio' => $request->anio,
                    'mes' => $request->mes,
                    'categoria_gasto_id' => $categoriaId,
                ],
                [
                    'monto_presupuestado' => $monto,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $lineas++;
        }

        if ($lineas === 0) {
            return back()
                ->withInput()
                ->withErrors(['montos' => 'Debe asignar un monto mayor a cero a al menos una categoría.']);
        }

        return redirect()
            ->route('presupuesto.show', [$request->anio, $request->mes])
            ->with('success', 'Presupuesto guardado correctamente.');
    }

    /**
     * Detalle de un período con los indicadores por categoría en tiempo real.
     */
    public function show($anio, $mes)
    {
        $lineas = Presupuesto::with('categoria')
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->orderByDesc('created_at')
            ->orderByDesc('categoria_gasto_id')
            ->get()
            ->map(function ($linea) use ($anio, $mes) {
                $ejecutado = $this->ejecutadoCategoria($linea->categoria_gasto_id, $anio, $mes);

                $linea->ejecutado = $ejecutado;
                $linea->disponible = $linea->monto_presupuestado - $ejecutado;
                $linea->porcentaje = $linea->monto_presupuestado > 0
                    ? round(($ejecutado / $linea->monto_presupuestado) * 100, 1)
                    : 0;
                return $linea;
            })
            ->values();

        if ($lineas->isEmpty()) {
            abort(404);
        }

        $totales = [
            'presupuestado' => $lineas->sum('monto_presupuestado'),
            'ejecutado' => $lineas->sum('ejecutado'),
        ];
        $totales['disponible'] = $totales['presupuestado'] - $totales['ejecutado'];
        $totales['porcentaje'] = $totales['presupuestado'] > 0
            ? round(($totales['ejecutado'] / $totales['presupuestado']) * 100, 1)
            : 0;

        return view('presupuesto.show', compact('lineas', 'totales', 'anio', 'mes'));
    }

    /**
     * Edición individual de una línea presupuestaria (monto y descripción).
     */
    public function edit($anio, $mes, $categoria_gasto_id)
    {
        $presupuesto = Presupuesto::with('categoria')
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->where('categoria_gasto_id', $categoria_gasto_id)
            ->firstOrFail();

        $presupuesto->ejecutado = $this->ejecutadoCategoria($categoria_gasto_id, $anio, $mes);

        return view('presupuesto.edit', compact('presupuesto'));
    }

    public function update(Request $request, $anio, $mes, $categoria_gasto_id)
    {
        $request->validate([
            'monto_presupuestado' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string|max:500',
        ]);

        DB::table('presupuesto')
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->where('categoria_gasto_id', $categoria_gasto_id)
            ->update([
                'monto_presupuestado' => $request->monto_presupuestado,
                'descripcion' => $request->descripcion ?? '',
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('presupuesto.show', [$anio, $mes])
            ->with('success', 'Línea presupuestaria actualizada correctamente.');
    }

    /**
     * Elimina una línea individual del presupuesto.
     */
    public function destroy($anio, $mes, $categoria_gasto_id)
    {
        DB::table('presupuesto')
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->where('categoria_gasto_id', $categoria_gasto_id)
            ->delete();

        $quedan = DB::table('presupuesto')
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->exists();

        if (! $quedan) {
            return redirect()
                ->route('presupuesto.index')
                ->with('success', 'Presupuesto eliminado correctamente.');
        }

        return redirect()
            ->route('presupuesto.show', [$anio, $mes])
            ->with('success', 'Línea presupuestaria eliminada correctamente.');
    }

    /**
     * Elimina todo el presupuesto de un período.
     */
    public function destroyPeriodo($anio, $mes)
    {
        DB::table('presupuesto')
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->delete();

        return redirect()
            ->route('presupuesto.index')
            ->with('success', 'Presupuesto eliminado correctamente.');
    }

    /**
     * Devuelve en JSON la disponibilidad presupuestaria de una categoría en un
     * período dado. Lo consume el formulario de gastos en tiempo real.
     */
    public function disponible(Request $request)
    {
        $request->validate([
            'categoria_gasto_id' => 'required|integer',
            'anio' => 'required|integer',
            'mes' => 'required|integer|min:1|max:12',
        ]);

        $linea = DB::table('presupuesto')
            ->where('anio', $request->anio)
            ->where('mes', $request->mes)
            ->where('categoria_gasto_id', $request->categoria_gasto_id)
            ->first();

        if (! $linea) {
            return response()->json(['tiene_presupuesto' => false]);
        }

        $ejecutado = $this->ejecutadoCategoria(
            $request->categoria_gasto_id,
            $request->anio,
            $request->mes
        );

        return response()->json([
            'tiene_presupuesto' => true,
            'presupuestado' => (float) $linea->monto_presupuestado,
            'ejecutado' => (float) $ejecutado,
            'disponible' => (float) $linea->monto_presupuestado - (float) $ejecutado,
        ]);
    }

    /**
     * Suma de gastos de una categoría en un período (mes/año).
     */
    private function ejecutadoCategoria($categoriaId, $anio, $mes, $excluirComprobante = null): float
    {
        $query = DB::table('gastos')
            ->where('categoria_gasto_id', $categoriaId)
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes);

        if ($excluirComprobante !== null) {
            $query->where('numero_comprobante', '!=', $excluirComprobante);
        }

        return (float) $query->sum('monto');
    }

    /**
     * Suma de gastos del período pero solo para las categorías presupuestadas.
     */
    private function ejecutadoDelPeriodo($anio, $mes): float
    {
        return (float) DB::table('gastos')
            ->join('presupuesto', function ($join) use ($anio, $mes) {
                $join->on('gastos.categoria_gasto_id', '=', 'presupuesto.categoria_gasto_id')
                    ->where('presupuesto.anio', '=', $anio)
                    ->where('presupuesto.mes', '=', $mes);
            })
            ->whereYear('gastos.fecha', $anio)
            ->whereMonth('gastos.fecha', $mes)
            ->sum('gastos.monto');
    }
}
