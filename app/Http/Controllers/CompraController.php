<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Estado;
use App\Models\CuentaPagar;
use App\Models\PlazoCompra;
use App\Models\Factura;
use App\Models\DetalleFactura;
use App\Models\Cliente;
use App\Models\MetodoPago;
use App\Models\CuentaCobrar;
use App\Models\PlazoVenta;
use App\Models\CuentaBancaria;
use App\Models\MovimientoBancario;
use App\Services\BitacoraService;
use App\Services\InventarioService;
use App\Services\AsientoContableService;
use App\Services\BancoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CompraController extends Controller
{
    public function index()
    {
        $compras = Compra::with(['proveedor', 'estado', 'metodoPago', 'detalles.producto', 'plazos'])
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
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
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
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
            'productos.*.precio_unitario' => 'required|numeric|min:0',
        ], [
            'productos.required' => 'Debe agregar al menos un producto.',
            'productos.min' => 'Debe agregar al menos un producto.',
            'productos.*.producto_id.required' => 'Seleccione un producto en cada línea.',
            'productos.*.cantidad.required' => 'Indique la cantidad de cada producto.',
            'productos.*.precio_unitario.required' => 'Indique el precio unitario de cada producto.',
        ]);

        $lineas = array_values($request->productos);

        // No se permite el mismo producto repetido (clave primaria del detalle).
        $idsProductos = array_column($lineas, 'producto_id');
        if (count($idsProductos) !== count(array_unique($idsProductos))) {
            throw ValidationException::withMessages([
                'productos' => 'No repita el mismo producto; ajuste la cantidad en una sola línea.',
            ]);
        }

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

        $impuesto = $subtotal * 0.13;
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
            $idPendiente = Estado::idPorNombre(Estado::PENDIENTE);
            $idPagado = Estado::idPorNombre(Estado::PAGADO);

            $numeroCompra = 'COM-' . now()->format('YmdHis');

            Compra::create([
                'numero_compra' => $numeroCompra,
                'proveedor_id' => $request->proveedor_id,
                'usuario_id' => Auth::id(),
                // Contado: la compra queda pagada de inmediato.
                // Crédito: queda pendiente hasta liquidar la cuenta por pagar.
                'estado_id' => $esCredito ? $idPendiente : $idPagado,
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
                    'estado_id' => $idPendiente,
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
                ]);
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
                ]);
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

        // Las ventas de contado exigen la cuenta bancaria que recibe el pago.
        if (! $esCredito) {
            $request->validate([
                'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            ], [
                'cuenta_bancaria_id.required' => 'Seleccione la cuenta bancaria en la que se recibirá el pago de la venta de contado.',
                'cuenta_bancaria_id.exists' => 'La cuenta bancaria seleccionada no es válida.',
            ]);
        }

        $subtotal = 0;
        foreach ($lineas as $linea) {
            $subtotal += $linea['precio_unitario'] * $linea['cantidad'];
        }

        $impuesto = $subtotal * 0.13;
        $descuento = $request->descuento ?? 0;
        $total = round(($subtotal + $impuesto) - $descuento, 2);

        if ($total < 0) {
            throw ValidationException::withMessages([
                'descuento' => 'El descuento no puede ser mayor al total de la venta.',
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
        foreach ($lineas as $linea) {
            $producto = Producto::findOrFail($linea['producto_id']);
            if ($producto->stock < $linea['cantidad']) {
                throw ValidationException::withMessages([
                    'productos' => "No hay suficiente stock de {$producto->nombre} (disponible: {$producto->stock}).",
                ]);
            }
        }

        $factura = DB::transaction(function () use ($request, $lineas, $subtotal, $impuesto, $descuento, $total, $esCredito, $cuotas) {
            $idPendiente = Estado::idPorNombre(Estado::PENDIENTE);
            $idPagado = Estado::idPorNombre(Estado::PAGADO);

            $numeroFactura = 'FAC-' . now()->format('YmdHis');

            $factura = Factura::create([
                'numero_factura' => $numeroFactura,
                'cliente_id' => $request->cliente_id,
                'usuario_id' => Auth::id(),
                'metodo_pago_id' => $request->metodo_pago_id,
                'estado_id' => $esCredito ? $idPendiente : $idPagado,
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
                    'estado_id' => $idPendiente,
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

            // Registro contable automático de la venta.
            $codigoBancos = '1.1.2';    // Bancos
            $codigoPorCobrar = '1.1.3'; // Cuentas por Cobrar
            $codigoVentas = '4.1';      // Ventas

            if ($esCredito) {
                // Crédito: no afecta el banco. Debe Cuentas por Cobrar / Haber Ventas.
                AsientoContableService::generar(now(), "Venta a crédito {$numeroFactura}", [
                    ['codigo_cuenta' => $codigoPorCobrar, 'debe' => $total, 'haber' => 0, 'descripcion' => "Venta a crédito {$numeroFactura}"],
                    ['codigo_cuenta' => $codigoVentas, 'debe' => 0, 'haber' => $total, 'descripcion' => "Ingreso por ventas {$numeroFactura}"],
                ]);
            } else {
                // Contado: el dinero entra al banco. Debe Bancos / Haber Ventas.
                $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($request->cuenta_bancaria_id);

                BancoService::acreditar(
                    $cuentaBancaria,
                    $total,
                    "Venta de contado {$numeroFactura}",
                    $numeroFactura
                );

                AsientoContableService::generar(now(), "Venta de contado {$numeroFactura}", [
                    ['codigo_cuenta' => $codigoBancos, 'debe' => $total, 'haber' => 0, 'descripcion' => "Cobro en {$cuentaBancaria->banco_nombre}"],
                    ['codigo_cuenta' => $codigoVentas, 'debe' => 0, 'haber' => $total, 'descripcion' => "Ingreso por ventas {$numeroFactura}"],
                ]);
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
            'productos.*.precio_unitario' => 'required|numeric|min:0',
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

        DB::transaction(function () use ($request, $compra, $lineas) {
            $referenciaAjuste = 'AJU-' . now()->format('YmdHis');

            // Revertir la tesorería y el asiento del registro anterior antes de
            // volver a aplicarlos con los nuevos importes.
            $this->revertirBancoYAsiento($compra->numero_compra);

            // Revertir el stock de los productos actuales y eliminarlos.
            foreach ($compra->detalles()->get() as $detalleAnterior) {
                $productoAnterior = Producto::find($detalleAnterior->producto_id);

                if ($productoAnterior) {
                    $productoAnterior->stock = max(0, $productoAnterior->stock - $detalleAnterior->cantidad);
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

            $impuesto = $subtotal * 0.13;
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

            // Regenerar la tesorería y el asiento con el nuevo total, según la
            // condición de pago original de la compra.
            $esCredito = $compra->tipo_compra === 'credito';
            $codigoInventario = '1.1.4.1';
            $codigoBancos = '1.1.2';
            $codigoPorPagar = '2.1.1';

            if ($esCredito) {
                // Mantener sincronizada la cuenta por pagar con el nuevo total.
                CuentaPagar::where('numero_compra', $compra->numero_compra)
                    ->where('proveedor_id', $compra->proveedor_id)
                    ->update([
                        'monto_original' => $total,
                        'saldo_pendiente' => $total,
                    ]);

                // Rehacer los plazos con el nuevo total en una sola cuota.
                $fechaVencimiento = PlazoCompra::where('numero_compra', $compra->numero_compra)
                    ->where('proveedor_id', $compra->proveedor_id)
                    ->min('fecha_vencimiento') ?? now()->addDays(30);

                PlazoCompra::where('numero_compra', $compra->numero_compra)
                    ->where('proveedor_id', $compra->proveedor_id)
                    ->delete();

                PlazoCompra::create([
                    'numero_compra' => $compra->numero_compra,
                    'proveedor_id' => $compra->proveedor_id,
                    'numero_cuota' => 1,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'monto' => $total,
                    'saldo_pendiente' => $total,
                ]);

                AsientoContableService::generar(now(), "Compra a crédito {$compra->numero_compra}", [
                    ['codigo_cuenta' => $codigoInventario, 'debe' => $total, 'haber' => 0, 'descripcion' => "Compra a crédito {$compra->numero_compra}"],
                    ['codigo_cuenta' => $codigoPorPagar, 'debe' => 0, 'haber' => $total, 'descripcion' => "Cuenta por pagar {$compra->numero_compra}"],
                ]);
            } elseif ($compra->cuenta_bancaria_id) {
                $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($compra->cuenta_bancaria_id);

                BancoService::debitar(
                    $cuentaBancaria,
                    $total,
                    "Compra de contado {$compra->numero_compra}",
                    $compra->numero_compra
                );

                AsientoContableService::generar(now(), "Compra de contado {$compra->numero_compra}", [
                    ['codigo_cuenta' => $codigoInventario, 'debe' => $total, 'haber' => 0, 'descripcion' => "Compra de contado {$compra->numero_compra}"],
                    ['codigo_cuenta' => $codigoBancos, 'debe' => 0, 'haber' => $total, 'descripcion' => "Pago desde {$cuentaBancaria->banco_nombre}"],
                ]);
            }
        });

        return redirect()
            ->route('compras.index')
            ->with('success', 'Compra actualizada correctamente.');
    }

    public function pagar(Compra $compra)
    {
        DB::transaction(function () use ($compra) {
            $idPagado = Estado::idPorNombre(Estado::PAGADO);

            $compra->update([
                'estado_id' => $idPagado,
            ]);

            // Liquidar la cuenta por pagar y sus plazos asociados para
            // mantener sincronizada la información financiera.
            CuentaPagar::where('numero_compra', $compra->numero_compra)
                ->where('proveedor_id', $compra->proveedor_id)
                ->update([
                    'saldo_pendiente' => 0,
                    'estado_id' => $idPagado,
                ]);

            PlazoCompra::where('numero_compra', $compra->numero_compra)
                ->where('proveedor_id', $compra->proveedor_id)
                ->update(['saldo_pendiente' => 0]);
        });

        return redirect()
            ->route('compras.index')
            ->with('success', 'Compra marcada como pagada. Se actualizó la cuenta por pagar.');
    }

    public function destroy(Compra $compra)
    {
        DB::transaction(function () use ($compra) {
            // Reversión del inventario: la compra sumó stock, al eliminar se resta.
            foreach ($compra->detalles()->get() as $detalle) {
                $producto = Producto::find($detalle->producto_id);

                if ($producto) {
                    $producto->stock = max(0, $producto->stock - $detalle->cantidad);
                    $producto->save();

                    InventarioService::registrarMovimiento(
                        $producto->id,
                        'Ajuste negativo',
                        $detalle->cantidad,
                        "Reversión por eliminación de compra {$compra->numero_compra}",
                        $compra->numero_compra
                    );
                }
            }

            // Reintegrar el banco (compras de contado) y eliminar el asiento.
            $this->revertirBancoYAsiento($compra->numero_compra);

            // Eliminar la cuenta por pagar y sus plazos (compras a crédito).
            CuentaPagar::where('numero_compra', $compra->numero_compra)
                ->where('proveedor_id', $compra->proveedor_id)
                ->delete();

            PlazoCompra::where('numero_compra', $compra->numero_compra)
                ->where('proveedor_id', $compra->proveedor_id)
                ->delete();

            $compra->delete();
        });

        return redirect()
            ->route('compras.index')
            ->with('success', 'Compra eliminada correctamente. Se revirtió el inventario, la tesorería y los asientos.');
    }

    /**
     * Revierte los movimientos bancarios y elimina los asientos contables
     * asociados a un documento (por su referencia / número).
     */
    private function revertirBancoYAsiento(string $referencia): void
    {
        $movimientos = MovimientoBancario::where('referencia', $referencia)->get();

        foreach ($movimientos as $movimiento) {
            BancoService::revertir($movimiento);
            $movimiento->delete();
        }

        AsientoContableService::eliminarPorDescripcion($referencia);
    }
}