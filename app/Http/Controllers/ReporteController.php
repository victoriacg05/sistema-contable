<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $datos = $this->obtenerDatosReporte($request);

        return view('reportes.index', $datos);
    }

    public function pdf(Request $request)
    {
        $datos = $this->obtenerDatosReporte($request);

        $pdf = Pdf::loadView('reportes.pdf', $datos)
            ->setPaper('letter', 'portrait');

        return $pdf->download('reporte-financiero-' . $datos['anio'] . '-' . $datos['mes'] . '.pdf');
    }

    private function obtenerDatosReporte(Request $request)
    {
        $anio = $request->anio ?? now()->year;
        $mes = $request->mes ?? now()->month;

        $ventas = DB::table('facturas')
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->sum('total');

        $compras = DB::table('compras')
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->sum('total');

        $ingresos = DB::table('ingresos')
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->sum('monto');

        $gastos = DB::table('gastos')
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->sum('monto');

        $cuentasCobrar = DB::table('cuentas_cobrar')
            ->where('estado_id', 1)
            ->sum('saldo_pendiente');

        $cuentasPagar = DB::table('cuentas_pagar')
            ->where('estado_id', 1)
            ->sum('saldo_pendiente');

        $utilidad = ($ventas + $ingresos) - ($compras + $gastos);

        $presupuestoVsGasto = DB::table('presupuesto')
            ->join('categorias_gastos', 'categorias_gastos.id', '=', 'presupuesto.categoria_gasto_id')
            ->where('presupuesto.anio', $anio)
            ->where('presupuesto.mes', $mes)
            ->select(
                'categorias_gastos.nombre as categoria',
                'presupuesto.monto_presupuestado',
                DB::raw('(
                    SELECT COALESCE(SUM(gastos.monto), 0)
                    FROM gastos
                    WHERE gastos.categoria_gasto_id = presupuesto.categoria_gasto_id
                    AND YEAR(gastos.fecha) = presupuesto.anio
                    AND MONTH(gastos.fecha) = presupuesto.mes
                ) as gasto_real')
            )
            ->get()
            ->map(function ($item) {
                $item->diferencia = $item->monto_presupuestado - $item->gasto_real;
                return $item;
            });

        return compact(
            'anio',
            'mes',
            'ventas',
            'compras',
            'ingresos',
            'gastos',
            'cuentasCobrar',
            'cuentasPagar',
            'utilidad',
            'presupuestoVsGasto'
        );
    }
}