<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ConsultaController extends Controller
{
    public function index()
    {
        $this->autorizar();

        return view('consultas.index', [
            'modulos' => $this->modulosDisponibles(),
            'resultados' => null,
            'moduloSeleccionado' => null,
            'configuracion' => null,
            'termino' => null,
            'fechaDesde' => null,
            'fechaHasta' => null,
            'totalMonetario' => null,
        ]);
    }

    public function buscar(Request $request)
    {
        $this->autorizar();
        $modulos = $this->modulosDisponibles();

        $datos = $request->validate([
            'modulo' => ['required', Rule::in(array_keys($modulos))],
            'termino' => 'nullable|string|max:255',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
        ]);

        $modulo = $datos['modulo'];
        $termino = trim((string) ($datos['termino'] ?? ''));
        $fechaDesde = $datos['fecha_desde'] ?? null;
        $fechaHasta = $datos['fecha_hasta'] ?? null;
        $configuracion = $modulos[$modulo];
        $query = $this->consultaModulo($modulo, $termino);

        if ($configuracion['campo_fecha'] && $fechaDesde) {
            $query->where($configuracion['campo_fecha'], '>=', $fechaDesde);
        }

        if ($configuracion['campo_fecha'] && $fechaHasta) {
            $query->where($configuracion['campo_fecha'], '<=', $fechaHasta);
        }

        foreach ($configuracion['orden'] as [$columna, $direccion]) {
            $query->orderBy($columna, $direccion);
        }

        $totalMonetario = $configuracion['campo_monto']
            ? (float) (clone $query)->sum($configuracion['campo_monto'])
            : null;

        $resultados = $query->paginate(30)->withQueryString();

        DB::table('consultas_realizadas')->insert([
            'codigo_consulta' => 'CON-' . now()->format('YmdHis') . '-' . strtoupper(str()->random(6)),
            'usuario_id' => Auth::id(),
            'modulo' => $modulo,
            'criterio_busqueda' => collect([
                $termino,
                $fechaDesde ? "Desde {$fechaDesde}" : null,
                $fechaHasta ? "Hasta {$fechaHasta}" : null,
            ])->filter()->implode(' | '),
            'fecha_consulta' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return view('consultas.index', compact(
            'modulos',
            'resultados',
            'modulo',
            'configuracion',
            'termino',
            'fechaDesde',
            'fechaHasta',
            'totalMonetario'
        ) + ['moduloSeleccionado' => $modulo]);
    }

    private function consultaModulo(string $modulo, string $termino): Builder
    {
        return match ($modulo) {
            'facturas' => DB::table('facturas')
                ->join('clientes', 'facturas.cliente_id', '=', 'clientes.id')
                ->join('estados', 'facturas.estado_id', '=', 'estados.id')
                ->select(
                    'facturas.numero_factura',
                    'clientes.nombre as cliente',
                    'facturas.fecha',
                    'facturas.total',
                    'estados.nombre as estado'
                )
                ->when($termino !== '', function ($query) use ($termino) {
                    $query->where(function ($filtro) use ($termino) {
                        $filtro->where('facturas.numero_factura', 'like', "%{$termino}%")
                            ->orWhere('clientes.nombre', 'like', "%{$termino}%")
                            ->orWhere('estados.nombre', 'like', "%{$termino}%");
                    });
                }),

            'compras' => DB::table('compras')
                ->join('proveedores', 'compras.proveedor_id', '=', 'proveedores.id')
                ->join('estados', 'compras.estado_id', '=', 'estados.id')
                ->select(
                    'compras.numero_compra',
                    'proveedores.nombre as proveedor',
                    'compras.fecha',
                    'compras.total',
                    'estados.nombre as estado'
                )
                ->when($termino !== '', function ($query) use ($termino) {
                    $query->where(function ($filtro) use ($termino) {
                        $filtro->where('compras.numero_compra', 'like', "%{$termino}%")
                            ->orWhere('proveedores.nombre', 'like', "%{$termino}%")
                            ->orWhere('estados.nombre', 'like', "%{$termino}%");
                    });
                }),

            'ingresos' => DB::table('ingresos')
                ->join('metodos_pago', 'ingresos.metodo_pago_id', '=', 'metodos_pago.id')
                ->select(
                    'ingresos.referencia_ingreso',
                    'ingresos.origen',
                    'metodos_pago.nombre as metodo',
                    'ingresos.fecha',
                    'ingresos.monto'
                )
                ->when($termino !== '', function ($query) use ($termino) {
                    $query->where(function ($filtro) use ($termino) {
                        $filtro->where('ingresos.referencia_ingreso', 'like', "%{$termino}%")
                            ->orWhere('ingresos.origen', 'like', "%{$termino}%")
                            ->orWhere('metodos_pago.nombre', 'like', "%{$termino}%");
                    });
                }),

            'gastos' => DB::table('gastos')
                ->join('categorias_gastos', 'gastos.categoria_gasto_id', '=', 'categorias_gastos.id')
                ->join('metodos_pago', 'gastos.metodo_pago_id', '=', 'metodos_pago.id')
                ->select(
                    'gastos.numero_comprobante',
                    'categorias_gastos.nombre as categoria',
                    'metodos_pago.nombre as metodo',
                    'gastos.fecha',
                    'gastos.monto',
                    'gastos.descripcion'
                )
                ->when($termino !== '', function ($query) use ($termino) {
                    $query->where(function ($filtro) use ($termino) {
                        $filtro->where('gastos.numero_comprobante', 'like', "%{$termino}%")
                            ->orWhere('gastos.descripcion', 'like', "%{$termino}%")
                            ->orWhere('categorias_gastos.nombre', 'like', "%{$termino}%");
                    });
                }),

            'clientes' => DB::table('clientes')
                ->select('identificacion', 'nombre', 'email', 'telefono', 'estado')
                ->when($termino !== '', function ($query) use ($termino) {
                    $query->where(function ($filtro) use ($termino) {
                        $filtro->where('nombre', 'like', "%{$termino}%")
                            ->orWhere('identificacion', 'like', "%{$termino}%")
                            ->orWhere('email', 'like', "%{$termino}%")
                            ->orWhere('telefono', 'like', "%{$termino}%");
                    });
                }),

            'proveedores' => DB::table('proveedores')
                ->select('identificacion', 'nombre', 'empresa', 'correo', 'telefono', 'estado')
                ->when($termino !== '', function ($query) use ($termino) {
                    $query->where(function ($filtro) use ($termino) {
                        $filtro->where('nombre', 'like', "%{$termino}%")
                            ->orWhere('identificacion', 'like', "%{$termino}%")
                            ->orWhere('empresa', 'like', "%{$termino}%")
                            ->orWhere('correo', 'like', "%{$termino}%");
                    });
                }),

            'productos' => DB::table('productos')
                ->join('categorias_productos', 'productos.categoria_producto_id', '=', 'categorias_productos.id')
                ->select(
                    'productos.codigo_barras',
                    'productos.nombre',
                    'categorias_productos.nombre as categoria',
                    'productos.stock',
                    'productos.stock_minimo',
                    'productos.precio',
                    'productos.porcentaje_ganancia',
                    'productos.estado'
                )
                ->when($termino !== '', function ($query) use ($termino) {
                    $query->where(function ($filtro) use ($termino) {
                        $filtro->where('productos.codigo_barras', 'like', "%{$termino}%")
                            ->orWhere('productos.nombre', 'like', "%{$termino}%")
                            ->orWhere('categorias_productos.nombre', 'like', "%{$termino}%");
                    });
                }),

            'inventario' => DB::table('movimientos_inventario')
                ->join('productos', 'movimientos_inventario.producto_id', '=', 'productos.id')
                ->join(
                    'tipos_movimiento_inventario',
                    'movimientos_inventario.tipo_movimiento_inventario_id',
                    '=',
                    'tipos_movimiento_inventario.id'
                )
                ->select(
                    'movimientos_inventario.referencia_movimiento',
                    'productos.codigo_barras',
                    'productos.nombre as producto',
                    'tipos_movimiento_inventario.nombre as tipo',
                    'movimientos_inventario.cantidad',
                    'movimientos_inventario.fecha',
                    'movimientos_inventario.descripcion'
                )
                ->when($termino !== '', function ($query) use ($termino) {
                    $query->where(function ($filtro) use ($termino) {
                        $filtro->where('movimientos_inventario.referencia_movimiento', 'like', "%{$termino}%")
                            ->orWhere('productos.codigo_barras', 'like', "%{$termino}%")
                            ->orWhere('productos.nombre', 'like', "%{$termino}%")
                            ->orWhere('tipos_movimiento_inventario.nombre', 'like', "%{$termino}%");
                    });
                }),

            'cuentas_cobrar' => DB::table('cuentas_cobrar')
                ->join('clientes', 'cuentas_cobrar.cliente_id', '=', 'clientes.id')
                ->join('estados', 'cuentas_cobrar.estado_id', '=', 'estados.id')
                ->select(
                    'cuentas_cobrar.numero_factura',
                    'clientes.nombre as cliente',
                    'cuentas_cobrar.fecha_emision',
                    'cuentas_cobrar.fecha_vencimiento',
                    'cuentas_cobrar.monto_original',
                    'cuentas_cobrar.saldo_pendiente',
                    'estados.nombre as estado'
                )
                ->when($termino !== '', function ($query) use ($termino) {
                    $query->where(function ($filtro) use ($termino) {
                        $filtro->where('cuentas_cobrar.numero_factura', 'like', "%{$termino}%")
                            ->orWhere('clientes.nombre', 'like', "%{$termino}%")
                            ->orWhere('estados.nombre', 'like', "%{$termino}%");
                    });
                }),

            'cuentas_pagar' => DB::table('cuentas_pagar')
                ->join('proveedores', 'cuentas_pagar.proveedor_id', '=', 'proveedores.id')
                ->join('estados', 'cuentas_pagar.estado_id', '=', 'estados.id')
                ->select(
                    'cuentas_pagar.numero_compra',
                    'proveedores.nombre as proveedor',
                    'cuentas_pagar.fecha_emision',
                    'cuentas_pagar.fecha_vencimiento',
                    'cuentas_pagar.monto_original',
                    'cuentas_pagar.saldo_pendiente',
                    'estados.nombre as estado'
                )
                ->when($termino !== '', function ($query) use ($termino) {
                    $query->where(function ($filtro) use ($termino) {
                        $filtro->where('cuentas_pagar.numero_compra', 'like', "%{$termino}%")
                            ->orWhere('proveedores.nombre', 'like', "%{$termino}%")
                            ->orWhere('estados.nombre', 'like', "%{$termino}%");
                    });
                }),

            'asientos' => DB::table('asientos_contables')
                ->join('estados', 'asientos_contables.estado_id', '=', 'estados.id')
                ->select(
                    'asientos_contables.numero_asiento',
                    'asientos_contables.fecha',
                    'asientos_contables.descripcion',
                    'asientos_contables.total_debe',
                    'asientos_contables.total_haber',
                    'estados.nombre as estado'
                )
                ->when($termino !== '', function ($query) use ($termino) {
                    $query->where(function ($filtro) use ($termino) {
                        $filtro->where('asientos_contables.numero_asiento', 'like', "%{$termino}%")
                            ->orWhere('asientos_contables.descripcion', 'like', "%{$termino}%")
                            ->orWhere('estados.nombre', 'like', "%{$termino}%");
                    });
                }),

            'bancos' => DB::table('cuentas_bancarias')
                ->select('banco_nombre', 'numero_cuenta', 'codigo_cuenta', 'moneda', 'saldo', 'estado')
                ->when($termino !== '', function ($query) use ($termino) {
                    $query->where(function ($filtro) use ($termino) {
                        $filtro->where('banco_nombre', 'like', "%{$termino}%")
                            ->orWhere('numero_cuenta', 'like', "%{$termino}%")
                            ->orWhere('codigo_cuenta', 'like', "%{$termino}%");
                    });
                }),
        };
    }

    private function modulosDisponibles(): array
    {
        $catalogo = [
            'facturas' => [
                'nombre' => 'Facturas y ventas',
                'descripcion' => 'Busque por factura, cliente o estado.',
                'permiso' => 'ver_facturas',
                'sigla' => 'FV',
                'placeholder' => 'Ej. FAC-2026, nombre del cliente o Pendiente',
                'campo_fecha' => 'facturas.fecha',
                'campo_monto' => 'facturas.total',
                'orden' => [['facturas.created_at', 'desc'], ['facturas.numero_factura', 'desc']],
                'columnas' => [
                    'numero_factura' => ['etiqueta' => 'Factura', 'tipo' => 'text'],
                    'cliente' => ['etiqueta' => 'Cliente', 'tipo' => 'text'],
                    'fecha' => ['etiqueta' => 'Fecha', 'tipo' => 'date'],
                    'total' => ['etiqueta' => 'Total', 'tipo' => 'money'],
                    'estado' => ['etiqueta' => 'Estado', 'tipo' => 'status'],
                ],
            ],
            'compras' => [
                'nombre' => 'Compras a proveedores',
                'descripcion' => 'Busque por compra, proveedor o estado.',
                'permiso' => 'ver_compras',
                'sigla' => 'CP',
                'placeholder' => 'Ej. COM-2026, proveedor o Pagado',
                'campo_fecha' => 'compras.fecha',
                'campo_monto' => 'compras.total',
                'orden' => [['compras.created_at', 'desc'], ['compras.numero_compra', 'desc']],
                'columnas' => [
                    'numero_compra' => ['etiqueta' => 'Compra', 'tipo' => 'text'],
                    'proveedor' => ['etiqueta' => 'Proveedor', 'tipo' => 'text'],
                    'fecha' => ['etiqueta' => 'Fecha', 'tipo' => 'date'],
                    'total' => ['etiqueta' => 'Total', 'tipo' => 'money'],
                    'estado' => ['etiqueta' => 'Estado', 'tipo' => 'status'],
                ],
            ],
            'ingresos' => [
                'nombre' => 'Ingresos',
                'descripcion' => 'Consulte ventas de contado, cobros y otros ingresos.',
                'permiso' => 'ver_ingresos',
                'sigla' => 'IN',
                'placeholder' => 'Ej. AUTO-VENTA, factura, origen o método',
                'campo_fecha' => 'ingresos.fecha',
                'campo_monto' => 'ingresos.monto',
                'orden' => [['ingresos.created_at', 'desc'], ['ingresos.referencia_ingreso', 'desc']],
                'columnas' => [
                    'referencia_ingreso' => ['etiqueta' => 'Referencia', 'tipo' => 'text'],
                    'origen' => ['etiqueta' => 'Origen', 'tipo' => 'text'],
                    'metodo' => ['etiqueta' => 'Método', 'tipo' => 'text'],
                    'fecha' => ['etiqueta' => 'Fecha', 'tipo' => 'date'],
                    'monto' => ['etiqueta' => 'Monto', 'tipo' => 'money'],
                ],
            ],
            'gastos' => [
                'nombre' => 'Gastos',
                'descripcion' => 'Busque comprobantes, categorías o descripciones.',
                'permiso' => 'ver_gastos',
                'sigla' => 'GA',
                'placeholder' => 'Ej. GAS-2026, categoría o descripción',
                'campo_fecha' => 'gastos.fecha',
                'campo_monto' => 'gastos.monto',
                'orden' => [['gastos.created_at', 'desc'], ['gastos.numero_comprobante', 'desc']],
                'columnas' => [
                    'numero_comprobante' => ['etiqueta' => 'Comprobante', 'tipo' => 'text'],
                    'categoria' => ['etiqueta' => 'Categoría', 'tipo' => 'text'],
                    'metodo' => ['etiqueta' => 'Método', 'tipo' => 'text'],
                    'fecha' => ['etiqueta' => 'Fecha', 'tipo' => 'date'],
                    'monto' => ['etiqueta' => 'Monto', 'tipo' => 'money'],
                    'descripcion' => ['etiqueta' => 'Descripción', 'tipo' => 'text'],
                ],
            ],
            'clientes' => [
                'nombre' => 'Clientes',
                'descripcion' => 'Localice clientes por identificación o contacto.',
                'permiso' => 'ver_clientes',
                'sigla' => 'CL',
                'placeholder' => 'Nombre, identificación, correo o teléfono',
                'campo_fecha' => null,
                'campo_monto' => null,
                'orden' => [['created_at', 'desc'], ['id', 'desc']],
                'columnas' => [
                    'identificacion' => ['etiqueta' => 'Identificación', 'tipo' => 'text'],
                    'nombre' => ['etiqueta' => 'Nombre', 'tipo' => 'text'],
                    'email' => ['etiqueta' => 'Correo', 'tipo' => 'text'],
                    'telefono' => ['etiqueta' => 'Teléfono', 'tipo' => 'text'],
                    'estado' => ['etiqueta' => 'Estado', 'tipo' => 'boolean'],
                ],
            ],
            'proveedores' => [
                'nombre' => 'Proveedores',
                'descripcion' => 'Localice proveedores por identificación o empresa.',
                'permiso' => 'ver_proveedores',
                'sigla' => 'PR',
                'placeholder' => 'Nombre, identificación, empresa o correo',
                'campo_fecha' => null,
                'campo_monto' => null,
                'orden' => [['created_at', 'desc'], ['id', 'desc']],
                'columnas' => [
                    'identificacion' => ['etiqueta' => 'Identificación', 'tipo' => 'text'],
                    'nombre' => ['etiqueta' => 'Nombre', 'tipo' => 'text'],
                    'empresa' => ['etiqueta' => 'Empresa', 'tipo' => 'text'],
                    'correo' => ['etiqueta' => 'Correo', 'tipo' => 'text'],
                    'telefono' => ['etiqueta' => 'Teléfono', 'tipo' => 'text'],
                    'estado' => ['etiqueta' => 'Estado', 'tipo' => 'boolean'],
                ],
            ],
            'productos' => [
                'nombre' => 'Productos',
                'descripcion' => 'Consulte códigos, categorías, precios y existencias.',
                'permiso' => 'ver_productos',
                'sigla' => 'PD',
                'placeholder' => 'Código, producto o categoría',
                'campo_fecha' => null,
                'campo_monto' => null,
                'orden' => [['productos.created_at', 'desc'], ['productos.id', 'desc']],
                'columnas' => [
                    'codigo_barras' => ['etiqueta' => 'Código', 'tipo' => 'text'],
                    'nombre' => ['etiqueta' => 'Producto', 'tipo' => 'text'],
                    'categoria' => ['etiqueta' => 'Categoría', 'tipo' => 'text'],
                    'stock' => ['etiqueta' => 'Stock', 'tipo' => 'number'],
                    'stock_minimo' => ['etiqueta' => 'Mínimo', 'tipo' => 'number'],
                    'precio' => ['etiqueta' => 'Costo mayorista', 'tipo' => 'money'],
                    'porcentaje_ganancia' => ['etiqueta' => 'Ganancia', 'tipo' => 'percent'],
                    'estado' => ['etiqueta' => 'Estado', 'tipo' => 'boolean'],
                ],
            ],
            'inventario' => [
                'nombre' => 'Movimientos de inventario',
                'descripcion' => 'Verifique entradas, salidas, ajustes y devoluciones.',
                'permiso' => 'ver_inventario',
                'sigla' => 'IV',
                'placeholder' => 'Referencia, código, producto o tipo',
                'campo_fecha' => 'movimientos_inventario.fecha',
                'campo_monto' => null,
                'orden' => [['movimientos_inventario.created_at', 'desc'], ['movimientos_inventario.referencia_movimiento', 'desc']],
                'columnas' => [
                    'referencia_movimiento' => ['etiqueta' => 'Referencia', 'tipo' => 'text'],
                    'codigo_barras' => ['etiqueta' => 'Código', 'tipo' => 'text'],
                    'producto' => ['etiqueta' => 'Producto', 'tipo' => 'text'],
                    'tipo' => ['etiqueta' => 'Movimiento', 'tipo' => 'status'],
                    'cantidad' => ['etiqueta' => 'Cantidad', 'tipo' => 'number'],
                    'fecha' => ['etiqueta' => 'Fecha', 'tipo' => 'date'],
                    'descripcion' => ['etiqueta' => 'Descripción', 'tipo' => 'text'],
                ],
            ],
            'cuentas_cobrar' => [
                'nombre' => 'Cuentas por cobrar',
                'descripcion' => 'Revise saldos de clientes y vencimientos.',
                'permiso' => 'ver_cuentas_cobrar',
                'sigla' => 'CC',
                'placeholder' => 'Factura, cliente o estado',
                'campo_fecha' => 'cuentas_cobrar.fecha_emision',
                'campo_monto' => 'cuentas_cobrar.saldo_pendiente',
                'orden' => [['cuentas_cobrar.created_at', 'desc'], ['cuentas_cobrar.fecha_vencimiento', 'desc']],
                'columnas' => [
                    'numero_factura' => ['etiqueta' => 'Factura', 'tipo' => 'text'],
                    'cliente' => ['etiqueta' => 'Cliente', 'tipo' => 'text'],
                    'fecha_emision' => ['etiqueta' => 'Emisión', 'tipo' => 'date'],
                    'fecha_vencimiento' => ['etiqueta' => 'Vencimiento', 'tipo' => 'date'],
                    'monto_original' => ['etiqueta' => 'Original', 'tipo' => 'money'],
                    'saldo_pendiente' => ['etiqueta' => 'Saldo', 'tipo' => 'money'],
                    'estado' => ['etiqueta' => 'Estado', 'tipo' => 'status'],
                ],
            ],
            'cuentas_pagar' => [
                'nombre' => 'Cuentas por pagar',
                'descripcion' => 'Revise obligaciones con proveedores y vencimientos.',
                'permiso' => 'ver_cuentas_pagar',
                'sigla' => 'CPG',
                'placeholder' => 'Compra, proveedor o estado',
                'campo_fecha' => 'cuentas_pagar.fecha_emision',
                'campo_monto' => 'cuentas_pagar.saldo_pendiente',
                'orden' => [['cuentas_pagar.created_at', 'desc'], ['cuentas_pagar.fecha_vencimiento', 'desc']],
                'columnas' => [
                    'numero_compra' => ['etiqueta' => 'Compra', 'tipo' => 'text'],
                    'proveedor' => ['etiqueta' => 'Proveedor', 'tipo' => 'text'],
                    'fecha_emision' => ['etiqueta' => 'Emisión', 'tipo' => 'date'],
                    'fecha_vencimiento' => ['etiqueta' => 'Vencimiento', 'tipo' => 'date'],
                    'monto_original' => ['etiqueta' => 'Original', 'tipo' => 'money'],
                    'saldo_pendiente' => ['etiqueta' => 'Saldo', 'tipo' => 'money'],
                    'estado' => ['etiqueta' => 'Estado', 'tipo' => 'status'],
                ],
            ],
            'asientos' => [
                'nombre' => 'Asientos contables',
                'descripcion' => 'Verifique asientos automáticos, totales y estados.',
                'permiso' => 'ver_contabilidad',
                'sigla' => 'AC',
                'placeholder' => 'Número de asiento, descripción o estado',
                'campo_fecha' => 'asientos_contables.fecha',
                'campo_monto' => 'asientos_contables.total_debe',
                'orden' => [['asientos_contables.created_at', 'desc'], ['asientos_contables.numero_asiento', 'desc']],
                'columnas' => [
                    'numero_asiento' => ['etiqueta' => 'Asiento', 'tipo' => 'text'],
                    'fecha' => ['etiqueta' => 'Fecha', 'tipo' => 'date'],
                    'descripcion' => ['etiqueta' => 'Descripción', 'tipo' => 'text'],
                    'total_debe' => ['etiqueta' => 'Debe', 'tipo' => 'money'],
                    'total_haber' => ['etiqueta' => 'Haber', 'tipo' => 'money'],
                    'estado' => ['etiqueta' => 'Estado', 'tipo' => 'status'],
                ],
            ],
            'bancos' => [
                'nombre' => 'Cuentas bancarias',
                'descripcion' => 'Consulte cuentas, monedas y saldos disponibles.',
                'permiso' => 'ver_contabilidad',
                'sigla' => 'BC',
                'placeholder' => 'Banco, número o código contable',
                'campo_fecha' => null,
                'campo_monto' => 'cuentas_bancarias.saldo',
                'orden' => [['cuentas_bancarias.created_at', 'desc'], ['cuentas_bancarias.id', 'desc']],
                'columnas' => [
                    'banco_nombre' => ['etiqueta' => 'Banco', 'tipo' => 'text'],
                    'numero_cuenta' => ['etiqueta' => 'Cuenta', 'tipo' => 'text'],
                    'codigo_cuenta' => ['etiqueta' => 'Cuenta contable', 'tipo' => 'text'],
                    'moneda' => ['etiqueta' => 'Moneda', 'tipo' => 'text'],
                    'saldo' => ['etiqueta' => 'Saldo', 'tipo' => 'money'],
                    'estado' => ['etiqueta' => 'Estado', 'tipo' => 'boolean'],
                ],
            ],
        ];

        return collect($catalogo)
            ->filter(fn ($configuracion) => Auth::user()->tienePermiso($configuracion['permiso']))
            ->all();
    }

    private function autorizar(): void
    {
        abort_unless(
            Auth::user()?->tienePermiso('ver_consultas'),
            403,
            'No tiene permiso para acceder al módulo de consultas.'
        );
    }
}
