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
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CompraController extends Controller
{
    public function index()
    {
        $compras = Compra::with(['proveedor', 'estado', 'detalles.producto', 'plazos'])
            ->orderByDesc('fecha')
            ->get();

        return view('compras.index', compact('compras'));
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

        return view('compras.create', compact(
            'proveedores',
            'productos',
            'clientes',
            'metodosPago',
            'tiposComprobante'
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
            'tipo_compra' => 'required|in:contado,credito',
        ]);

        $subtotal = 0;
        foreach ($lineas as $linea) {
            $subtotal += $linea['precio_unitario'] * $linea['cantidad'];
        }

        $impuesto = $subtotal * 0.13;
        $total = round($subtotal + $impuesto, 2);

        $esCredito = $request->tipo_compra === 'credito';
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

            $numeroCompra = 'COM-' . now()->format('YmdHis');

            Compra::create([
                'numero_compra' => $numeroCompra,
                'proveedor_id' => $request->proveedor_id,
                'usuario_id' => Auth::id(),
                'estado_id' => $estadoPendiente?->id ?? 1,
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
            }

            // Vencimiento de la cuenta por pagar:
            //  - Crédito: fecha de la primera cuota.
            //  - Contado: 30 días (comportamiento original).
            if ($esCredito) {
                $fechaVencimiento = collect($cuotas)
                    ->min('fecha_vencimiento');
            } else {
                $fechaVencimiento = now()->addDays(30);
            }

            CuentaPagar::create([
                'numero_compra' => $numeroCompra,
                'proveedor_id' => $request->proveedor_id,
                'monto_original' => $total,
                'saldo_pendiente' => $total,
                'fecha_emision' => now(),
                'fecha_vencimiento' => $fechaVencimiento,
                'estado_id' => $estadoPendiente?->id ?? 1,
            ]);

            if ($esCredito) {
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
        });

        return redirect()
            ->route('compras.index')
            ->with('success', 'Compra a proveedor registrada correctamente. El inventario fue actualizado.');
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
            'descuento' => 'nullable|numeric|min:0',
        ]);

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

        // Validar stock disponible antes de crear la factura.
        foreach ($lineas as $linea) {
            $producto = Producto::findOrFail($linea['producto_id']);
            if ($producto->stock < $linea['cantidad']) {
                throw ValidationException::withMessages([
                    'productos' => "No hay suficiente stock de {$producto->nombre} (disponible: {$producto->stock}).",
                ]);
            }
        }

        $factura = DB::transaction(function () use ($request, $lineas, $subtotal, $impuesto, $descuento, $total) {
            $estadoPendiente = Estado::where('nombre', 'pendiente')->first();
            $estadoPagado = Estado::where('nombre', 'pagado')->first();

            $numeroFactura = 'FAC-' . now()->format('YmdHis');

            $esCredito = (int) $request->tipo_comprobante_id === 3;

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
            }

            if ($esCredito) {
                CuentaCobrar::create([
                    'numero_factura' => $numeroFactura,
                    'cliente_id' => $request->cliente_id,
                    'monto_original' => $total,
                    'saldo_pendiente' => $total,
                    'fecha_emision' => now(),
                    'fecha_vencimiento' => now()->addDays(30),
                    'estado_id' => $estadoPendiente?->id ?? 1,
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

        return redirect()
            ->route('facturas.index')
            ->with('success', "Venta registrada. Se generó automáticamente la factura {$factura->numero_factura} y se actualizó el inventario.");
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
            // Revertir el stock de los productos actuales y eliminarlos.
            foreach ($compra->detalles()->get() as $detalleAnterior) {
                $productoAnterior = Producto::find($detalleAnterior->producto_id);

                if ($productoAnterior) {
                    $productoAnterior->stock -= $detalleAnterior->cantidad;
                    $productoAnterior->save();
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
            }
        });

        return redirect()
            ->route('compras.index')
            ->with('success', 'Compra actualizada correctamente.');
    }

    public function pagar(Compra $compra)
    {
        $estadoPagado = Estado::where('nombre', 'pagado')->first();

        $compra->update([
            'estado_id' => $estadoPagado?->id ?? $compra->estado_id,
        ]);

        return redirect()
            ->route('compras.index')
            ->with('success', 'Compra marcada como pagada.');
    }

    public function destroy(Compra $compra)
    {
        DB::transaction(function () use ($compra) {
            foreach ($compra->detalles()->get() as $detalle) {
                $producto = Producto::find($detalle->producto_id);

                if ($producto) {
                    $producto->stock -= $detalle->cantidad;
                    $producto->save();
                }
            }

            $compra->delete();
        });

        return redirect()
            ->route('compras.index')
            ->with('success', 'Compra eliminada correctamente.');
    }
}