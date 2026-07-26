<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\DetalleFactura;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\MetodoPago;
use App\Models\Estado;
use App\Models\CuentaCobrar;
use App\Models\PlazoVenta;
use App\Models\CuentaBancaria;
use App\Models\MovimientoBancario;
use App\Services\BitacoraService;
use App\Services\InventarioService;
use App\Services\CodigoProductoService;
use App\Services\AsientoContableService;
use App\Services\BancoService;
use App\Services\IngresoAutomaticoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class FacturaController extends Controller
{
    public function __construct(CodigoProductoService $codigoProductoService)
    {
        $codigoProductoService->asegurarEstructuraPrecios();
        $codigoProductoService->convertirPreciosExistentes();
    }

    public function index()
    {
        $facturas = Factura::with(['cliente', 'estado', 'metodoPago', 'detalles.producto'])
            ->orderByDesc('created_at')
            ->orderByDesc('fecha')
            ->orderByDesc('numero_factura')
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

        if (
            $request->tipo_compra === 'contado'
            && AsientoContableService::requiereCuentaBancaria((int) $request->metodo_pago_id)
        ) {
            $request->validate([
                'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            ]);
        }

        $factura = DB::transaction(function () use ($request) {
            $producto = Producto::findOrFail($request->producto_id);

            if ($producto->stock < $request->cantidad) {
                abort(422, 'No hay suficiente stock disponible para este producto.');
            }

            $precioVenta = $producto->precio_venta_sin_impuesto;
            $subtotal = $precioVenta * $request->cantidad;
            $impuesto = $subtotal * Producto::IMPUESTO;
            $descuento = $request->descuento ?? 0;
            $total = ($subtotal + $impuesto) - $descuento;

            if ($descuento > $subtotal) {
                throw ValidationException::withMessages([
                    'descuento' => 'El descuento no puede ser mayor al subtotal de la venta.',
                ]);
            }

            $estadoPendiente = Estado::where('nombre', 'pendiente')->first();
            $estadoPagado = Estado::where('nombre', 'pagado')->first();

            $numeroFactura = 'FAC-' . now()->format('YmdHis') . '-' . strtoupper(str()->random(6));

            // La condición de pago determina si la venta queda pagada
            // (contado) o pendiente con cuenta por cobrar (crédito).
            $esCredito = $request->tipo_compra === 'credito';

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

            DetalleFactura::create([
                'numero_factura' => $numeroFactura,
                'cliente_id' => $request->cliente_id,
                'producto_id' => $producto->id,
                'cantidad' => $request->cantidad,
                'precio_unitario' => $precioVenta,
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
                    'estado_id' => $estadoPendiente?->id ?? 1,
                ]);

                PlazoVenta::create([
                    'numero_factura' => $numeroFactura,
                    'cliente_id' => $request->cliente_id,
                    'numero_cuota' => 1,
                    'fecha_vencimiento' => now()->addDays(30),
                    'monto' => $total,
                    'saldo_pendiente' => $total,
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

            AsientoContableService::registrarVenta(
                now(),
                $numeroFactura,
                (float) $subtotal,
                (float) $impuesto,
                (float) $descuento,
                round((float) $producto->precio * $request->cantidad, 2),
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
        if ($factura->detalles()->count() > 1) {
            throw ValidationException::withMessages([
                'factura' => 'Las facturas con varios productos no se pueden editar desde este formulario.',
            ]);
        }

        $clientes = Cliente::orderBy('nombre')->get();

        $productos = Producto::where('estado', 1)
            ->orderBy('nombre')
            ->get();

        $metodosPago = MetodoPago::orderBy('nombre')->get();
        $cuentasBancarias = CuentaBancaria::where('estado', true)
            ->orderBy('banco_nombre')
            ->get();

        $detalle = $factura->detalles()->first();

        return view('facturas.edit', compact(
            'factura',
            'clientes',
            'productos',
            'metodosPago',
            'cuentasBancarias',
            'detalle'
        ));
    }

    public function update(Request $request, Factura $factura)
    {
        if ($this->estaAnulada($factura)) {
            throw ValidationException::withMessages([
                'factura' => 'No se puede editar una factura anulada.',
            ]);
        }

        if ($factura->detalles()->count() > 1) {
            throw ValidationException::withMessages([
                'factura' => 'Las facturas con varios productos no se pueden editar desde este formulario.',
            ]);
        }

        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'producto_id' => 'required|exists:productos,id',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'cantidad' => 'required|integer|min:1',
            'descuento' => 'nullable|numeric|min:0',
            'tipo_compra' => 'required|in:contado,credito',
        ]);

        if (
            $request->tipo_compra === 'contado'
            && AsientoContableService::requiereCuentaBancaria((int) $request->metodo_pago_id)
        ) {
            $request->validate([
                'cuenta_bancaria_id' => 'required|exists:cuentas_bancarias,id',
            ]);
        }

        DB::transaction(function () use ($request, $factura) {
            $factura = Factura::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $factura->cliente_id)
                ->lockForUpdate()
                ->firstOrFail();
            $referenciaAjuste = 'AJU-' . now()->format('YmdHis');
            $clienteIdAnterior = $factura->cliente_id;
            $cuentaCobrarAnterior = CuentaCobrar::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $clienteIdAnterior)
                ->lockForUpdate()
                ->first();

            $tablaPagos = Schema::hasTable('pagos_cuentas_cobrar')
                ? 'pagos_cuentas_cobrar'
                : 'pagos_clientes';

            if (
                DB::table($tablaPagos)
                    ->where('numero_factura', $factura->numero_factura)
                    ->where('cliente_id', $clienteIdAnterior)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'producto_id' => 'No se puede editar una factura que ya tiene pagos registrados.',
                ]);
            }

            if ($cuentaCobrarAnterior && (float) $cuentaCobrarAnterior->saldo_pendiente <= 0) {
                throw ValidationException::withMessages([
                    'producto_id' => 'No se puede editar una factura que ya fue liquidada.',
                ]);
            }

            if (
                $cuentaCobrarAnterior
                && (int) $clienteIdAnterior !== (int) $request->cliente_id
            ) {
                throw ValidationException::withMessages([
                    'cliente_id' => 'No se puede cambiar el cliente de una factura a crédito.',
                ]);
            }

            $this->revertirCobroBancario($factura);

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

            $precioVenta = $detalleAnterior
                && (int) $detalleAnterior->producto_id === (int) $producto->id
                    ? (float) $detalleAnterior->precio_unitario
                    : $producto->precio_venta_sin_impuesto;
            $subtotal = $precioVenta * $request->cantidad;
            $impuesto = $subtotal * Producto::IMPUESTO;
            $descuento = $request->descuento ?? 0;
            $total = ($subtotal + $impuesto) - $descuento;

            if ($descuento > $subtotal) {
                throw ValidationException::withMessages([
                    'descuento' => 'El descuento no puede ser mayor al subtotal de la venta.',
                ]);
            }

            $estadoPendiente = Estado::where('nombre', 'pendiente')->first();
            $estadoPagado = Estado::where('nombre', 'pagado')->first();

            $esCredito = $request->tipo_compra === 'credito';

            $factura->update([
                'cliente_id' => $request->cliente_id,
                'metodo_pago_id' => $request->metodo_pago_id,
                'estado_id' => $esCredito
                    ? ($estadoPendiente?->id ?? 1)
                    : ($estadoPagado?->id ?? 2),
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
                'precio_unitario' => $precioVenta,
                'subtotal' => $subtotal,
            ]);

            CuentaCobrar::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $clienteIdAnterior)
                ->delete();
            PlazoVenta::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $clienteIdAnterior)
                ->delete();

            if ($esCredito) {
                $fechaVencimiento = now()->addDays(30);

                CuentaCobrar::create([
                    'numero_factura' => $factura->numero_factura,
                    'cliente_id' => $request->cliente_id,
                    'monto_original' => $total,
                    'saldo_pendiente' => $total,
                    'fecha_emision' => now(),
                    'fecha_vencimiento' => $fechaVencimiento,
                    'estado_id' => $estadoPendiente?->id ?? 1,
                ]);

                PlazoVenta::create([
                    'numero_factura' => $factura->numero_factura,
                    'cliente_id' => $request->cliente_id,
                    'numero_cuota' => 1,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'monto' => $total,
                    'saldo_pendiente' => $total,
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

            AsientoContableService::registrarVenta(
                $factura->fecha,
                $factura->numero_factura,
                (float) $subtotal,
                (float) $impuesto,
                (float) $descuento,
                round((float) $producto->precio * $request->cantidad, 2),
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
                    "Venta actualizada {$factura->numero_factura}",
                    $factura->numero_factura
                );
            }

            if ($esCredito) {
                IngresoAutomaticoService::eliminarVentaContado($factura->numero_factura);
            } else {
                IngresoAutomaticoService::registrarVentaContado(
                    $factura->numero_factura,
                    $factura->fecha,
                    (int) $factura->usuario_id,
                    (int) $request->metodo_pago_id,
                    (float) $total
                );
            }
        });

        return redirect()
            ->route('facturas.index')
            ->with('success', 'Factura actualizada correctamente.');
    }

    public function destroy(Request $request, Factura $factura)
    {
        if ($this->estaAnulada($factura)) {
            throw ValidationException::withMessages([
                'factura' => 'No se puede eliminar una factura anulada.',
            ]);
        }

        DB::transaction(function () use ($factura) {
            $factura = Factura::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $factura->cliente_id)
                ->lockForUpdate()
                ->firstOrFail();
            $cuentaCobrar = CuentaCobrar::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $factura->cliente_id)
                ->lockForUpdate()
                ->first();
            $tablaPagos = Schema::hasTable('pagos_cuentas_cobrar')
                ? 'pagos_cuentas_cobrar'
                : 'pagos_clientes';

            if (
                DB::table($tablaPagos)
                    ->where('numero_factura', $factura->numero_factura)
                    ->where('cliente_id', $factura->cliente_id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'factura' => 'No se puede eliminar una factura que ya tiene pagos registrados.',
                ]);
            }

            if ($cuentaCobrar && (float) $cuentaCobrar->saldo_pendiente <= 0) {
                throw ValidationException::withMessages([
                    'factura' => 'No se puede eliminar una factura que ya fue liquidada.',
                ]);
            }

            $this->revertirCobroBancario($factura);
            IngresoAutomaticoService::eliminarVentaContado($factura->numero_factura);

            AsientoContableService::revertir(
                now(),
                'VENTA:' . $factura->numero_factura,
                'REVERSO-VENTA:' . $factura->numero_factura,
                "Reversión de factura eliminada {$factura->numero_factura}"
            );

            foreach ($factura->detalles as $detalle) {
                Producto::whereKey($detalle->producto_id)
                    ->increment('stock', $detalle->cantidad);
            }

            DB::table('movimientos_inventario')
                ->where('referencia_movimiento', $factura->numero_factura)
                ->delete();

            Factura::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $factura->cliente_id)
                ->delete();
        });

        $rutaDestino = $request->input('origen') === 'compras-clientes'
            ? 'compras.clientes'
            : 'facturas.index';

        return redirect()
            ->route($rutaDestino)
            ->with('success', 'Factura eliminada correctamente.');
    }

    public function anular(Request $request, Factura $factura)
    {
        if ($this->estaAnulada($factura)) {
            throw ValidationException::withMessages([
                'motivo' => 'La factura ya se encuentra anulada.',
            ]);
        }

        $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($request, $factura) {
            $factura = Factura::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $factura->cliente_id)
                ->lockForUpdate()
                ->firstOrFail();
            $cuentaCobrar = CuentaCobrar::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $factura->cliente_id)
                ->lockForUpdate()
                ->first();
            $tablaPagos = Schema::hasTable('pagos_cuentas_cobrar')
                ? 'pagos_cuentas_cobrar'
                : 'pagos_clientes';

            if (
                DB::table($tablaPagos)
                    ->where('numero_factura', $factura->numero_factura)
                    ->where('cliente_id', $factura->cliente_id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'motivo' => 'No se puede anular una factura que ya tiene pagos registrados.',
                ]);
            }

            if ($cuentaCobrar && (float) $cuentaCobrar->saldo_pendiente <= 0) {
                throw ValidationException::withMessages([
                    'motivo' => 'No se puede anular una factura que ya fue liquidada.',
                ]);
            }

            $this->revertirCobroBancario($factura);
            IngresoAutomaticoService::eliminarVentaContado($factura->numero_factura);

            $estadoAnulado = Estado::where('nombre', 'Anulado')->first();

            // Registrar anulación
            DB::table('anulaciones_facturas')->insert([
                'numero_factura' => $factura->numero_factura,
                'cliente_id' => $factura->cliente_id,
                'usuario_id' => Auth::id(),
                'estado_id' => $estadoAnulado?->id ?? 5,
                'motivo' => $request->motivo,
                'fecha_anulacion' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Actualizar estado de la factura
            $factura->update([
                'estado_id' => $estadoAnulado?->id ?? 5,
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
            PlazoVenta::where('numero_factura', $factura->numero_factura)
                ->where('cliente_id', $factura->cliente_id)
                ->delete();

            AsientoContableService::revertir(
                now(),
                'VENTA:' . $factura->numero_factura,
                'ANULACION-VENTA:' . $factura->numero_factura,
                "Anulación de factura {$factura->numero_factura}"
            );

            BitacoraService::registrar('anular', 'facturas', "Factura {$factura->numero_factura} anulada. Motivo: {$request->motivo}");
        });

        return redirect()
            ->route('facturas.index')
            ->with('success', 'Factura anulada correctamente.');
    }

    private function estaAnulada(Factura $factura): bool
    {
        return strtolower((string) $factura->estado()->value('nombre')) === 'anulado';
    }

    private function revertirCobroBancario(Factura $factura): void
    {
        $movimiento = MovimientoBancario::where('referencia', $factura->numero_factura)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if (! $movimiento || $movimiento->tipo !== 'entrada') {
            return;
        }

        $cuentaBancaria = CuentaBancaria::lockForUpdate()
            ->findOrFail($movimiento->cuenta_bancaria_id);

        BancoService::debitar(
            $cuentaBancaria,
            (float) $movimiento->monto,
            "Reversión de cobro de {$factura->numero_factura}",
            $factura->numero_factura
        );
    }
}