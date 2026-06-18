<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\BitacoraService;

class ContabilidadController extends Controller
{
    public function indexCuentas()
    {
        $cuentas = DB::table('catalogo_cuentas')
            ->join('tipos_cuenta_contable', 'catalogo_cuentas.tipo_cuenta_contable_id', '=', 'tipos_cuenta_contable.id')
            ->select('catalogo_cuentas.*', 'tipos_cuenta_contable.nombre as tipo_nombre')
            ->orderBy('catalogo_cuentas.codigo_cuenta')
            ->get();

        return view('contabilidad.cuentas.index', compact('cuentas'));
    }

    public function createCuenta()
    {
        $tipos = DB::table('tipos_cuenta_contable')->orderBy('nombre')->get();

        return view('contabilidad.cuentas.create', compact('tipos'));
    }

    public function storeCuenta(Request $request)
    {
        $request->validate([
            'codigo_cuenta' => 'required|string|max:255|unique:catalogo_cuentas,codigo_cuenta',
            'tipo_cuenta_contable_id' => 'required|exists:tipos_cuenta_contable,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'banco_nombre' => 'nullable|string|max:100',
            'banco_moneda' => 'nullable|string|in:CRC,USD',
        ]);

        $descripcion = $request->descripcion ?? '';
        if ($request->filled('banco_nombre')) {
            $moneda = $request->banco_moneda === 'USD' ? 'Dólares ($)' : 'Colones (₡)';
            $descripcion = "Banco: {$request->banco_nombre} | Moneda: {$moneda}" . ($descripcion ? " | {$descripcion}" : '');
        }

        DB::table('catalogo_cuentas')->insert([
            'codigo_cuenta' => $request->codigo_cuenta,
            'tipo_cuenta_contable_id' => $request->tipo_cuenta_contable_id,
            'nombre' => $request->nombre,
            'descripcion' => $descripcion,
            'estado' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        BitacoraService::registrar('crear', 'catalogo_cuentas', 'Cuenta creada: ' . $request->codigo_cuenta);

        return redirect()->route('contabilidad.cuentas.index')
            ->with('success', 'Cuenta contable creada correctamente.');
    }

    public function editCuenta(string $codigoCuenta)
    {
        $cuenta = DB::table('catalogo_cuentas')
            ->where('codigo_cuenta', $codigoCuenta)
            ->firstOrFail();

        $tipos = DB::table('tipos_cuenta_contable')->orderBy('nombre')->get();

        return view('contabilidad.cuentas.edit', compact('cuenta', 'tipos'));
    }

    public function updateCuenta(Request $request, string $codigoCuenta)
    {
        $request->validate([
            'tipo_cuenta_contable_id' => 'required|exists:tipos_cuenta_contable,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'estado' => 'required|boolean',
        ]);

        DB::table('catalogo_cuentas')
            ->where('codigo_cuenta', $codigoCuenta)
            ->update([
                'tipo_cuenta_contable_id' => $request->tipo_cuenta_contable_id,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion ?? '',
                'estado' => $request->estado,
                'updated_at' => now(),
            ]);

        BitacoraService::registrar('editar', 'catalogo_cuentas', 'Cuenta editada: ' . $codigoCuenta);

        return redirect()->route('contabilidad.cuentas.index')
            ->with('success', 'Cuenta contable actualizada correctamente.');
    }

    public function indexAsientos()
    {
        $asientos = DB::table('asientos_contables')
            ->join('users', 'asientos_contables.usuario_id', '=', 'users.id')
            ->join('estados', 'asientos_contables.estado_id', '=', 'estados.id')
            ->select('asientos_contables.*', 'users.name as usuario_nombre', 'estados.nombre as estado_nombre')
            ->orderByDesc('asientos_contables.fecha')
            ->get();

        return view('contabilidad.asientos.index', compact('asientos'));
    }

    public function createAsiento()
    {
        $cuentas = DB::table('catalogo_cuentas')
            ->where('estado', true)
            ->orderBy('codigo_cuenta')
            ->get();

        $estados = DB::table('estados')->orderBy('nombre')->get();

        return view('contabilidad.asientos.create', compact('cuentas', 'estados'));
    }

    public function storeAsiento(Request $request)
    {
        $request->validate([
            'descripcion' => 'nullable|string|max:500',
            'fecha' => 'required|date',
            'estado_id' => 'required|exists:estados,id',
            'lineas' => 'required|array|min:2',
            'lineas.*.codigo_cuenta' => 'required|exists:catalogo_cuentas,codigo_cuenta',
            'lineas.*.debe' => 'required|numeric|min:0',
            'lineas.*.haber' => 'required|numeric|min:0',
        ]);

        $totalDebe = collect($request->lineas)->sum('debe');
        $totalHaber = collect($request->lineas)->sum('haber');

        if (abs($totalDebe - $totalHaber) > 0.01) {
            return back()->withErrors(['lineas' => 'El total del debe debe ser igual al total del haber.'])->withInput();
        }

        $numeroAsiento = 'ASI-' . now()->format('YmdHis');

        DB::table('asientos_contables')->insert([
            'numero_asiento' => $numeroAsiento,
            'usuario_id' => Auth::id(),
            'fecha' => $request->fecha,
            'descripcion' => $request->descripcion ?? '',
            'total_debe' => $totalDebe,
            'total_haber' => $totalHaber,
            'estado_id' => $request->estado_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($request->lineas as $linea) {
            if ($linea['debe'] > 0 || $linea['haber'] > 0) {
                DB::table('detalle_asientos_contables')->insert([
                    'numero_asiento' => $numeroAsiento,
                    'fecha_asiento' => $request->fecha,
                    'codigo_cuenta' => $linea['codigo_cuenta'],
                    'debe' => $linea['debe'],
                    'haber' => $linea['haber'],
                    'descripcion' => $linea['descripcion'] ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        BitacoraService::registrar('crear', 'asientos_contables', 'Asiento creado: ' . $numeroAsiento);

        return redirect()->route('contabilidad.asientos.index')
            ->with('success', 'Asiento contable registrado correctamente.');
    }

    public function showAsiento(string $numeroAsiento, string $fecha)
    {
        $asiento = DB::table('asientos_contables')
            ->join('users', 'asientos_contables.usuario_id', '=', 'users.id')
            ->join('estados', 'asientos_contables.estado_id', '=', 'estados.id')
            ->where('asientos_contables.numero_asiento', $numeroAsiento)
            ->where('asientos_contables.fecha', $fecha)
            ->select('asientos_contables.*', 'users.name as usuario_nombre', 'estados.nombre as estado_nombre')
            ->firstOrFail();

        $detalles = DB::table('detalle_asientos_contables')
            ->join('catalogo_cuentas', 'detalle_asientos_contables.codigo_cuenta', '=', 'catalogo_cuentas.codigo_cuenta')
            ->where('detalle_asientos_contables.numero_asiento', $numeroAsiento)
            ->where('detalle_asientos_contables.fecha_asiento', $fecha)
            ->select('detalle_asientos_contables.*', 'catalogo_cuentas.nombre as cuenta_nombre')
            ->get();

        return view('contabilidad.asientos.show', compact('asiento', 'detalles'));
    }
}
