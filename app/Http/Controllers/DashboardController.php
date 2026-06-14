<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Factura;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $ventasTotales = Factura::sum('total');

        $estadoPagado = DB::table('estados')->where('nombre', 'pagado')->first();
        $estadoPendiente = DB::table('estados')->where('nombre', 'pendiente')->first();

        $facturasPagadas = $estadoPagado
            ? Factura::where('estado_id', $estadoPagado->id)->count()
            : 0;

        $facturasPendientes = $estadoPendiente
            ? Factura::where('estado_id', $estadoPendiente->id)->count()
            : 0;

        $clientesRegistrados = Cliente::count();

        $productosRegistrados = Producto::count();

        $stockBajo = Producto::whereColumn('stock', '<=', 'stock_minimo')->count();

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
                DB::raw('DATEDIFF(CURDATE(), cuentas_cobrar.fecha_vencimiento) as dias_atraso')
            )
            ->orderByDesc('dias_atraso')
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