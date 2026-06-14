<?php

namespace App\Http\Controllers;

use App\Models\Ingreso;
use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IngresoController extends Controller
{
    public function index()
    {
        $ingresos = Ingreso::with(['metodoPago', 'usuario'])
            ->orderByDesc('fecha')
            ->get();

        return view('ingresos.index', compact('ingresos'));
    }

    public function create()
    {
        $metodosPago = MetodoPago::orderBy('nombre')->get();

        return view('ingresos.create', compact('metodosPago'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'origen' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'monto' => 'required|numeric|min:1',
            'fecha' => 'required|date',
        ]);

        Ingreso::create([
            'referencia_ingreso' => 'ING-' . now()->format('YmdHis'),
            'usuario_id' => Auth::id(),
            'metodo_pago_id' => $request->metodo_pago_id,
            'origen' => $request->origen,
            'descripcion' => $request->descripcion ?? '',
            'monto' => $request->monto,
            'fecha' => $request->fecha,
        ]);

        return redirect()
            ->route('ingresos.index')
            ->with('success', 'Ingreso registrado correctamente.');
    }

    public function edit($referencia_ingreso, $fecha, $usuario_id)
    {
        $ingreso = Ingreso::where('referencia_ingreso', $referencia_ingreso)
            ->where('fecha', $fecha)
            ->where('usuario_id', $usuario_id)
            ->firstOrFail();

        $metodosPago = MetodoPago::orderBy('nombre')->get();

        return view('ingresos.edit', compact('ingreso', 'metodosPago'));
    }

    public function update(Request $request, $referencia_ingreso, $fecha, $usuario_id)
    {
        $request->validate([
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'origen' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'monto' => 'required|numeric|min:1',
            'fecha' => 'required|date',
        ]);

        DB::table('ingresos')
            ->where('referencia_ingreso', $referencia_ingreso)
            ->where('fecha', $fecha)
            ->where('usuario_id', $usuario_id)
            ->update([
                'metodo_pago_id' => $request->metodo_pago_id,
                'origen' => $request->origen,
                'descripcion' => $request->descripcion ?? '',
                'monto' => $request->monto,
                'fecha' => $request->fecha,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('ingresos.index')
            ->with('success', 'Ingreso actualizado correctamente.');
    }

    public function destroy($referencia_ingreso, $fecha, $usuario_id)
    {
        DB::table('ingresos')
            ->where('referencia_ingreso', $referencia_ingreso)
            ->where('fecha', $fecha)
            ->where('usuario_id', $usuario_id)
            ->delete();

        return redirect()
            ->route('ingresos.index')
            ->with('success', 'Ingreso eliminado correctamente.');
    }
}