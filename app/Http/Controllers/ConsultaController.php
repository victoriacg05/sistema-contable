<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsultaController extends Controller
{
    public function index()
    {
        return view('consultas.index');
    }

    public function buscar(Request $request)
    {
        $request->validate([
            'modulo' => 'required|string',
            'termino' => 'nullable|string|max:255',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date',
        ]);

        $modulo = $request->modulo;
        $termino = $request->termino;
        $fechaDesde = $request->fecha_desde;
        $fechaHasta = $request->fecha_hasta;
        $resultados = collect();

        switch ($modulo) {
            case 'facturas':
                $query = DB::table('facturas')
                    ->join('clientes', 'facturas.cliente_id', '=', 'clientes.id')
                    ->join('estados', 'facturas.estado_id', '=', 'estados.id')
                    ->select('facturas.numero_factura', 'clientes.nombre as cliente', 'facturas.fecha', 'facturas.total', 'estados.nombre as estado');

                if ($termino) {
                    $query->where(function ($q) use ($termino) {
                        $q->where('facturas.numero_factura', 'like', "%$termino%")
                          ->orWhere('clientes.nombre', 'like', "%$termino%");
                    });
                }
                if ($fechaDesde) $query->where('facturas.fecha', '>=', $fechaDesde);
                if ($fechaHasta) $query->where('facturas.fecha', '<=', $fechaHasta);

                $resultados = $query->orderByDesc('facturas.fecha')->limit(100)->get();
                break;

            case 'compras':
                $query = DB::table('compras')
                    ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
                    ->join('estados', 'compras.estado_id', '=', 'estados.id')
                    ->select('compras.numero_compra', 'proveedores.nombre as proveedor', 'compras.fecha', 'compras.total', 'estados.nombre as estado');

                if ($termino) {
                    $query->where(function ($q) use ($termino) {
                        $q->where('compras.numero_compra', 'like', "%$termino%")
                          ->orWhere('proveedores.nombre', 'like', "%$termino%");
                    });
                }
                if ($fechaDesde) $query->where('compras.fecha', '>=', $fechaDesde);
                if ($fechaHasta) $query->where('compras.fecha', '<=', $fechaHasta);

                $resultados = $query->orderByDesc('compras.fecha')->limit(100)->get();
                break;

            case 'ingresos':
                $query = DB::table('ingresos')
                    ->join('users', 'ingresos.usuario_id', '=', 'users.id')
                    ->select('ingresos.referencia_ingreso', 'ingresos.origen', 'ingresos.fecha', 'ingresos.monto', 'users.name as usuario');

                if ($termino) {
                    $query->where(function ($q) use ($termino) {
                        $q->where('ingresos.referencia_ingreso', 'like', "%$termino%")
                          ->orWhere('ingresos.origen', 'like', "%$termino%");
                    });
                }
                if ($fechaDesde) $query->where('ingresos.fecha', '>=', $fechaDesde);
                if ($fechaHasta) $query->where('ingresos.fecha', '<=', $fechaHasta);

                $resultados = $query->orderByDesc('ingresos.fecha')->limit(100)->get();
                break;

            case 'gastos':
                $query = DB::table('gastos')
                    ->join('categorias_gastos', 'gastos.categoria_gasto_id', '=', 'categorias_gastos.id')
                    ->select('gastos.numero_comprobante', 'categorias_gastos.nombre as categoria', 'gastos.fecha', 'gastos.monto', 'gastos.descripcion');

                if ($termino) {
                    $query->where(function ($q) use ($termino) {
                        $q->where('gastos.numero_comprobante', 'like', "%$termino%")
                          ->orWhere('gastos.descripcion', 'like', "%$termino%");
                    });
                }
                if ($fechaDesde) $query->where('gastos.fecha', '>=', $fechaDesde);
                if ($fechaHasta) $query->where('gastos.fecha', '<=', $fechaHasta);

                $resultados = $query->orderByDesc('gastos.fecha')->limit(100)->get();
                break;

            case 'clientes':
                $query = DB::table('clientes')
                    ->select('clientes.identificacion', 'clientes.nombre', 'clientes.email', 'clientes.telefono', 'clientes.estado');

                if ($termino) {
                    $query->where(function ($q) use ($termino) {
                        $q->where('clientes.nombre', 'like', "%$termino%")
                          ->orWhere('clientes.identificacion', 'like', "%$termino%")
                          ->orWhere('clientes.email', 'like', "%$termino%");
                    });
                }

                $resultados = $query->orderBy('clientes.nombre')->limit(100)->get();
                break;

            case 'proveedores':
                $query = DB::table('proveedores')
                    ->select('proveedores.identificacion', 'proveedores.nombre', 'proveedores.empresa', 'proveedores.correo', 'proveedores.estado');

                if ($termino) {
                    $query->where(function ($q) use ($termino) {
                        $q->where('proveedores.nombre', 'like', "%$termino%")
                          ->orWhere('proveedores.identificacion', 'like', "%$termino%")
                          ->orWhere('proveedores.empresa', 'like', "%$termino%");
                    });
                }

                $resultados = $query->orderBy('proveedores.nombre')->limit(100)->get();
                break;
        }

        DB::table('consultas_realizadas')->insert([
            'codigo_consulta' => 'CON-' . now()->format('YmdHis'),
            'usuario_id' => Auth::id(),
            'modulo' => $modulo,
            'criterio_busqueda' => $termino ?? '',
            'fecha_consulta' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return view('consultas.resultados', compact('resultados', 'modulo', 'termino', 'fechaDesde', 'fechaHasta'));
    }
}
