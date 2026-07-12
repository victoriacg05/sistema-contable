<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\CategoriaGasto;
use App\Models\MetodoPago;
use App\Models\CuentaBancaria;
use App\Services\BitacoraService;
use App\Services\BancoService;
use App\Services\AsientoContableService;
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
        $cuentasBancarias = CuentaBancaria::where('estado', true)
            ->orderBy('banco_nombre')
            ->get();

        return view('gastos.create', compact(
            'categorias',
            'metodosPago',
            'cuentasBancarias'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'categoria_gasto_id' => 'required|exists:categorias_gastos,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            'descripcion' => 'nullable|string|max:500',
            'monto' => 'required|numeric|min:1',
            'fecha' => 'required|date',
        ], [
            'cuenta_bancaria_id.required' => 'Seleccione la cuenta bancaria desde la cual se pagará el gasto.',
            'cuenta_bancaria_id.exists' => 'La cuenta bancaria seleccionada no es válida.',
        ]);

        if ($error = $this->validarPresupuesto(
            $request->categoria_gasto_id,
            $request->fecha,
            $request->monto
        )) {
            return back()->withInput()->withErrors(['monto' => $error]);
        }

        $numeroComprobante = 'GAS-' . now()->format('YmdHis');

        DB::transaction(function () use ($request, $numeroComprobante) {
            Gasto::create([
                'numero_comprobante' => $numeroComprobante,
                'categoria_gasto_id' => $request->categoria_gasto_id,
                'usuario_id' => Auth::id(),
                'metodo_pago_id' => $request->metodo_pago_id,
                'cuenta_bancaria_id' => $request->cuenta_bancaria_id,
                'descripcion' => $request->descripcion ?? '',
                'monto' => $request->monto,
                'fecha' => $request->fecha,
            ]);

            // Tesorería: descuenta el saldo del banco y registra el movimiento.
            $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($request->cuenta_bancaria_id);

            BancoService::debitar(
                $cuentaBancaria,
                (float) $request->monto,
                "Gasto {$numeroComprobante}",
                $numeroComprobante
            );

            // Contabilidad: Debe cuenta de gasto / Haber Bancos.
            $codigoGasto = $this->codigoCuentaGasto($request->categoria_gasto_id);
            $codigoBancos = '1.1.2';

            AsientoContableService::generar($request->fecha, "Gasto {$numeroComprobante}", [
                ['codigo_cuenta' => $codigoGasto, 'debe' => $request->monto, 'haber' => 0, 'descripcion' => "Gasto {$numeroComprobante}"],
                ['codigo_cuenta' => $codigoBancos, 'debe' => 0, 'haber' => $request->monto, 'descripcion' => "Pago desde {$cuentaBancaria->banco_nombre}"],
            ]);
        });

        BitacoraService::registrar('crear', 'gastos', 'Gasto registrado por ₡' . $request->monto);

        return redirect()
            ->route('gastos.index')
            ->with('success', 'Gasto registrado. Se descontó el saldo del banco y se generó el asiento contable.');
    }

    public function edit($numero_comprobante, $categoria_gasto_id, $fecha)
    {
        $gasto = Gasto::where('numero_comprobante', $numero_comprobante)
            ->where('categoria_gasto_id', $categoria_gasto_id)
            ->where('fecha', $fecha)
            ->firstOrFail();

        $categorias = CategoriaGasto::orderBy('nombre')->get();
        $metodosPago = MetodoPago::orderBy('nombre')->get();
        $cuentasBancarias = CuentaBancaria::where('estado', true)
            ->orderBy('banco_nombre')
            ->get();

        return view('gastos.edit', compact(
            'gasto',
            'categorias',
            'metodosPago',
            'cuentasBancarias'
        ));
    }

    public function update(Request $request, $numero_comprobante, $categoria_gasto_id, $fecha)
    {
        $request->validate([
            'categoria_gasto_id' => 'required|exists:categorias_gastos,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            'descripcion' => 'nullable|string|max:500',
            'monto' => 'required|numeric|min:1',
            'fecha' => 'required|date',
        ], [
            'cuenta_bancaria_id.required' => 'Seleccione la cuenta bancaria desde la cual se pagará el gasto.',
            'cuenta_bancaria_id.exists' => 'La cuenta bancaria seleccionada no es válida.',
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
                'cuenta_bancaria_id' => $request->cuenta_bancaria_id,
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

    /**
     * Resuelve la cuenta contable de gasto según la clasificación de la
     * categoría: los gastos directos se cargan a Gastos de Ventas y los
     * indirectos a Gastos Administrativos.
     */
    private function codigoCuentaGasto($categoriaId): string
    {
        $clasificacion = DB::table('categorias_gastos')
            ->where('id', $categoriaId)
            ->value('clasificacion');

        return $clasificacion === 'Directo' ? '5.3' : '5.2';
    }
}