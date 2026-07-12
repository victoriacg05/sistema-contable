<?php

namespace App\Http\Controllers;

use App\Models\Ingreso;
use App\Models\MetodoPago;
use App\Models\CuentaBancaria;
use App\Models\MovimientoBancario;
use App\Services\BancoService;
use App\Services\AsientoContableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IngresoController extends Controller
{
    public function index()
    {
        $ingresos = Ingreso::with(['metodoPago', 'usuario'])
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->get();

        return view('ingresos.index', compact('ingresos'));
    }

    public function create()
    {
        $metodosPago = MetodoPago::orderBy('nombre')->get();
        $cuentasBancarias = CuentaBancaria::where('estado', true)
            ->orderBy('banco_nombre')
            ->get();

        return view('ingresos.create', compact('metodosPago', 'cuentasBancarias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            'origen' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'monto' => 'required|numeric|min:1',
            'fecha' => 'required|date',
        ], [
            'cuenta_bancaria_id.required' => 'Seleccione la cuenta bancaria en la que ingresó el dinero.',
            'cuenta_bancaria_id.exists' => 'La cuenta bancaria seleccionada no es válida.',
        ]);

        DB::transaction(function () use ($request) {
            $referencia = 'ING-' . now()->format('YmdHis');

            Ingreso::create([
                'referencia_ingreso' => $referencia,
                'usuario_id' => Auth::id(),
                'metodo_pago_id' => $request->metodo_pago_id,
                'cuenta_bancaria_id' => $request->cuenta_bancaria_id,
                'origen' => $request->origen,
                'descripcion' => $request->descripcion ?? '',
                'monto' => $request->monto,
                'fecha' => $request->fecha,
            ]);

            // El ingreso entra a la tesorería: aumenta el saldo del banco y genera
            // el asiento Debe Bancos (1.1.2) / Haber Otros Ingresos (4.2).
            $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($request->cuenta_bancaria_id);

            BancoService::acreditar(
                $cuentaBancaria,
                (float) $request->monto,
                "Ingreso {$referencia} - {$request->origen}",
                $referencia
            );

            AsientoContableService::generar($request->fecha, "Ingreso {$referencia} - {$request->origen}", [
                ['codigo_cuenta' => '1.1.2', 'debe' => $request->monto, 'haber' => 0, 'descripcion' => "Ingreso en {$cuentaBancaria->banco_nombre}"],
                ['codigo_cuenta' => '4.2', 'debe' => 0, 'haber' => $request->monto, 'descripcion' => "Otros ingresos - {$request->origen}"],
            ]);
        });

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
        $cuentasBancarias = CuentaBancaria::where('estado', true)
            ->orderBy('banco_nombre')
            ->get();

        return view('ingresos.edit', compact('ingreso', 'metodosPago', 'cuentasBancarias'));
    }

    public function update(Request $request, $referencia_ingreso, $fecha, $usuario_id)
    {
        $request->validate([
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            'origen' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'monto' => 'required|numeric|min:1',
            'fecha' => 'required|date',
        ], [
            'cuenta_bancaria_id.required' => 'Seleccione la cuenta bancaria en la que ingresó el dinero.',
            'cuenta_bancaria_id.exists' => 'La cuenta bancaria seleccionada no es válida.',
        ]);

        DB::transaction(function () use ($request, $referencia_ingreso, $fecha, $usuario_id) {
            $ingreso = Ingreso::where('referencia_ingreso', $referencia_ingreso)
                ->where('fecha', $fecha)
                ->where('usuario_id', $usuario_id)
                ->firstOrFail();

            // Revertir el movimiento bancario y el asiento anteriores antes de re-aplicar.
            $this->revertirMovimientos($referencia_ingreso);

            DB::table('ingresos')
                ->where('referencia_ingreso', $referencia_ingreso)
                ->where('fecha', $fecha)
                ->where('usuario_id', $usuario_id)
                ->update([
                    'metodo_pago_id' => $request->metodo_pago_id,
                    'cuenta_bancaria_id' => $request->cuenta_bancaria_id,
                    'origen' => $request->origen,
                    'descripcion' => $request->descripcion ?? '',
                    'monto' => $request->monto,
                    'fecha' => $request->fecha,
                    'updated_at' => now(),
                ]);

            $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($request->cuenta_bancaria_id);

            BancoService::acreditar(
                $cuentaBancaria,
                (float) $request->monto,
                "Ingreso {$referencia_ingreso} - {$request->origen}",
                $referencia_ingreso
            );

            AsientoContableService::generar($request->fecha, "Ingreso {$referencia_ingreso} - {$request->origen}", [
                ['codigo_cuenta' => '1.1.2', 'debe' => $request->monto, 'haber' => 0, 'descripcion' => "Ingreso en {$cuentaBancaria->banco_nombre}"],
                ['codigo_cuenta' => '4.2', 'debe' => 0, 'haber' => $request->monto, 'descripcion' => "Otros ingresos - {$request->origen}"],
            ]);
        });

        return redirect()
            ->route('ingresos.index')
            ->with('success', 'Ingreso actualizado correctamente.');
    }

    public function destroy($referencia_ingreso, $fecha, $usuario_id)
    {
        DB::transaction(function () use ($referencia_ingreso, $fecha, $usuario_id) {
            $this->revertirMovimientos($referencia_ingreso);

            DB::table('ingresos')
                ->where('referencia_ingreso', $referencia_ingreso)
                ->where('fecha', $fecha)
                ->where('usuario_id', $usuario_id)
                ->delete();
        });

        return redirect()
            ->route('ingresos.index')
            ->with('success', 'Ingreso eliminado correctamente.');
    }

    private function revertirMovimientos(string $referencia): void
    {
        $movimientos = MovimientoBancario::where('referencia', $referencia)->get();

        foreach ($movimientos as $movimiento) {
            BancoService::revertir($movimiento);
            $movimiento->delete();
        }

        AsientoContableService::eliminarPorDescripcion($referencia);
    }
}