<?php

namespace App\Http\Controllers;

use App\Models\Ingreso;
use App\Models\MetodoPago;
use App\Models\CuentaBancaria;
use App\Models\MovimientoBancario;
use App\Services\AsientoContableService;
use App\Services\BancoService;
use App\Services\IngresoAutomaticoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IngresoController extends Controller
{
    public function index()
    {
        $ingresos = Ingreso::with(['metodoPago', 'usuario'])
            ->orderByDesc('created_at')
            ->orderByDesc('fecha')
            ->orderByDesc('referencia_ingreso')
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
            'origen' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'monto' => 'required|numeric|min:1',
            'fecha' => 'required|date',
        ]);

        if (AsientoContableService::requiereCuentaBancaria((int) $request->metodo_pago_id)) {
            $request->validate([
                'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            ]);
        }

        DB::transaction(function () use ($request) {
            $referencia = 'ING-' . now()->format('YmdHis') . '-' . strtoupper(str()->random(6));

            Ingreso::create([
                'referencia_ingreso' => $referencia,
                'usuario_id' => Auth::id(),
                'metodo_pago_id' => $request->metodo_pago_id,
                'origen' => $request->origen,
                'descripcion' => $request->descripcion ?? '',
                'monto' => $request->monto,
                'fecha' => $request->fecha,
            ]);

            AsientoContableService::registrarIngreso(
                $request->fecha,
                $referencia,
                (float) $request->monto,
                (int) $request->metodo_pago_id,
                $request->origen
            );

            if (AsientoContableService::requiereCuentaBancaria((int) $request->metodo_pago_id)) {
                $cuentaBancaria = CuentaBancaria::lockForUpdate()
                    ->findOrFail($request->cuenta_bancaria_id);

                BancoService::acreditar(
                    $cuentaBancaria,
                    (float) $request->monto,
                    "Ingreso {$referencia}",
                    $referencia
                );
            }
        });

        return redirect()
            ->route('ingresos.index')
            ->with('success', 'Ingreso registrado correctamente.');
    }

    public function edit($referencia_ingreso, $fecha, $usuario_id)
    {
        $this->validarIngresoManual($referencia_ingreso);

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
        $this->validarIngresoManual($referencia_ingreso);

        $request->validate([
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'origen' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'monto' => 'required|numeric|min:1',
            'fecha' => 'required|date',
        ]);

        if (AsientoContableService::requiereCuentaBancaria((int) $request->metodo_pago_id)) {
            $request->validate([
                'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            ]);
        }

        DB::transaction(function () use ($request, $referencia_ingreso, $fecha, $usuario_id) {
            DB::table('ingresos')
                ->where('referencia_ingreso', $referencia_ingreso)
                ->where('fecha', $fecha)
                ->where('usuario_id', $usuario_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->revertirIngresoBancario($referencia_ingreso);

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

            AsientoContableService::registrarIngreso(
                $request->fecha,
                $referencia_ingreso,
                (float) $request->monto,
                (int) $request->metodo_pago_id,
                $request->origen
            );

            if (AsientoContableService::requiereCuentaBancaria((int) $request->metodo_pago_id)) {
                $cuentaBancaria = CuentaBancaria::lockForUpdate()
                    ->findOrFail($request->cuenta_bancaria_id);

                BancoService::acreditar(
                    $cuentaBancaria,
                    (float) $request->monto,
                    "Ingreso actualizado {$referencia_ingreso}",
                    $referencia_ingreso
                );
            }
        });

        return redirect()
            ->route('ingresos.index')
            ->with('success', 'Ingreso actualizado correctamente.');
    }

    public function destroy($referencia_ingreso, $fecha, $usuario_id)
    {
        $this->validarIngresoManual($referencia_ingreso);

        DB::transaction(function () use ($referencia_ingreso, $fecha, $usuario_id) {
            DB::table('ingresos')
                ->where('referencia_ingreso', $referencia_ingreso)
                ->where('fecha', $fecha)
                ->where('usuario_id', $usuario_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->revertirIngresoBancario($referencia_ingreso);

            AsientoContableService::revertir(
                now(),
                'INGRESO:' . $referencia_ingreso,
                'REVERSO-INGRESO:' . $referencia_ingreso,
                "Reversión de ingreso {$referencia_ingreso}"
            );

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

    private function revertirIngresoBancario(string $referencia): void
    {
        $movimiento = MovimientoBancario::where('referencia', $referencia)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (! $movimiento || $movimiento->tipo !== 'entrada') {
            return;
        }

        $cuentaBancaria = CuentaBancaria::lockForUpdate()
            ->findOrFail($movimiento->cuenta_bancaria_id);

        BancoService::debitar(
            $cuentaBancaria,
            (float) $movimiento->monto,
            "Reversión de ingreso {$referencia}",
            $referencia
        );
    }

    private function validarIngresoManual(string $referencia): void
    {
        if (IngresoAutomaticoService::esAutomatico($referencia)) {
            abort(403, 'Los ingresos automáticos se administran desde la operación que los generó.');
        }
    }
}