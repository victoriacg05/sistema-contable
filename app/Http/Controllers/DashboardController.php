<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $facturas = DB::table('facturas')
            ->leftJoin('estados', 'facturas.estado_id', '=', 'estados.id')
            ->selectRaw('COALESCE(SUM(facturas.total), 0) as ventas_totales')
            ->selectRaw(
                "SUM(CASE WHEN LOWER(estados.nombre) = 'pagado' THEN 1 ELSE 0 END) as pagadas"
            )
            ->selectRaw(
                "SUM(CASE WHEN LOWER(estados.nombre) = 'pendiente' THEN 1 ELSE 0 END) as pendientes"
            )
            ->first();

        $productos = DB::table('productos')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                'SUM(CASE WHEN stock <= stock_minimo THEN 1 ELSE 0 END) as stock_bajo'
            )
            ->first();

        $ventasTotales = (float) ($facturas->ventas_totales ?? 0);
        $facturasPagadas = (int) ($facturas->pagadas ?? 0);
        $facturasPendientes = (int) ($facturas->pendientes ?? 0);
        $clientesRegistrados = DB::table('clientes')->count();
        $productosRegistrados = (int) ($productos->total ?? 0);
        $stockBajo = (int) ($productos->stock_bajo ?? 0);

        // Cuentas por cobrar vencidas (alertas de morosidad)
        $cuentasVencidas = DB::table('cuentas_cobrar')
            ->join('clientes', 'cuentas_cobrar.cliente_id', '=', 'clientes.id')
            ->where('cuentas_cobrar.saldo_pendiente', '>', 0)
            ->where('cuentas_cobrar.fecha_vencimiento', '<', now())
            ->select(
                'cuentas_cobrar.numero_factura',
                'clientes.nombre as cliente_nombre',
                'cuentas_cobrar.saldo_pendiente',
                'cuentas_cobrar.fecha_vencimiento',
            )
            ->orderBy('cuentas_cobrar.fecha_vencimiento')
            ->limit(10)
            ->get();

        // Cuentas por pagar próximas a vencer
        $cuentasPorVencer = DB::table('cuentas_pagar')
            ->join('proveedores', 'cuentas_pagar.proveedor_id', '=', 'proveedores.id')
            ->where('cuentas_pagar.saldo_pendiente', '>', 0)
            ->where('cuentas_pagar.fecha_vencimiento', '<=', now()->addDays(7))
            ->select(
                'cuentas_pagar.numero_compra',
                'proveedores.nombre as proveedor_nombre',
                'cuentas_pagar.saldo_pendiente',
                'cuentas_pagar.fecha_vencimiento'
            )
            ->orderBy('cuentas_pagar.fecha_vencimiento')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'ventasTotales',
            'facturasPagadas',
            'facturasPendientes',
            'clientesRegistrados',
            'productosRegistrados',
            'stockBajo',
            'cuentasVencidas',
            'cuentasPorVencer'
        ));
    }
}