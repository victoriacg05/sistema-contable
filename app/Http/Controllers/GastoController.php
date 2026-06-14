<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\CategoriaGasto;
use App\Models\TipoGasto;
use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GastoController extends Controller
{
    public function index()
    {
        $gastos = Gasto::with(['categoria', 'tipoGasto', 'metodoPago', 'usuario'])
            ->orderByDesc('fecha')
            ->get();

        return view('gastos.index', compact('gastos'));
    }

    public function create()
    {
        $categorias = CategoriaGasto::orderBy('nombre')->get();
        $tiposGasto = TipoGasto::orderBy('nombre')->get();
        $metodosPago = MetodoPago::orderBy('nombre')->get();

        return view('gastos.create', compact(
            'categorias',
            'tiposGasto',
            'metodosPago'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'categoria_gasto_id' => 'required|exists:categorias_gastos,id',
            'tipo_gasto_id' => 'required|exists:tipos_gasto,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'descripcion' => 'nullable|string|max:500',
            'monto' => 'required|numeric|min:1',
            'fecha' => 'required|date',
        ]);

        Gasto::create([
            'numero_comprobante' => 'GAS-' . now()->format('YmdHis'),
            'categoria_gasto_id' => $request->categoria_gasto_id,
            'tipo_gasto_id' => $request->tipo_gasto_id,
            'usuario_id' => Auth::id(),
            'metodo_pago_id' => $request->metodo_pago_id,
            'descripcion' => $request->descripcion ?? '',
            'monto' => $request->monto,
            'fecha' => $request->fecha,
        ]);

        return redirect()
            ->route('gastos.index')
            ->with('success', 'Gasto registrado correctamente.');
    }

    public function edit($numero_comprobante, $categoria_gasto_id, $fecha)
    {
        $gasto = Gasto::where('numero_comprobante', $numero_comprobante)
            ->where('categoria_gasto_id', $categoria_gasto_id)
            ->where('fecha', $fecha)
            ->firstOrFail();

        $categorias = CategoriaGasto::orderBy('nombre')->get();
        $tiposGasto = TipoGasto::orderBy('nombre')->get();
        $metodosPago = MetodoPago::orderBy('nombre')->get();

        return view('gastos.edit', compact(
            'gasto',
            'categorias',
            'tiposGasto',
            'metodosPago'
        ));
    }

    public function update(Request $request, $numero_comprobante, $categoria_gasto_id, $fecha)
    {
        $request->validate([
            'categoria_gasto_id' => 'required|exists:categorias_gastos,id',
            'tipo_gasto_id' => 'required|exists:tipos_gasto,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'descripcion' => 'nullable|string|max:500',
            'monto' => 'required|numeric|min:1',
            'fecha' => 'required|date',
        ]);

        DB::table('gastos')
            ->where('numero_comprobante', $numero_comprobante)
            ->where('categoria_gasto_id', $categoria_gasto_id)
            ->where('fecha', $fecha)
            ->update([
                'categoria_gasto_id' => $request->categoria_gasto_id,
                'tipo_gasto_id' => $request->tipo_gasto_id,
                'metodo_pago_id' => $request->metodo_pago_id,
                'descripcion' => $request->descripcion ?? '',
                'monto' => $request->monto,
                'fecha' => $request->fecha,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('gastos.index')
            ->with('success', 'Gasto actualizado correctamente.');
    }

    public function destroy($numero_comprobante, $categoria_gasto_id, $fecha)
    {
        DB::table('gastos')
            ->where('numero_comprobante', $numero_comprobante)
            ->where('categoria_gasto_id', $categoria_gasto_id)
            ->where('fecha', $fecha)
            ->delete();

        return redirect()
            ->route('gastos.index')
            ->with('success', 'Gasto eliminado correctamente.');
    }
}