<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\DetalleFactura;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\MetodoPago;
use App\Models\Estado;
use App\Models\CuentaCobrar;
use App\Models\CuentaBancaria;
use App\Models\MovimientoBancario;
use App\Services\BitacoraService;
use App\Services\InventarioService;
use App\Services\BancoService;
use App\Services\AsientoContableService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class FacturaController extends Controller
{
    public function index()
    {
        $facturas = Factura::with(['cliente', 'estado', 'metodoPago', 'detalles.producto'])
            ->orderByDesc('fecha')
            ->orderByDesc('created_at')
            ->get();

        return view('facturas.index', compact('facturas'));
    }

    public function create()
    {
        $clientes = Cliente::orderBy('nombre')->get();

        $productos = Producto::where('estado', 1)
            ->orderBy('nombre')
            ->get();

        $metodosPago = MetodoPago::orderBy('nombre')->get();

        $tiposComprobante = DB::table('tipos_comprobante')
            ->orderBy('nombre')
            ->get();

        $cuentasBancarias = CuentaBancaria::where('estado', true)
            ->orderBy('banco_nombre')
            ->get();

        return view('facturas.create', compact(
            'clientes',
            'productos',
            'metodosPago',
            'tiposComprobante',
            'cuentasBancarias'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'producto_id' => 'required|exists:productos,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'cantidad' => 'required|integer|min:1',
            'descuento' => 'nullable|numeric|min:0',
            'tipo_comprobante_id' => 'required|exists:tipos_comprobante,id',
            'tipo_compra' => 'required|in:contado,credito',
        ]);

        // Las ventas de contado exigen la cuenta bancaria que recibe el pago.
        if ($request->tipo_compra !== 'credito') {
            $request->validate([
                'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            ], [
                'cuenta_bancaria_id.required' => 'Seleccione la cuenta bancaria en la que se recibirá el pago de la venta de contado.',
                'cuenta_bancaria_id.exists' => 'La cuenta bancaria seleccionada no es válida.',
            ]);
        }

        $factura = DB::transaction(function () use ($request) {
            $producto = Producto::findOrFail($request->producto_id);

            if ($producto->stock < $request->cantidad) {
                abort(422, 'No hay suficiente stock disponible para este producto.');
            }

            $subtotal = $producto->precio * $request->cantidad;
            $impuesto = $subtotal * 0.13;
            $descuento = $request->descuento ?? 0;
            $total = ($subtotal + $impuesto) - $descuento;

            $idPendiente = Estado::idPorNombre(Estado::PENDIENTE);
            $idPagado = Estado::idPorNombre(Estado::PAGADO);

            $numeroFactura = 'FAC-' . now()->format('YmdHis');

            // La condición de pago determina si la venta queda pagada
            // (contado) o pendiente con cuenta por cobrar (crédito).
            $esCredito = $request->tipo_compra === 'credito';

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

            DetalleFactura::create([
                'numero_factura' => $numeroFactura,
                'cliente_id' => $request->cliente_id,
                'producto_id' => $producto->id,
                'cantidad' => $request->cantidad,
                'precio_unitario' => $producto->precio,
                'subtotal' => $subtotal,
            ]);

            if ($esCredito) {
                CuentaCobrar::create([
                    'numero_factura' => $numeroFactura,
                    'cliente_id' => $request->cliente_id,
                    'monto_original' => $total,
                    'saldo_pendiente' => $total,
                    'fecha_emision' => now(),
                    'fecha_vencimiento' => now()->addDays(30),
                    'estado_id' => $idPendiente,
                ]);
            }

            $producto->stock -= $request->cantidad;
            $producto->save();

            // Salida automática del inventario por la venta facturada.
            InventarioService::registrarMovimiento(
                $producto->id,
                'Salida',
                $request->cantidad,
                "Venta a cliente {$numeroFactura}",
                $numeroFactura
            );

            // Registro contable automático de la venta.
            if ($esCredito) {
                // Crédito: Debe Cuentas por Cobrar (1.1.3) / Haber Ventas (4.1).
                AsientoContableService::generar(now(), "Venta a crédito {$numeroFactura}", [
                    ['codigo_cuenta' => '1.1.3', 'debe' => $total, 'haber' => 0, 'descripcion' => "Venta a crédito {$numeroFactura}"],
                    ['codigo_cuenta' => '4.1', 'debe' => 0, 'haber' => $total, 'descripcion' => "Ingreso por ventas {$numeroFactura}"],
                ]);
            } else {
                // Contado: el dinero entra al banco. Debe Bancos (1.1.2) / Haber Ventas (4.1).
                $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($request->cuenta_bancaria_id);

                BancoService::acreditar(
                    $cuentaBancaria,
                    $total,
                    "Venta de contado {$numeroFactura}",
                    $numeroFactura
                );

                AsientoContableService::generar(now(), "Venta de contado {$numeroFactura}", [
                    ['codigo_cuenta' => '1.1.2', 'debe' => $total, 'haber' => 0, 'descripcion' => "Cobro en {$cuentaBancaria->banco_nombre}"],
                    ['codigo_cuenta' => '4.1', 'debe' => 0, 'haber' => $total, 'descripcion' => "Ingreso por ventas {$numeroFactura}"],
                ]);
            }

            return $factura;
        });

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

        BitacoraService::registrar('crear', 'facturas', "Factura {$factura->numero_factura} creada por ₡" . number_format($factura->total, 2));

        return redirect()
            ->route('facturas.index')
            ->with('success', 'Factura creada correctamente.');
    }

    public function show(Factura $factura)
    {
        $factura->load(['cliente', 'metodoPago', 'estado', 'detalles.producto']);

        $tipoComprobante = DB::table('tipos_comprobante')
            ->where('id', $factura->tipo_comprobante_id)
            ->value('nombre');

        $usuario = DB::table('users')
            ->where('id', $factura->usuario_id)
            ->value('name');

        return view('facturas.show', compact('factura', 'tipoComprobante', 'usuario'));
    }

    public function pdf(Factura $factura)
    {
        $factura->load(['cliente', 'metodoPago', 'estado', 'detalles.producto']);

        $tipoComprobante = DB::table('tipos_comprobante')
            ->where('id', $factura->tipo_comprobante_id)
            ->value('nombre');

        $usuario = DB::table('users')
            ->where('id', $factura->usuario_id)
            ->value('name');

        $pdf = Pdf::loadView('facturas.pdf', compact('factura', 'tipoComprobante', 'usuario'))
            ->setPaper('letter', 'portrait');

        return $pdf->download('factura-' . $factura->numero_factura . '.pdf');
    }

    public function edit(Factura $factura)
    {
        $clientes = Cliente::orderBy('nombre')->get();

        $productos = Producto::where('estado', 1)
            ->orderBy('nombre')
            ->get();

        $metodosPago = MetodoPago::orderBy('nombre')->get();

        $detalle = $factura->detalles()->first();

        $cuentasBancarias = CuentaBancaria::where('estado', true)
            ->orderBy('banco_nombre')
            ->get();

        return view('facturas.edit', compact(
            'factura',
            'clientes',
            'productos',
            'metodosPago',
            'detalle',
            'cuentasBancarias'
        ));
    }

    public function update(Request $request, Factura $factura)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'producto_id' => 'required|exists:productos,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'cantidad' => 'required|integer|min:1',
            'descuento' => 'nullable|numeric|min:0',
            'tipo_compra' => 'required|in:contado,credito',
        ]);

        // Las ventas de contado exigen la cuenta bancaria que recibe el pago.
        if ($request->tipo_compra !== 'credito') {
            $request->validate([
                'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            ], [
                'cuenta_bancaria_id.required' => 'Seleccione la cuenta bancaria en la que se recibirá el pago de la venta de contado.',
                'cuenta_bancaria_id.exists' => 'La cuenta bancaria seleccionada no es válida.',
            ]);
        }

        DB::transaction(function () use ($request, $factura) {
            $referenciaAjuste = 'AJU-' . now()->format('YmdHis');

            // Revertir la tesorería y el asiento del registro anterior antes de
            // volver a aplicarlos con los nuevos importes.
            $this->revertirBancoYAsiento($factura->numero_factura);

            $detalleAnterior = $factura->detalles()->first();

            if ($detalleAnterior) {
                $productoAnterior = Producto::find($detalleAnterior->producto_id);

                if ($productoAnterior) {
                    $productoAnterior->stock += $detalleAnterior->cantidad;
                    $productoAnterior->save();

                    // Reingreso al inventario por reversión de la factura editada.
                    InventarioService::registrarMovimiento(
                        $productoAnterior->id,
                        'Ajuste positivo',
                        $detalleAnterior->cantidad,
                        "Reversión por edición de factura {$factura->numero_factura}",
                        $referenciaAjuste
                    );
                }

                $detalleAnterior->delete();
            }

            $producto = Producto::findOrFail($request->producto_id);

            if ($producto->stock < $request->cantidad) {
                abort(422, 'No hay suficiente stock disponible para este producto.');
            }

            $subtotal = $producto->precio * $request->cantidad;
            $impuesto = $subtotal * 0.13;
            $descuento = $request->descuento ?? 0;
            $total = ($subtotal + $impuesto) - $descuento;

            $idPendiente = Estado::idPorNombre(Estado::PENDIENTE);
            $idPagado = Estado::idPorNombre(Estado::PAGADO);

            $esCredito = $request->tipo_compra === 'credito';

            $factura->update([
                'cliente_id' => $request->cliente_id,
                'metodo_pago_id' => $request->metodo_pago_id,
                'estado_id' => $esCredito ? $idPendiente : $idPagado,
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'descuento' => $descuento,
                'total' => $total,
            ]);

            DetalleFactura::create([
                'numero_factura' => $factura->numero_factura,
                'cliente_id' => $request->cliente_id,
                'producto_id' => $producto->id,
                'cantidad' => $request->cantidad,
                'precio_unitario' => $producto->precio,
                'subtotal' => $subtotal,
            ]);

            CuentaCobrar::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $factura->cliente_id)
                ->delete();

            if ($esCredito) {
                CuentaCobrar::create([
                    'numero_factura' => $factura->numero_factura,
                    'cliente_id' => $request->cliente_id,
                    'monto_original' => $total,
                    'saldo_pendiente' => $total,
                    'fecha_emision' => now(),
                    'fecha_vencimiento' => now()->addDays(30),
                    'estado_id' => $idPendiente,
                ]);
            }

            $producto->stock -= $request->cantidad;
            $producto->save();

            // Salida al inventario por los productos de la factura editada.
            InventarioService::registrarMovimiento(
                $producto->id,
                'Ajuste negativo',
                $request->cantidad,
                "Actualización de factura {$factura->numero_factura}",
                $referenciaAjuste
            );

            // Regenerar el asiento (y el crédito bancario en contado) con el
            // nuevo total de la factura editada.
            if ($esCredito) {
                AsientoContableService::generar(now(), "Venta a crédito {$factura->numero_factura}", [
                    ['codigo_cuenta' => '1.1.3', 'debe' => $total, 'haber' => 0, 'descripcion' => "Venta a crédito {$factura->numero_factura}"],
                    ['codigo_cuenta' => '4.1', 'debe' => 0, 'haber' => $total, 'descripcion' => "Ingreso por ventas {$factura->numero_factura}"],
                ]);
            } else {
                $cuentaBancaria = CuentaBancaria::lockForUpdate()->findOrFail($request->cuenta_bancaria_id);

                BancoService::acreditar(
                    $cuentaBancaria,
                    $total,
                    "Venta de contado {$factura->numero_factura}",
                    $factura->numero_factura
                );

                AsientoContableService::generar(now(), "Venta de contado {$factura->numero_factura}", [
                    ['codigo_cuenta' => '1.1.2', 'debe' => $total, 'haber' => 0, 'descripcion' => "Cobro en {$cuentaBancaria->banco_nombre}"],
                    ['codigo_cuenta' => '4.1', 'debe' => 0, 'haber' => $total, 'descripcion' => "Ingreso por ventas {$factura->numero_factura}"],
                ]);
            }
        });

        return redirect()
            ->route('facturas.index')
            ->with('success', 'Factura actualizada correctamente.');
    }

    public function destroy(Factura $factura)
    {
        DB::transaction(function () use ($factura) {
            // Reintegrar el inventario de los productos facturados.
            foreach ($factura->detalles as $detalle) {
                $producto = Producto::find($detalle->producto_id);
                if ($producto) {
                    $producto->increment('stock', $detalle->cantidad);

                    InventarioService::registrarMovimiento(
                        $producto->id,
                        'Ajuste positivo',
                        $detalle->cantidad,
                        "Reversión por eliminación de factura {$factura->numero_factura}",
                        $factura->numero_factura
                    );
                }
            }

            // Eliminar la cuenta por cobrar asociada (ventas a crédito).
            CuentaCobrar::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $factura->cliente_id)
                ->delete();

            // Revertir la tesorería y el asiento contable de la venta.
            $this->revertirBancoYAsiento($factura->numero_factura);

            $factura->detalles()->delete();
            $factura->delete();
        });

        return redirect()
            ->route('facturas.index')
            ->with('success', 'Factura eliminada correctamente. Se revirtió el inventario, la tesorería y los asientos.');
    }

    /**
     * Revierte los movimientos bancarios y elimina los asientos contables
     * asociados a un documento (por su referencia / número), para que al
     * editar o eliminar no queden saldos ni asientos desalineados.
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

    public function pagar(Factura $factura)
    {
        DB::transaction(function () use ($factura) {
            $idPagado = Estado::idPorNombre(Estado::PAGADO);

            $factura->update([
                'estado_id' => $idPagado,
            ]);

            CuentaCobrar::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $factura->cliente_id)
                ->update([
                    'saldo_pendiente' => 0,
                    'estado_id' => $idPagado,
                ]);
        });

        return redirect()
            ->route('facturas.index')
            ->with('success', 'Factura marcada como pagada.');
    }

    public function anular(Request $request, Factura $factura)
    {
        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($request, $factura) {
            $idAnulado = Estado::idPorNombre(Estado::ANULADO);

            // Registrar anulación
            DB::table('anulaciones_facturas')->insert([
                'numero_factura' => $factura->numero_factura,
                'cliente_id' => $factura->cliente_id,
                'usuario_id' => Auth::id(),
                'estado_id' => $idAnulado,
                'motivo' => $request->motivo,
                'fecha_anulacion' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Actualizar estado de la factura
            $factura->update([
                'estado_id' => $idAnulado,
            ]);

            // Revertir stock
            foreach ($factura->detalles as $detalle) {
                $producto = Producto::find($detalle->producto_id);
                if ($producto) {
                    $producto->increment('stock', $detalle->cantidad);
                }
            }

            // Eliminar cuenta por cobrar asociada
            CuentaCobrar::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $factura->cliente_id)
                ->delete();

            // Revertir la tesorería y el asiento contable de la venta anulada.
            $this->revertirBancoYAsiento($factura->numero_factura);

            BitacoraService::registrar('anular', 'facturas', "Factura {$factura->numero_factura} anulada. Motivo: {$request->motivo}");
        });

        return redirect()
            ->route('facturas.index')
            ->with('success', 'Factura anulada correctamente.');
    }
}