<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Services\BitacoraService;

class ContabilidadController extends Controller
{
    public function indexCuentas()
    {
        // Catálogo enriquecido con jerarquía (es_hoja, ruta, tipo_nombre).
        $cuentas = $this->catalogoConJerarquia();

        // Todos los movimientos (detalle de asientos) para desplegarlos en
        // línea dentro de cada cuenta del catálogo.
        $movimientos = DB::table('detalle_asientos_contables')
            ->join('asientos_contables', function ($join) {
                $join->on('detalle_asientos_contables.numero_asiento', '=', 'asientos_contables.numero_asiento')
                    ->on('detalle_asientos_contables.fecha_asiento', '=', 'asientos_contables.fecha');
            })
            ->join('estados', 'asientos_contables.estado_id', '=', 'estados.id')
            ->orderBy('asientos_contables.fecha')
            ->orderBy('detalle_asientos_contables.numero_asiento')
            ->select(
                'detalle_asientos_contables.numero_asiento',
                'detalle_asientos_contables.fecha_asiento',
                'detalle_asientos_contables.codigo_cuenta',
                'detalle_asientos_contables.debe',
                'detalle_asientos_contables.haber',
                'detalle_asientos_contables.descripcion',
                'asientos_contables.descripcion as asiento_descripcion',
                'estados.nombre as estado_nombre'
            )
            ->get();

        // Módulo relacionado por cuenta (para la opción "Ver más").
        $modulos = [];
        foreach ($cuentas as $cuenta) {
            $modulo = $this->moduloRelacionado($cuenta->nombre);
            if ($modulo) {
                $modulos[$cuenta->codigo_cuenta] = $modulo;
            }
        }

        // Datos operativos (cuentas por pagar/cobrar, inventario) que viven en
        // sus propios módulos y no generan asientos contables, para reflejarlos
        // directamente en la cuenta del catálogo correspondiente.
        $operativos = $this->datosOperativosPorCuenta($cuentas);

        // Cuentas bancarias reales con su saldo, para reflejarlas dentro de la
        // cuenta "Bancos" (1.1.2) del catálogo.
        $cuentasBancarias = \App\Models\CuentaBancaria::orderBy('banco_nombre')->get();

        return view('contabilidad.cuentas.index', compact('cuentas', 'movimientos', 'modulos', 'operativos', 'cuentasBancarias'));
    }

    /**
     * Construye un mapa (código de cuenta => datos operativos) para las
     * cuentas del catálogo que se corresponden con módulos que gestionan sus
     * propios documentos sin pasar por asientos contables: Cuentas por Pagar,
     * Cuentas por Cobrar e Inventario. Así el catálogo refleja los saldos y
     * documentos reales existentes en esos módulos.
     */
    private function datosOperativosPorCuenta($cuentas): array
    {
        $mapa = [];

        $porPagar = null;
        $porCobrar = null;
        $inventario = null;

        foreach ($cuentas as $cuenta) {
            $nombre = mb_strtolower($cuenta->nombre);

            if (str_contains($nombre, 'cuentas por pagar')) {
                $porPagar ??= $this->documentosPorPagar();
                $mapa[$cuenta->codigo_cuenta] = $porPagar;
            } elseif (str_contains($nombre, 'cuentas por cobrar')) {
                $porCobrar ??= $this->documentosPorCobrar();
                $mapa[$cuenta->codigo_cuenta] = $porCobrar;
            } elseif (str_contains($nombre, 'inventario') || str_contains($nombre, 'mercader') || str_contains($nombre, 'bodega')) {
                $inventario ??= $this->documentosInventario();
                $mapa[$cuenta->codigo_cuenta] = $inventario;
            }
        }

        return $mapa;
    }

