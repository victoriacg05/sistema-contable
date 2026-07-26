<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        return view('reportes.index', $this->obtenerDatosReporte($request));
    }

    public function pdf(Request $request)
    {
        $datos = $this->obtenerDatosReporte($request);

        $pdf = Pdf::loadView('reportes.pdf', $datos)
            ->setPaper('letter', 'landscape');

        return $pdf->download('reporte-financiero-' . $datos['anio'] . '-' . $datos['mes'] . '.pdf');
    }

    private function obtenerDatosReporte(Request $request): array
    {
        $datosFiltro = $request->validate([
            'anio' => 'nullable|integer|min:2000|max:2100',
            'mes' => 'nullable|integer|min:1|max:12',
        ]);

        $anio = (int) ($datosFiltro['anio'] ?? now()->year);
        $mes = (int) ($datosFiltro['mes'] ?? now()->month);
        $inicio = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin = $inicio->copy()->addMonth();
        $inicioAnterior = $inicio->copy()->subMonthNoOverflow()->startOfMonth();
        $finAnterior = $inicio->copy();

        $metricas = $this->metricasPeriodo($inicio, $fin);
        $metricasAnteriores = $this->metricasPeriodo($inicioAnterior, $finAnterior);

        $cuentasCobrar = (float) DB::table('cuentas_cobrar')
            ->where('saldo_pendiente', '>', 0)
            ->sum('saldo_pendiente');
        $cuentasCobrarVencidas = (float) DB::table('cuentas_cobrar')
            ->where('saldo_pendiente', '>', 0)
            ->where('fecha_vencimiento', '<', $inicio->copy()->endOfMonth()->toDateString())
            ->sum('saldo_pendiente');
        $cuentasPagar = (float) DB::table('cuentas_pagar')
            ->where('saldo_pendiente', '>', 0)
            ->sum('saldo_pendiente');
        $cuentasPagarVencidas = (float) DB::table('cuentas_pagar')
            ->where('saldo_pendiente', '>', 0)
            ->where('fecha_vencimiento', '<', $inicio->copy()->endOfMonth()->toDateString())
            ->sum('saldo_pendiente');

        $saldoBancos = (float) DB::table('cuentas_bancarias')
            ->where('estado', true)
            ->sum('saldo');
        $valorInventario = (float) DB::table('productos')
            ->where('estado', true)
            ->selectRaw('COALESCE(SUM(stock * precio), 0) as total')
            ->value('total');
        $productosStockBajo = DB::table('productos')
            ->where('estado', true)
            ->whereColumn('stock', '<=', 'stock_minimo')
            ->count();

        $flujoBancario = Schema::hasTable('movimientos_bancarios')
            ? DB::table('movimientos_bancarios')
                ->where('fecha', '>=', $inicio)
                ->where('fecha', '<', $fin)
                ->selectRaw("
                    COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN monto ELSE 0 END), 0) as entradas,
                    COALESCE(SUM(CASE WHEN tipo = 'salida' THEN monto ELSE 0 END), 0) as salidas
                ")
                ->first()
            : (object) ['entradas' => 0, 'salidas' => 0];

        $cobrosClientes = (float) DB::table('ingresos')
            ->where('referencia_ingreso', 'like', 'AUTO-COBRO-%')
            ->where('fecha', '>=', $inicio->toDateString())
            ->where('fecha', '<', $fin->toDateString())
            ->sum('monto');
        $cobrosContado = (float) DB::table('ingresos')
            ->where('referencia_ingreso', 'like', 'AUTO-VENTA-%')
            ->where('fecha', '>=', $inicio->toDateString())
            ->where('fecha', '<', $fin->toDateString())
            ->sum('monto');
        $pagosProveedores = Schema::hasTable('pagos_cuentas_pagar')
            ? (float) DB::table('pagos_cuentas_pagar')
                ->where('fecha_pago', '>=', $inicio)
                ->where('fecha_pago', '<', $fin)
                ->sum('monto_pagado')
            : 0.0;

        $topProductos = DB::table('detalle_facturas')
            ->join('facturas', function ($join) {
                $join->on('detalle_facturas.numero_factura', '=', 'facturas.numero_factura')
                    ->on('detalle_facturas.cliente_id', '=', 'facturas.cliente_id');
            })
            ->join('productos', 'detalle_facturas.producto_id', '=', 'productos.id')
            ->where('facturas.fecha', '>=', $inicio->toDateString())
            ->where('facturas.fecha', '<', $fin->toDateString())
            ->whereNotExists($this->facturaAnulada())
            ->groupBy('productos.id', 'productos.codigo_barras', 'productos.nombre')
            ->select(
                'productos.codigo_barras',
                'productos.nombre',
                DB::raw('SUM(detalle_facturas.cantidad) as cantidad'),
                DB::raw('SUM(detalle_facturas.subtotal) as total')
            )
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $gastosPorCategoria = DB::table('gastos')
            ->join('categorias_gastos', 'gastos.categoria_gasto_id', '=', 'categorias_gastos.id')
            ->where('gastos.fecha', '>=', $inicio->toDateString())
            ->where('gastos.fecha', '<', $fin->toDateString())
            ->groupBy('categorias_gastos.id', 'categorias_gastos.nombre')
            ->select(
                'categorias_gastos.nombre as categoria',
                DB::raw('SUM(gastos.monto) as total')
            )
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $gastosPorCategoriaId = DB::table('gastos')
            ->where('fecha', '>=', $inicio->toDateString())
            ->where('fecha', '<', $fin->toDateString())
            ->groupBy('categoria_gasto_id')
            ->select('categoria_gasto_id', DB::raw('SUM(monto) as total'))
            ->pluck('total', 'categoria_gasto_id');

        $presupuestoVsGasto = DB::table('presupuesto')
            ->join('categorias_gastos', 'categorias_gastos.id', '=', 'presupuesto.categoria_gasto_id')
            ->where('presupuesto.anio', $anio)
            ->where('presupuesto.mes', $mes)
            ->select(
                'presupuesto.categoria_gasto_id',
                'categorias_gastos.nombre as categoria',
                'presupuesto.monto_presupuestado'
            )
            ->orderBy('categorias_gastos.nombre')
            ->get()
            ->map(function ($item) use ($gastosPorCategoriaId) {
                $item->gasto_real = (float) ($gastosPorCategoriaId[$item->categoria_gasto_id] ?? 0);
                $item->diferencia = (float) $item->monto_presupuestado - $item->gasto_real;
                $item->ejecucion = (float) $item->monto_presupuestado > 0
                    ? ($item->gasto_real / (float) $item->monto_presupuestado) * 100
                    : 0;

                return $item;
            });

        $comparativo = collect([
            'ventasNetas' => 'Ventas netas',
            'utilidadBruta' => 'Utilidad bruta estimada',
            'gastos' => 'Gastos operativos',
            'resultado' => 'Resultado estimado',
        ])->map(function ($etiqueta, $clave) use ($metricas, $metricasAnteriores) {
            $actual = (float) $metricas[$clave];
            $anterior = (float) $metricasAnteriores[$clave];

            return (object) [
                'etiqueta' => $etiqueta,
                'actual' => $actual,
                'anterior' => $anterior,
                'variacion' => $anterior != 0
                    ? (($actual - $anterior) / abs($anterior)) * 100
                    : null,
            ];
        })->values();

        $indiceLiquidez = $cuentasPagar > 0
            ? ($saldoBancos + $cuentasCobrar) / $cuentasPagar
            : null;
        $porcentajeMorosidad = $cuentasCobrar > 0
            ? ($cuentasCobrarVencidas / $cuentasCobrar) * 100
            : 0;

        return [
            ...$metricas,
            'anio' => $anio,
            'mes' => $mes,
            'nombrePeriodo' => $inicio->locale('es')->translatedFormat('F Y'),
            'nombrePeriodoAnterior' => $inicioAnterior->locale('es')->translatedFormat('F Y'),
            'cuentasCobrar' => $cuentasCobrar,
            'cuentasCobrarVencidas' => $cuentasCobrarVencidas,
            'cuentasPagar' => $cuentasPagar,
            'cuentasPagarVencidas' => $cuentasPagarVencidas,
            'saldoBancos' => $saldoBancos,
            'valorInventario' => $valorInventario,
            'productosStockBajo' => $productosStockBajo,
            'entradasBancarias' => (float) $flujoBancario->entradas,
            'salidasBancarias' => (float) $flujoBancario->salidas,
            'flujoBancarioNeto' => (float) $flujoBancario->entradas - (float) $flujoBancario->salidas,
            'cobrosClientes' => $cobrosClientes,
            'cobrosContado' => $cobrosContado,
            'pagosProveedores' => $pagosProveedores,
            'indiceLiquidez' => $indiceLiquidez,
            'porcentajeMorosidad' => $porcentajeMorosidad,
            'topProductos' => $topProductos,
            'gastosPorCategoria' => $gastosPorCategoria,
            'presupuestoVsGasto' => $presupuestoVsGasto,
            'comparativo' => $comparativo,
            'generadoEn' => now(),
        ];
    }

    private function metricasPeriodo(Carbon $inicio, Carbon $fin): array
    {
        $facturas = DB::table('facturas')
            ->where('fecha', '>=', $inicio->toDateString())
            ->where('fecha', '<', $fin->toDateString())
            ->whereNotExists($this->facturaAnulada());

        $ventas = (float) (clone $facturas)->sum('total');
        $ventasNetas = (float) (clone $facturas)
            ->selectRaw('COALESCE(SUM(subtotal - descuento), 0) as total')
            ->value('total');
        $impuestosVentas = (float) (clone $facturas)->sum('impuesto');
        $cantidadFacturas = (clone $facturas)->count();
        $ventasCredito = (float) (clone $facturas)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('cuentas_cobrar')
                    ->whereColumn('cuentas_cobrar.numero_factura', 'facturas.numero_factura')
                    ->whereColumn('cuentas_cobrar.cliente_id', 'facturas.cliente_id');
            })
            ->sum('total');
        $ventasContado = $ventas - $ventasCredito;

        $costoVentas = (float) DB::table('detalle_facturas')
            ->join('facturas', function ($join) {
                $join->on('detalle_facturas.numero_factura', '=', 'facturas.numero_factura')
                    ->on('detalle_facturas.cliente_id', '=', 'facturas.cliente_id');
            })
            ->join('productos', 'detalle_facturas.producto_id', '=', 'productos.id')
            ->where('facturas.fecha', '>=', $inicio->toDateString())
            ->where('facturas.fecha', '<', $fin->toDateString())
            ->whereNotExists($this->facturaAnulada())
            ->selectRaw('COALESCE(SUM(detalle_facturas.cantidad * productos.precio), 0) as total')
            ->value('total');

        $compras = (float) DB::table('compras')
            ->where('fecha', '>=', $inicio->toDateString())
            ->where('fecha', '<', $fin->toDateString())
            ->sum('total');
        $cantidadCompras = DB::table('compras')
            ->where('fecha', '>=', $inicio->toDateString())
            ->where('fecha', '<', $fin->toDateString())
            ->count();
        $ingresos = (float) DB::table('ingresos')
            ->where('referencia_ingreso', 'not like', 'AUTO-%')
            ->where('fecha', '>=', $inicio->toDateString())
            ->where('fecha', '<', $fin->toDateString())
            ->sum('monto');
        $gastos = (float) DB::table('gastos')
            ->where('fecha', '>=', $inicio->toDateString())
            ->where('fecha', '<', $fin->toDateString())
            ->sum('monto');
        $cantidadGastos = DB::table('gastos')
            ->where('fecha', '>=', $inicio->toDateString())
            ->where('fecha', '<', $fin->toDateString())
            ->count();

        $utilidadBruta = $ventasNetas - $costoVentas;
        $resultado = $utilidadBruta + $ingresos - $gastos;

        return [
            'ventas' => $ventas,
            'ventasNetas' => $ventasNetas,
            'impuestosVentas' => $impuestosVentas,
            'ventasContado' => $ventasContado,
            'ventasCredito' => $ventasCredito,
            'compras' => $compras,
            'ingresos' => $ingresos,
            'gastos' => $gastos,
            'costoVentas' => $costoVentas,
            'utilidadBruta' => $utilidadBruta,
            'utilidad' => $resultado,
            'resultado' => $resultado,
            'cantidadFacturas' => $cantidadFacturas,
            'cantidadCompras' => $cantidadCompras,
            'cantidadGastos' => $cantidadGastos,
        ];
    }

    private function facturaAnulada(): callable
    {
        return function (Builder $query) {
            $query->selectRaw('1')
                ->from('anulaciones_facturas')
                ->whereColumn('anulaciones_facturas.numero_factura', 'facturas.numero_factura')
                ->whereColumn('anulaciones_facturas.cliente_id', 'facturas.cliente_id');
        };
    }
}
