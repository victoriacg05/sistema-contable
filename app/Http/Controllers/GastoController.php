<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\CategoriaGasto;
use App\Models\MetodoPago;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GastoController extends Controller
{
    public function index()
    {
        $gastos = Gasto::with(['categoria', 'metodoPago', 'usuario'])
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->get();

        return view('gastos.index', compact('gastos'));
    }

    public function create()
    {
        $categorias = CategoriaGasto::orderBy('nombre')->get();
        $metodosPago = MetodoPago::orderBy('nombre')->get();

        return view('gastos.create', compact(
            'categorias',
            'metodosPago'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'categoria_gasto_id' => 'required|exists:categorias_gastos,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'descripcion' => 'nullable|string|max:500',
            'monto' => 'required|numeric|min:1',
            'fecha' => 'required|date',
        ]);

        if ($error = $this->validarPresupuesto(
            $request->categoria_gasto_id,
            $request->fecha,
            $request->monto
        )) {
            return back()->withInput()->withErrors(['monto' => $error]);
        }

        Gasto::create([
            'numero_comprobante' => 'GAS-' . now()->format('YmdHis'),
            'categoria_gasto_id' => $request->categoria_gasto_id,
            'usuario_id' => Auth::id(),
            'metodo_pago_id' => $request->metodo_pago_id,
            'descripcion' => $request->descripcion ?? '',
            'monto' => $request->monto,
            'fecha' => $request->fecha,
        ]);

        BitacoraService::registrar('crear', 'gastos', 'Gasto registrado por ₡' . $request->monto);

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
        $metodosPago = MetodoPago::orderBy('nombre')->get();

        return view('gastos.edit', compact(
            'gasto',
            'categorias',
            'metodosPago'
        ));
    }

    public function update(Request $request, $numero_comprobante, $categoria_gasto_id, $fecha)
    {
        $request->validate([
            'categoria_gasto_id' => 'required|exists:categorias_gastos,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'descripcion' => 'nullable|string|max:500',
            'monto' => 'required|numeric|min:1',
            'fecha' => 'required|date',
        ]);

        if ($error = $this->validarPresupuesto(
            $request->categoria_gasto_id,
            $request->fecha,
            $request->monto,
            $numero_comprobante
        )) {
            return back()->withInput()->withErrors(['monto' => $error]);
        }

        DB::table('gastos')
            ->where('numero_comprobante', $numero_comprobante)
            ->where('categoria_gasto_id', $categoria_gasto_id)
            ->where('fecha', $fecha)
            ->update([
                'categoria_gasto_id' => $request->categoria_gasto_id,
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

    /**
     * Valida el gasto contra el presupuesto de la categoría en el período de la fecha.
     * Devuelve un mensaje de error si supera el disponible, o null si es válido.
     * Si no existe una línea presupuestaria para la categoría/período, no se controla.
     */
    private function validarPresupuesto($categoriaId, $fecha, $monto, $excluirComprobante = null)
    {
        $anio = (int) date('Y', strtotime($fecha));
        $mes = (int) date('n', strtotime($fecha));

        $presupuesto = DB::table('presupuesto')
            ->where('categoria_gasto_id', $categoriaId)
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->first();

        if (!$presupuesto) {
            return null;
        }

        $ejecutado = DB::table('gastos')
            ->where('categoria_gasto_id', $categoriaId)
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->when($excluirComprobante, function ($q) use ($excluirComprobante) {
                $q->where('numero_comprobante', '!=', $excluirComprobante);
            })
            ->sum('monto');

        if (($ejecutado + $monto) > $presupuesto->monto_presupuestado) {
            return 'El monto ingresado supera el presupuesto disponible para esta categoría. No es posible registrar el gasto.';
        }

        return null;
    }
}