    private function documentosPorPagar(): array
    {
        $items = DB::table('cuentas_pagar')
            ->join('proveedores', 'cuentas_pagar.proveedor_id', '=', 'proveedores.id')
            ->join('estados', 'cuentas_pagar.estado_id', '=', 'estados.id')
            ->orderBy('cuentas_pagar.fecha_vencimiento')
            ->select(
                'cuentas_pagar.numero_compra as documento',
                'proveedores.nombre as tercero',
                'cuentas_pagar.fecha_emision',
                'cuentas_pagar.fecha_vencimiento',
                'cuentas_pagar.monto_original',
                'cuentas_pagar.saldo_pendiente',
                'estados.nombre as estado_nombre'
            )
            ->get();

        return [
            'tipo' => 'documentos',
            'titulo' => 'Cuentas por pagar',
            'tercero_label' => 'Proveedor',
            'items' => $items,
            'total_saldo' => $items->sum('saldo_pendiente'),
        ];
    }

    private function documentosPorCobrar(): array
    {
        $items = DB::table('cuentas_cobrar')
            ->join('clientes', 'cuentas_cobrar.cliente_id', '=', 'clientes.id')
            ->join('estados', 'cuentas_cobrar.estado_id', '=', 'estados.id')
            ->orderBy('cuentas_cobrar.fecha_vencimiento')
            ->select(
                'cuentas_cobrar.numero_factura as documento',
                'clientes.nombre as tercero',
                'cuentas_cobrar.fecha_emision',
                'cuentas_cobrar.fecha_vencimiento',
                'cuentas_cobrar.monto_original',
                'cuentas_cobrar.saldo_pendiente',
                'estados.nombre as estado_nombre'
            )
            ->get();

        return [
            'tipo' => 'documentos',
            'titulo' => 'Cuentas por cobrar',
            'tercero_label' => 'Cliente',
            'items' => $items,
            'total_saldo' => $items->sum('saldo_pendiente'),
        ];
    }

