<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Estado;
use App\Models\CuentaPagar;
use App\Models\PagoCuentaPagar;
use App\Models\PlazoCompra;
use App\Models\Factura;
use App\Models\DetalleFactura;
use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\CuentaCobrar;
use App\Models\PlazoVenta;
use App\Models\CuentaBancaria;
use App\Services\BitacoraService;
use App\Services\InventarioService;
use App\Services\AsientoContableService;
use App\Services\BancoService;
use App\Services\CodigoProductoService;
use App\Services\IngresoAutomaticoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CompraController extends Controller
{
    public function __construct(CodigoProductoService $codigoProductoService)
    {
        $codigoProductoService->asegurarEstructuraPrecios();
        $codigoProductoService->convertirPreciosExistentes();
    }

    public function index()
    {
        $compras = Compra::with(['proveedor', 'estado', 'metodoPago', 'detalles.producto', 'plazos'])
            ->orderByDesc('created_at')
            ->orderByDesc('fecha')
            ->orderByDesc('numero_compra')
            ->get();

        return view('compras.index', compact('compras'));
    }

    /**
     * Sección de compras de clientes externos (ventas): muestra únicamente
     * las facturas de venta, separadas de las compras a proveedores.
     */
    public function clientes()
    {
        $facturas = Factura::with(['cliente', 'estado', 'metodoPago', 'detalles.producto'])
            ->orderByDesc('created_at')
            ->orderByDesc('fecha')
            ->orderByDesc('numero_factura')
            ->get();

        return view('compras.clientes', compact('facturas'));
    }

    public function create()
    {
        $proveedores = Proveedor::orderBy('nombre')->get();

        $productos = Producto::orderBy('nombre')->get();

        $clientes = Cliente::orderBy('nombre')->get();

        $metodosPago = MetodoPago::orderBy('nombre')->get();

        $tiposComprobante = DB::table('tipos_comprobante')
            ->orderBy('nombre')
            ->get();

        $cuentasBancarias = CuentaBancaria::where('estado', true)
            ->orderBy('banco_nombre')
            ->get();

        return view('compras.create', compact(
            'proveedores',
            'productos',
            'clientes',
            'metodosPago',
            'tiposComprobante',
            'cuentasBancarias'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_operacion' => 'required|in:proveedor,cliente',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
        ], [
            'productos.required' => 'Debe agregar al menos un producto.',
            'productos.min' => 'Debe agregar al menos un producto.',
            'productos.*.producto_id.required' => 'Seleccione un producto en cada línea.',
            'productos.*.cantidad.required' => 'Indique la cantidad de cada producto.',
        ]);

        $lineas = array_values($request->productos);

        // No se permite el mismo producto repetido (clave primaria del detalle).
        $idsProductos = array_column($lineas, 'producto_id');
        if (count($idsProductos) !== count(array_unique($idsProductos))) {
            throw ValidationException::withMessages([
                'productos' => 'No repita el mismo producto; ajuste la cantidad en una sola línea.',
            ]);
        }

        $lineas = $this->aplicarPreciosProductos(
            $lineas,
            $request->tipo_operacion === 'cliente'
        );

        if ($request->tipo_operacion === 'cliente') {
            return $this->registrarVentaCliente($request, $lineas);
        }

        return $this->registrarCompraProveedor($request, $lineas);
    }

    /**
     * Compra a proveedor: incrementa el inventario y genera el comprobante
     * de compra con su cuenta por pagar (contado o crédito con plazos).
     */
    private function registrarCompraProveedor(Request $request, array $lineas)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'tipo_compra' => 'required|in:contado,credito',
        ]);

        $esCredito = $request->tipo_compra === 'credito';

        // Las compras de contado exigen una cuenta bancaria de origen del pago.
        if (! $esCredito) {
            $request->validate([
                'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            ], [
                'cuenta_bancaria_id.required' => 'Seleccione la cuenta bancaria desde la cual se pagará la compra de contado.',
                'cuenta_bancaria_id.exists' => 'La cuenta bancaria seleccionada no es válida.',
            ]);
        }

        $subtotal = 0;
        foreach ($lineas as $linea) {
            $subtotal += $linea['precio_unitario'] * $linea['cantidad'];
        }

        $impuesto = $subtotal * Producto::IMPUESTO;
        $total = round($subtotal + $impuesto, 2);

        $cuotas = [];

        if ($esCredito) {
            $request->validate([
                'cuotas' => 'required|array|min:1',
                'cuotas.*.fecha_vencimiento' => 'required|date',
                'cuotas.*.monto' => 'required|numeric|min:0.01',
            ], [
                'cuotas.required' => 'Debe registrar al menos un plazo de pago para una compra a crédito.',
                'cuotas.min' => 'Debe registrar al menos un plazo de pago para una compra a crédito.',
                'cuotas.*.fecha_vencimiento.required' => 'Cada cuota debe tener una fecha de vencimiento.',
                'cuotas.*.monto.required' => 'Cada cuota debe tener un monto.',
                'cuotas.*.monto.min' => 'El monto de cada cuota debe ser mayor a cero.',
            ]);

            $cuotas = array_values($request->cuotas);

            $sumaCuotas = round(array_sum(array_column($cuotas, 'monto')), 2);

            if (abs($sumaCuotas - $total) > 0.01) {
                throw ValidationException::withMessages([
                    'cuotas' => 'La suma de las cuotas (₡' . number_format($sumaCuotas, 2)
                        . ') debe ser igual al total de la compra (₡' . number_format($total, 2) . ').',
                ]);
            }
        }

        DB::transaction(function () use ($request, $lineas, $subtotal, $impuesto, $total, $esCredito, $cuotas) {
            $estadoPendiente = Estado::where('nombre', 'pendiente')->first();
            $estadoPagado = Estado::where('nombre', 'pagado')->first();

            $numeroCompra = 'COM-' . now()->format('YmdHis') . '-' . strtoupper(str()->random(6));

            Compra::create([
                'numero_compra' => $numeroCompra,
                'proveedor_id' => $request->proveedor_id,
                'usuario_id' => Auth::id(),
                // Contado: la compra queda pagada de inmediato.
                // Crédito: queda pendiente hasta liquidar la cuenta por pagar.
                'estado_id' => $esCredito
                    ? ($estadoPendiente?->id ?? 1)
                    : ($estadoPagado?->id ?? 2),
                'metodo_pago_id' => $request->metodo_pago_id,
                'cuenta_bancaria_id' => $esCredito ? null : $request->cuenta_bancaria_id,
                'tipo_compra' => $request->tipo_compra,
                'fecha' => now(),
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'total' => $total,
            ]);

            foreach ($lineas as $linea) {
                $producto = Producto::findOrFail($linea['producto_id']);

                DetalleCompra::create([
                    'numero_compra' => $numeroCompra,
                    'proveedor_id' => $request->proveedor_id,
                    'producto_id' => $producto->id,
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'subtotal' => $linea['precio_unitario'] * $linea['cantidad'],
                ]);

                $producto->stock += $linea['cantidad'];
                $producto->save();

                // Entrada automática al inventario por la compra a proveedor.
                InventarioService::registrarMovimiento(
                    $producto->id,
                    'Entrada',
                    $linea['cantidad'],
                    "Compra a proveedor {$numeroCompra}",
                    $numeroCompra
                );
            }

            // Solo las compras a crédito generan una cuenta por pagar
            // pendiente con sus plazos. Las de contado no dejan saldo.
            if ($esCredito) {
                $fechaVencimiento = collect($cuotas)->min('fecha_vencimiento');

                CuentaPagar::create([
                    'numero_compra' => $numeroCompra,
                    'proveedor_id' => $request->proveedor_id,
                    'monto_original' => $total,
                    'saldo_pendiente' => $total,
                    'fecha_emision' => now(),
                    'fecha_vencimiento' => $fechaVencimiento,
                    'estado_id' => $estadoPendiente?->id ?? 1,
                ]);

                foreach ($cuotas as $indice => $cuota) {
                    PlazoCompra::create([
                        'numero_compra' => $numeroCompra,
                        'proveedor_id' => $request->proveedor_id,
                        'numero_cuota' => $indice + 1,
                        'fecha_vencimiento' => $cuota['fecha_vencimiento'],
                        'monto' => $cuota['monto'],
                        'saldo_pendiente' => $cuota['monto'],
                    ]);
                }
            }

            // Registro contable y de tesorería automático.
            $codigoInventario = '1.1.4.1'; // Inventario en Bodega
            $codigoBancos = '1.1.2';       // Bancos
            $codigoPorPagar = '2.1.1';     // Cuentas por Pagar

            if ($esCredito) {
                // Crédito: no afecta el banco. Debe Inventario / Haber Cuentas por Pagar.
                AsientoContableService::generar(now(), "Compra a crédito {$numeroCompra}", [
                    ['codigo_cuenta' => $codigoInventario, 'debe' => $total, 'haber' => 0, 'descripcion' => "Compra a crédito {$numeroCompra}"],
                    ['codigo_cuenta' => $codigoPorPagar, 'debe' => 0, 'haber' => $total, 'descripcion' => "Cuenta por pagar {$numeroCompra}"],
                ], 'COMPRA:' . $numeroCompra);
            } else {
                // Contado: descuenta el banco y genera Debe Inventario / Haber Bancos.
                $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($request->cuenta_bancaria_id);

                BancoService::debitar(
                    $cuentaBancaria,
                    $total,
                    "Compra de contado {$numeroCompra}",
                    $numeroCompra
                );

                AsientoContableService::generar(now(), "Compra de contado {$numeroCompra}", [
                    ['codigo_cuenta' => $codigoInventario, 'debe' => $total, 'haber' => 0, 'descripcion' => "Compra de contado {$numeroCompra}"],
                    ['codigo_cuenta' => $codigoBancos, 'debe' => 0, 'haber' => $total, 'descripcion' => "Pago desde {$cuentaBancaria->banco_nombre}"],
                ], 'COMPRA:' . $numeroCompra);
            }
        });

        $mensaje = $esCredito
            ? 'Compra a crédito registrada. Se generó la cuenta por pagar y se actualizó el inventario.'
            : 'Compra al contado registrada y pagada. Se actualizó el inventario.';

        return redirect()
            ->route('compras.index')
            ->with('success', $mensaje);
    }

    /**
     * Compra de cliente externo (venta): descuenta el inventario y genera
     * automáticamente la factura de venta con su cuenta por cobrar si aplica.
     */
    private function registrarVentaCliente(Request $request, array $lineas)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'tipo_comprobante_id' => 'required|exists:tipos_comprobante,id',
            'tipo_compra' => 'required|in:contado,credito',
            'descuento' => 'nullable|numeric|min:0',
        ]);

        $esCredito = $request->tipo_compra === 'credito';

        if (
            ! $esCredito
            && AsientoContableService::requiereCuentaBancaria((int) $request->metodo_pago_id)
        ) {
            $request->validate([
                'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            ]);
        }

        $subtotal = 0;
        foreach ($lineas as $linea) {
            $subtotal += $linea['precio_unitario'] * $linea['cantidad'];
        }

        $impuesto = $subtotal * Producto::IMPUESTO;
        $descuento = $request->descuento ?? 0;
        $total = round(($subtotal + $impuesto) - $descuento, 2);

        if ($total < 0) {
            throw ValidationException::withMessages([
                'descuento' => 'El descuento no puede ser mayor al total de la venta.',
            ]);
        }

        if ($descuento > $subtotal) {
            throw ValidationException::withMessages([
                'descuento' => 'El descuento no puede ser mayor al subtotal de la venta.',
            ]);
        }

        // Las ventas a crédito también registran plazos (cuotas) de cobro.
        $cuotas = [];
        if ($esCredito) {
            $request->validate([
                'cuotas' => 'required|array|min:1',
                'cuotas.*.fecha_vencimiento' => 'required|date',
                'cuotas.*.monto' => 'required|numeric|min:0.01',
            ], [
                'cuotas.required' => 'Debe registrar al menos un plazo de pago para una venta a crédito.',
                'cuotas.min' => 'Debe registrar al menos un plazo de pago para una venta a crédito.',
                'cuotas.*.fecha_vencimiento.required' => 'Cada cuota debe tener una fecha de vencimiento.',
                'cuotas.*.monto.required' => 'Cada cuota debe tener un monto.',
                'cuotas.*.monto.min' => 'El monto de cada cuota debe ser mayor a cero.',
            ]);

            $cuotas = array_values($request->cuotas);

            $sumaCuotas = round(array_sum(array_column($cuotas, 'monto')), 2);

            if (abs($sumaCuotas - $total) > 0.01) {
                throw ValidationException::withMessages([
                    'cuotas' => 'La suma de las cuotas (₡' . number_format($sumaCuotas, 2)
                        . ') debe ser igual al total de la venta (₡' . number_format($total, 2) . ').',
                ]);
            }
        }

        // Validar stock disponible antes de crear la factura.
        $costoInventario = 0.0;

        foreach ($lineas as $linea) {
            $producto = Producto::findOrFail($linea['producto_id']);
            $costoInventario += (float) $producto->precio * $linea['cantidad'];
            if ($producto->stock < $linea['cantidad']) {
                throw ValidationException::withMessages([
                    'productos' => "No hay suficiente stock de {$producto->nombre} (disponible: {$producto->stock}).",
                ]);
            }
        }

        $factura = DB::transaction(function () use ($request, $lineas, $subtotal, $impuesto, $descuento, $total, $esCredito, $cuotas, $costoInventario) {
            $estadoPendiente = Estado::where('nombre', 'pendiente')->first();
            $estadoPagado = Estado::where('nombre', 'pagado')->first();

            $numeroFactura = 'FAC-' . now()->format('YmdHis') . '-' . strtoupper(str()->random(6));

            $factura = Factura::create([
                'numero_factura' => $numeroFactura,
                'cliente_id' => $request->cliente_id,
                'usuario_id' => Auth::id(),
                'metodo_pago_id' => $request->metodo_pago_id,
                'estado_id' => $esCredito
                    ? ($estadoPendiente?->id ?? 1)
                    : ($estadoPagado?->id ?? 2),
                'tipo_comprobante_id' => $request->tipo_comprobante_id,
                'fecha' => now(),
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'descuento' => $descuento,
                'total' => $total,
            ]);

            foreach ($lineas as $linea) {
                $producto = Producto::findOrFail($linea['producto_id']);

                DetalleFactura::create([
                    'numero_factura' => $numeroFactura,
                    'cliente_id' => $request->cliente_id,
                    'producto_id' => $producto->id,
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'subtotal' => $linea['precio_unitario'] * $linea['cantidad'],
                ]);

                $producto->stock -= $linea['cantidad'];
                $producto->save();

                // Salida automática del inventario por la venta a cliente.
                InventarioService::registrarMovimiento(
                    $producto->id,
                    'Salida',
                    $linea['cantidad'],
                    "Venta a cliente {$numeroFactura}",
                    $numeroFactura
                );
            }

            if ($esCredito) {
                $fechaVencimiento = collect($cuotas)->min('fecha_vencimiento');

                CuentaCobrar::create([
                    'numero_factura' => $numeroFactura,
                    'cliente_id' => $request->cliente_id,
                    'monto_original' => $total,
                    'saldo_pendiente' => $total,
                    'fecha_emision' => now(),
                    'fecha_vencimiento' => $fechaVencimiento,
                    'estado_id' => $estadoPendiente?->id ?? 1,
                ]);

                foreach ($cuotas as $indice => $cuota) {
                    PlazoVenta::create([
                        'numero_factura' => $numeroFactura,
                        'cliente_id' => $request->cliente_id,
                        'numero_cuota' => $indice + 1,
                        'fecha_vencimiento' => $cuota['fecha_vencimiento'],
                        'monto' => $cuota['monto'],
                        'saldo_pendiente' => $cuota['monto'],
                    ]);
                }
            }

            AsientoContableService::registrarVenta(
                now(),
                $numeroFactura,
                (float) $subtotal,
                (float) $impuesto,
                (float) $descuento,
                round($costoInventario, 2),
                $esCredito,
                (int) $request->metodo_pago_id
            );

            if (
                ! $esCredito
                && AsientoContableService::requiereCuentaBancaria((int) $request->metodo_pago_id)
            ) {
                $cuentaBancaria = CuentaBancaria::lockForUpdate()
                    ->findOrFail($request->cuenta_bancaria_id);

                BancoService::acreditar(
                    $cuentaBancaria,
                    (float) $total,
                    "Venta {$numeroFactura}",
                    $numeroFactura
                );
            }

            if (! $esCredito) {
                IngresoAutomaticoService::registrarVentaContado(
                    $numeroFactura,
                    now(),
                    (int) Auth::id(),
                    (int) $request->metodo_pago_id,
                    (float) $total
                );
            }

            return $factura;
        });

        // Enviar la factura por correo si es electrónica y el cliente tiene email.
        $tipoComprobante = DB::table('tipos_comprobante')
            ->where('id', $request->tipo_comprobante_id)
            ->first();

        $cliente = Cliente::find($request->cliente_id);

        if (
            $tipoComprobante &&
            $tipoComprobante->nombre === 'Factura Electrónica' &&
            $cliente &&
            $cliente->email
        ) {
            Mail::raw(
                "Estimado/a {$cliente->nombre},\n\n" .
                "Adjuntamos la información de su factura electrónica.\n\n" .
                "Número de factura: {$factura->numero_factura}\n" .
                "Subtotal: ₡" . number_format($factura->subtotal, 2) . "\n" .
                "Impuesto: ₡" . number_format($factura->impuesto, 2) . "\n" .
                "Descuento: ₡" . number_format($factura->descuento, 2) . "\n" .
                "Total: ₡" . number_format($factura->total, 2) . "\n\n" .
                "Gracias por su compra.\n\n" .
                "Distribuidora Ipacaraí",
                function ($message) use ($cliente, $factura) {
                    $message->to($cliente->email)
                        ->subject('Factura Electrónica ' . $factura->numero_factura);
                }
            );
        }

        BitacoraService::registrar('crear', 'facturas', "Factura {$factura->numero_factura} generada desde compras por ₡" . number_format($factura->total, 2));

        $mensaje = $esCredito
            ? "Venta a crédito registrada. Se generó la factura {$factura->numero_factura} y su cuenta por cobrar, y se actualizó el inventario."
            : "Venta al contado registrada y pagada. Se generó la factura {$factura->numero_factura} y se actualizó el inventario.";

        return redirect()
            ->route('compras.clientes')
            ->with('success', $mensaje);
    }

    public function edit(Compra $compra)
    {
        $proveedores = Proveedor::orderBy('nombre')->get();

        $productos = Producto::orderBy('nombre')->get();

        $detalles = $compra->detalles()->get();

        return view('compras.edit', compact(
            'compra',
            'proveedores',
            'productos',
            'detalles'
        ));
    }

    public function update(Request $request, Compra $compra)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
        ], [
            'productos.required' => 'Debe agregar al menos un producto a la compra.',
            'productos.min' => 'Debe agregar al menos un producto a la compra.',
        ]);

        $lineas = array_values($request->productos);

        $idsProductos = array_column($lineas, 'producto_id');
        if (count($idsProductos) !== count(array_unique($idsProductos))) {
            throw ValidationException::withMessages([
                'productos' => 'No repita el mismo producto en la compra; ajuste la cantidad en una sola línea.',
            ]);
        }

        $preciosExistentes = $compra->detalles()
            ->pluck('precio_unitario', 'producto_id')
            ->map(fn ($precio) => (float) $precio)
            ->all();

        $lineas = $this->aplicarPreciosProductos($lineas, false, $preciosExistentes);

        DB::transaction(function () use ($request, $compra, $lineas) {
            $compra = Compra::where('numero_compra', $compra->numero_compra)
                ->where('proveedor_id', $compra->proveedor_id)
                ->lockForUpdate()
                ->firstOrFail();
            $referenciaAjuste = 'AJU-' . now()->format('YmdHis');
            $totalAnterior = (float) $compra->total;
            $cuentaPagar = CuentaPagar::where('numero_compra', $compra->numero_compra)
                ->where('proveedor_id', $compra->proveedor_id)
                ->lockForUpdate()
                ->first();

            if ($cuentaPagar && (int) $compra->proveedor_id !== (int) $request->proveedor_id) {
                throw ValidationException::withMessages([
                    'proveedor_id' => 'No se puede cambiar el proveedor de una compra que tiene una cuenta por pagar.',
                ]);
            }

            if (
                $cuentaPagar
                && PagoCuentaPagar::where('numero_compra', $compra->numero_compra)
                    ->where('proveedor_id', $compra->proveedor_id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'productos' => 'No se puede editar una compra que ya tiene pagos registrados.',
                ]);
            }

            if ($cuentaPagar && (float) $cuentaPagar->saldo_pendiente <= 0) {
                throw ValidationException::withMessages([
                    'productos' => 'No se puede editar una compra que ya fue liquidada.',
                ]);
            }

            // Revertir el stock de los productos actuales y eliminarlos.
            foreach ($compra->detalles()->get() as $detalleAnterior) {
                $productoAnterior = Producto::find($detalleAnterior->producto_id);

                if ($productoAnterior) {
                    $productoAnterior->stock -= $detalleAnterior->cantidad;
                    $productoAnterior->save();

                    // Salida por reversión de la compra editada.
                    InventarioService::registrarMovimiento(
                        $productoAnterior->id,
                        'Ajuste negativo',
                        $detalleAnterior->cantidad,
                        "Reversión por edición de compra {$compra->numero_compra}",
                        $referenciaAjuste
                    );
                }

                $detalleAnterior->delete();
            }

            $subtotal = 0;
            foreach ($lineas as $linea) {
                $subtotal += $linea['precio_unitario'] * $linea['cantidad'];
            }

            $impuesto = $subtotal * Producto::IMPUESTO;
            $total = round($subtotal + $impuesto, 2);

            $compra->update([
                'proveedor_id' => $request->proveedor_id,
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'total' => $total,
            ]);

            foreach ($lineas as $linea) {
                $producto = Producto::findOrFail($linea['producto_id']);

                DetalleCompra::create([
                    'numero_compra' => $compra->numero_compra,
                    'proveedor_id' => $request->proveedor_id,
                    'producto_id' => $producto->id,
                    'cantidad' => $linea['cantidad'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'subtotal' => $linea['precio_unitario'] * $linea['cantidad'],
                ]);

                $producto->stock += $linea['cantidad'];
                $producto->save();

                // Entrada por los productos de la compra editada.
                InventarioService::registrarMovimiento(
                    $producto->id,
                    'Ajuste positivo',
                    $linea['cantidad'],
                    "Actualización de compra {$compra->numero_compra}",
                    $referenciaAjuste
                );
            }

            if ($cuentaPagar) {
                $cuentaPagar->update([
                    'monto_original' => $total,
                    'saldo_pendiente' => $total,
                ]);

                $plazos = PlazoCompra::where('numero_compra', $compra->numero_compra)
                    ->where('proveedor_id', $compra->proveedor_id)
                    ->orderBy('numero_cuota')
                    ->get();

                if ($plazos->isNotEmpty()) {
                    $montoBase = floor(($total / $plazos->count()) * 100) / 100;

                    foreach ($plazos as $indice => $plazo) {
                        $monto = $indice === $plazos->count() - 1
                            ? round($total - ($montoBase * ($plazos->count() - 1)), 2)
                            : $montoBase;

                        $plazo->update([
                            'monto' => $monto,
                            'saldo_pendiente' => $monto,
                        ]);
                    }
                }
            } elseif ($compra->cuenta_bancaria_id) {
                $cuentaBancaria = CuentaBancaria::lockForUpdate()
                    ->findOrFail($compra->cuenta_bancaria_id);
                $diferencia = round($total - $totalAnterior, 2);

                if ($diferencia > 0) {
                    BancoService::debitar(
                        $cuentaBancaria,
                        $diferencia,
                        "Ajuste de compra {$compra->numero_compra}",
                        $compra->numero_compra
                    );
                } elseif ($diferencia < 0) {
                    BancoService::acreditar(
                        $cuentaBancaria,
                        abs($diferencia),
                        "Reintegro por ajuste de compra {$compra->numero_compra}",
                        $compra->numero_compra
                    );
                }
            }

            $descripcionAsiento = $cuentaPagar
                ? "Compra a crédito {$compra->numero_compra}"
                : "Compra de contado {$compra->numero_compra}";

            AsientoContableService::generar($compra->fecha, $descripcionAsiento, [
                ['codigo_cuenta' => '1.1.4.1', 'debe' => $total, 'haber' => 0],
                [
                    'codigo_cuenta' => $cuentaPagar ? '2.1.1' : '1.1.2',
                    'debe' => 0,
                    'haber' => $total,
                ],
            ], 'COMPRA:' . $compra->numero_compra);
        });

        return redirect()
            ->route('compras.index')
            ->with('success', 'Compra actualizada correctamente.');
    }

    public function destroy(Compra $compra)
    {
        DB::transaction(function () use ($compra) {
            $compra = Compra::where('numero_compra', $compra->numero_compra)
                ->where('proveedor_id', $compra->proveedor_id)
                ->lockForUpdate()
                ->firstOrFail();
            $cuentaPagar = CuentaPagar::where('numero_compra', $compra->numero_compra)
                ->where('proveedor_id', $compra->proveedor_id)
                ->lockForUpdate()
                ->first();
            $detalles = $compra->detalles()->get();
            $productos = Producto::whereIn('id', $detalles->pluck('producto_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if (
                PagoCuentaPagar::where('numero_compra', $compra->numero_compra)
                    ->where('proveedor_id', $compra->proveedor_id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'compra' => 'No se puede eliminar una compra que ya tiene pagos registrados.',
                ]);
            }

            if ($cuentaPagar && (float) $cuentaPagar->saldo_pendiente <= 0) {
                throw ValidationException::withMessages([
                    'compra' => 'No se puede eliminar una compra que ya fue liquidada.',
                ]);
            }

            foreach ($detalles as $detalle) {
                $producto = $productos->get($detalle->producto_id);

                if ($producto && $producto->stock < $detalle->cantidad) {
                    throw ValidationException::withMessages([
                        'compra' => "No se puede eliminar la compra porque el stock actual de {$producto->nombre} "
                            . "es menor que las {$detalle->cantidad} unidades recibidas.",
                    ]);
                }
            }

            if (! $cuentaPagar && $compra->cuenta_bancaria_id) {
                $cuentaBancaria = CuentaBancaria::lockForUpdate()
                    ->findOrFail($compra->cuenta_bancaria_id);

                BancoService::acreditar(
                    $cuentaBancaria,
                    (float) $compra->total,
                    "Reintegro por eliminación de compra {$compra->numero_compra}",
                    $compra->numero_compra
                );
            }

            AsientoContableService::revertir(
                now(),
                'COMPRA:' . $compra->numero_compra,
                'REVERSO-COMPRA:' . $compra->numero_compra,
                "Reversión de compra {$compra->numero_compra}"
            );

            foreach ($detalles as $detalle) {
                $producto = $productos->get($detalle->producto_id);
                if ($producto) {
                    $producto->stock -= $detalle->cantidad;
                    $producto->save();
                }
            }

            DB::table('movimientos_inventario')
                ->where('referencia_movimiento', $compra->numero_compra)
                ->delete();

            Compra::where('numero_compra', $compra->numero_compra)
                ->where('proveedor_id', $compra->proveedor_id)
                ->delete();
        });

        return redirect()
            ->route('compras.index')
            ->with('success', 'Compra eliminada correctamente.');
    }

    private function aplicarPreciosProductos(
        array $lineas,
        bool $esVentaCliente,
        array $preciosExistentes = []
    ): array
    {
        $productos = Producto::whereIn('id', array_column($lineas, 'producto_id'))
            ->get()
            ->keyBy('id');

        return array_map(function (array $linea) use ($productos, $esVentaCliente, $preciosExistentes) {
            $producto = $productos->get($linea['producto_id']);

            if (! $producto) {
                throw ValidationException::withMessages([
                    'productos' => 'Uno de los productos seleccionados ya no está disponible.',
                ]);
            }

            $linea['precio_unitario'] = $preciosExistentes[$producto->id]
                ?? ($esVentaCliente
                    ? $producto->precio_venta_sin_impuesto
                    : (float) $producto->precio);

            return $linea;
        }, $lineas);
    }
}