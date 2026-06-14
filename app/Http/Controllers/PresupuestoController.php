<?php

namespace App\Http\Controllers;

use App\Models\Presupuesto;
use App\Models\CategoriaGasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PresupuestoController extends Controller
{
    public function index()
    {
        $presupuestos = Presupuesto::with('categoria')
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get()
            ->map(function ($presupuesto) {
                $gastoReal = DB::table('gastos')
                    ->where('categoria_gasto_id', $presupuesto->categoria_gasto_id)
                    ->whereYear('fecha', $presupuesto->anio)
                    ->whereMonth('fecha', $presupuesto->mes)
                    ->sum('monto');

                $presupuesto->gasto_real = $gastoReal;
                $presupuesto->diferencia = $presupuesto->monto_presupuestado - $gastoReal;

                return $presupuesto;
            });

        return view('presupuesto.index', compact('presupuestos'));
    }

    public function create()
    {
        $categorias = CategoriaGasto::orderBy('nombre')->get();

        return view('presupuesto.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer|min:2020|max:2100',
            'mes' => 'required|integer|min:1|max:12',
            'categoria_gasto_id' => 'required|exists:categorias_gastos,id',
            'monto_presupuestado' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string|max:500',
        ]);

        Presupuesto::create([
            'anio' => $request->anio,
            'mes' => $request->mes,
            'categoria_gasto_id' => $request->categoria_gasto_id,
            'monto_presupuestado' => $request->monto_presupuestado,
            'descripcion' => $request->descripcion ?? '',
        ]);

        return redirect()
            ->route('presupuesto.index')
            ->with('success', 'Presupuesto registrado correctamente.');
    }

    public function edit($anio, $mes, $categoria_gasto_id)
    {
        $presupuesto = Presupuesto::where('anio', $anio)
            ->where('mes', $mes)
            ->where('categoria_gasto_id', $categoria_gasto_id)
            ->firstOrFail();

        $categorias = CategoriaGasto::orderBy('nombre')->get();

        return view('presupuesto.edit', compact('presupuesto', 'categorias'));
    }

    public function update(Request $request, $anio, $mes, $categoria_gasto_id)
    {
        $request->validate([
            'anio' => 'required|integer|min:2020|max:2100',
            'mes' => 'required|integer|min:1|max:12',
            'categoria_gasto_id' => 'required|exists:categorias_gastos,id',
            'monto_presupuestado' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string|max:500',
        ]);

        DB::table('presupuesto')
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->where('categoria_gasto_id', $categoria_gasto_id)
            ->update([
                'anio' => $request->anio,
                'mes' => $request->mes,
                'categoria_gasto_id' => $request->categoria_gasto_id,
                'monto_presupuestado' => $request->monto_presupuestado,
                'descripcion' => $request->descripcion ?? '',
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('presupuesto.index')
            ->with('success', 'Presupuesto actualizado correctamente.');
    }

    public function destroy($anio, $mes, $categoria_gasto_id)
    {
        DB::table('presupuesto')
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->where('categoria_gasto_id', $categoria_gasto_id)
            ->delete();

        return redirect()
            ->route('presupuesto.index')
            ->with('success', 'Presupuesto eliminado correctamente.');
    }
}