    private function documentosInventario(): array
    {
        $items = DB::table('productos')
            ->orderBy('nombre')
            ->select('nombre', 'stock', 'precio')
            ->get();

        foreach ($items as $item) {
            $item->valor = $item->stock * $item->precio;
        }

        return [
            'tipo' => 'inventario',
            'titulo' => 'Productos en inventario',
            'items' => $items,
            'total_saldo' => $items->sum('valor'),
        ];
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

    /**
     * Devuelve el catálogo de cuentas enriquecido con dos atributos:
     * - es_hoja: true si la cuenta no tiene subcuentas (nodo hoja).
     * - ruta: ruta completa dentro del árbol (p. ej. "Activos → Activo
     *   Circulante → Caja").
     *
     * La jerarquía se deriva del código de cuenta con notación de puntos
     * (1, 1.1, 1.1.1), ya que el catálogo no tiene columna de cuenta padre.
     */
    private function catalogoConJerarquia()
    {
        $cuentas = DB::table('catalogo_cuentas')
            ->join('tipos_cuenta_contable', 'catalogo_cuentas.tipo_cuenta_contable_id', '=', 'tipos_cuenta_contable.id')
            ->select('catalogo_cuentas.*', 'tipos_cuenta_contable.nombre as tipo_nombre')
            ->orderBy('catalogo_cuentas.codigo_cuenta')
            ->get();

        $codigos = $cuentas->pluck('codigo_cuenta')->all();
        $porCodigo = $cuentas->keyBy('codigo_cuenta');

        foreach ($cuentas as $cuenta) {
            $prefijo = $cuenta->codigo_cuenta . '.';

            $cuenta->es_hoja = ! collect($codigos)->contains(
                fn ($cod) => str_starts_with($cod, $prefijo)
            );

            $acumulado = '';
            $ruta = [];
            foreach (explode('.', $cuenta->codigo_cuenta) as $parte) {
                $acumulado = $acumulado === '' ? $parte : $acumulado . '.' . $parte;
                $ancestro = $porCodigo->get($acumulado);
                $ruta[] = $ancestro ? $ancestro->nombre : $acumulado;
            }
            $cuenta->ruta = implode(' → ', $ruta);
        }

        return $cuentas;
    }

    /**
     * Determina si una cuenta es de detalle (nodo hoja) a partir de su código.
     */
    private function esCuentaHoja(string $codigoCuenta): bool
    {
        return ! DB::table('catalogo_cuentas')
            ->where('codigo_cuenta', 'like', $codigoCuenta . '.%')
            ->exists();
    }

    /**
     * Determina el módulo del sistema relacionado con una cuenta contable a
     * partir de su nombre, para ofrecer una opción "Ver más" que redirija al
     * detalle completo. Solo devuelve un destino si la ruta existe.
     */
    private function moduloRelacionado(string $nombreCuenta): ?array
    {
        $nombre = mb_strtolower($nombreCuenta);

        $mapa = [
            ['claves' => ['cuentas por cobrar', 'por cobrar'], 'ruta' => 'cuentas-cobrar.index', 'modulo' => 'Cuentas por Cobrar'],
            ['claves' => ['cuentas por pagar', 'por pagar'], 'ruta' => 'cuentas-pagar.index', 'modulo' => 'Cuentas por Pagar'],
            ['claves' => ['inventario', 'mercader', 'bodega', 'existencia'], 'ruta' => 'inventario.index', 'modulo' => 'Inventario'],
            ['claves' => ['banco', 'tesoreria', 'tesorería'], 'ruta' => 'tesoreria.index', 'modulo' => 'Tesorería / Bancos'],
            ['claves' => ['activo fijo', 'activos fijos'], 'ruta' => 'activos.index', 'modulo' => 'Activos'],
        ];

        foreach ($mapa as $entrada) {
            foreach ($entrada['claves'] as $clave) {
                if (str_contains($nombre, $clave) && Route::has($entrada['ruta'])) {
                    return ['ruta' => $entrada['ruta'], 'modulo' => $entrada['modulo']];
                }
            }
        }

        return null;
    }

    /**
     * Muestra el desglose de movimientos de una cuenta contable: todas las
     * transacciones (detalle de asientos), los totales de debe/haber y el
     * saldo corrido. Para cuentas agrupadoras se incluyen además los
     * movimientos de todas sus subcuentas, permitiendo navegar desde los
     * niveles generales hasta el detalle.
     */
    public function showCuenta(string $codigoCuenta)
    {
        $cuenta = DB::table('catalogo_cuentas')
            ->join('tipos_cuenta_contable', 'catalogo_cuentas.tipo_cuenta_contable_id', '=', 'tipos_cuenta_contable.id')
            ->where('catalogo_cuentas.codigo_cuenta', $codigoCuenta)
            ->select('catalogo_cuentas.*', 'tipos_cuenta_contable.nombre as tipo_nombre')
            ->firstOrFail();

        // La cuenta y todas sus descendientes (para cuentas agrupadoras).
        $codigosIncluidos = DB::table('catalogo_cuentas')
            ->where('codigo_cuenta', $codigoCuenta)
            ->orWhere('codigo_cuenta', 'like', $codigoCuenta . '.%')
            ->pluck('codigo_cuenta')
            ->all();

        $movimientos = DB::table('detalle_asientos_contables')
            ->join('asientos_contables', function ($join) {
                $join->on('detalle_asientos_contables.numero_asiento', '=', 'asientos_contables.numero_asiento')
                    ->on('detalle_asientos_contables.fecha_asiento', '=', 'asientos_contables.fecha');
            })
            ->join('catalogo_cuentas', 'detalle_asientos_contables.codigo_cuenta', '=', 'catalogo_cuentas.codigo_cuenta')
            ->join('estados', 'asientos_contables.estado_id', '=', 'estados.id')
            ->whereIn('detalle_asientos_contables.codigo_cuenta', $codigosIncluidos)
            ->orderBy('asientos_contables.fecha')
            ->orderBy('detalle_asientos_contables.numero_asiento')
            ->select(
                'detalle_asientos_contables.numero_asiento',
                'detalle_asientos_contables.fecha_asiento',
                'detalle_asientos_contables.codigo_cuenta',
                'detalle_asientos_contables.debe',
                'detalle_asientos_contables.haber',
                'detalle_asientos_contables.descripcion',
                'asientos_contables.descripcion as asiento_descripcion',
                'catalogo_cuentas.nombre as cuenta_nombre',
                'estados.nombre as estado_nombre'
            )
            ->get();

        $totalDebe = $movimientos->sum('debe');
        $totalHaber = $movimientos->sum('haber');
        $saldo = $totalDebe - $totalHaber;

        // Saldo corrido acumulado por movimiento.
        $acumulado = 0;
        foreach ($movimientos as $mov) {
            $acumulado += ($mov->debe - $mov->haber);
            $mov->saldo_acumulado = $acumulado;
        }

        // Ruta completa y si es cuenta de detalle.
        $info = $this->catalogoConJerarquia()->keyBy('codigo_cuenta')->get($codigoCuenta);
        $cuenta->ruta = optional($info)->ruta ?? $cuenta->nombre;
        $cuenta->es_hoja = optional($info)->es_hoja ?? true;

        $verMas = $this->moduloRelacionado($cuenta->nombre);

        return view('contabilidad.cuentas.movimientos', compact(
            'cuenta',
            'movimientos',
            'totalDebe',
            'totalHaber',
            'saldo',
            'verMas'
        ));
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
        // Solo las cuentas de detalle (nodos hoja) permiten registrar asientos.
        $cuentasHoja = $this->catalogoConJerarquia()
            ->where('estado', true)
            ->where('es_hoja', true)
            ->values();

        // Cuentas bancarias activas para desglosar la cuenta "Bancos" (1.1.2)
        // en una ruta por cada banco registrado.
        $bancos = \App\Models\CuentaBancaria::where('estado', true)
            ->orderBy('banco_nombre')
            ->get();

        $cuentas = collect();
        foreach ($cuentasHoja as $cuenta) {
            if ($cuenta->codigo_cuenta === '1.1.2' && $bancos->isNotEmpty()) {
                foreach ($bancos as $banco) {
                    $cuentas->push((object) [
                        'codigo_cuenta' => $cuenta->codigo_cuenta,
                        'etiqueta' => $cuenta->ruta . ' → ' . $banco->banco_nombre,
                    ]);
                }
            } else {
                $cuentas->push((object) [
                    'codigo_cuenta' => $cuenta->codigo_cuenta,
                    'etiqueta' => $cuenta->ruta,
                ]);
            }
        }

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

        // Solo se permiten movimientos en cuentas de detalle (nodos hoja).
        foreach ($request->lineas as $linea) {
            if (! $this->esCuentaHoja($linea['codigo_cuenta'])) {
                $cuenta = DB::table('catalogo_cuentas')
                    ->where('codigo_cuenta', $linea['codigo_cuenta'])
                    ->first();
                $nombre = $cuenta ? "{$cuenta->codigo_cuenta} - {$cuenta->nombre}" : $linea['codigo_cuenta'];

                return back()->withErrors([
                    'lineas' => "La cuenta «{$nombre}» es una cuenta agrupadora y no admite movimientos. Seleccione una cuenta de detalle (último nivel).",
                ])->withInput();
            }
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

        // Añadir la ruta completa de cada cuenta dentro del catálogo.
        $rutas = $this->catalogoConJerarquia()->keyBy('codigo_cuenta');
        foreach ($detalles as $detalle) {
            $detalle->cuenta_ruta = optional($rutas->get($detalle->codigo_cuenta))->ruta
                ?? $detalle->cuenta_nombre;
        }

        return view('contabilidad.asientos.show', compact('asiento', 'detalles'));
    }
}
