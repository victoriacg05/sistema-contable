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

        return view('dashboard', compact(
            'ventasTotales',
            'facturasPagadas',
            'facturasPendientes',
            'clientesRegistrados',
            'productosRegistrados',
            'stockBajo'
        ));
    }
}