<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\DetalleFactura;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\MetodoPago;
use App\Models\Estado;
use App\Models\CuentaCobrar;
use App\Services\BitacoraService;
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

        return view('facturas.create', compact(
            'clientes',
            'productos',
            'metodosPago',
            'tiposComprobante'
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
        ]);

        $factura = DB::transaction(function () use ($request) {
            $producto = Producto::findOrFail($request->producto_id);

            if ($producto->stock < $request->cantidad) {
                abort(422, 'No hay suficiente stock disponible para este producto.');
            }

            $subtotal = $producto->precio * $request->cantidad;
            $impuesto = $subtotal * 0.13;
            $descuento = $request->descuento ?? 0;
            $total = ($subtotal + $impuesto) - $descuento;

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
                    'estado_id' => $estadoPendiente?->id ?? 1,
                ]);
            }

            $producto->stock -= $request->cantidad;
            $producto->save();

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

    public function edit(Factura $factura)
    {
        $clientes = Cliente::orderBy('nombre')->get();

        $productos = Producto::where('estado', 1)
            ->orderBy('nombre')
            ->get();

        $metodosPago = MetodoPago::orderBy('nombre')->get();

        $detalle = $factura->detalles()->first();

        return view('facturas.edit', compact(
            'factura',
            'clientes',
            'productos',
            'metodosPago',
            'detalle'
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
        ]);

        DB::transaction(function () use ($request, $factura) {
            $detalleAnterior = $factura->detalles()->first();

            if ($detalleAnterior) {
                $productoAnterior = Producto::find($detalleAnterior->producto_id);

                if ($productoAnterior) {
                    $productoAnterior->stock += $detalleAnterior->cantidad;
                    $productoAnterior->save();
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

            $estadoPendiente = Estado::where('nombre', 'pendiente')->first();
            $estadoPagado = Estado::where('nombre', 'pagado')->first();

            $esCredito = (int) $request->tipo_comprobante_id === 3;

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
                    'estado_id' => $estadoPendiente?->id ?? 1,
                ]);
            }

            $producto->stock -= $request->cantidad;
            $producto->save();
        });

        return redirect()
            ->route('facturas.index')
            ->with('success', 'Factura actualizada correctamente.');
    }

    public function destroy(Factura $factura)
    {
        $factura->delete();

        return redirect()
            ->route('facturas.index')
            ->with('success', 'Factura eliminada correctamente.');
    }

    public function pagar(Factura $factura)
    {
        $estadoPagado = Estado::where('nombre', 'pagado')->first();

        $factura->update([
            'estado_id' => $estadoPagado?->id ?? $factura->estado_id,
        ]);

        CuentaCobrar::where('numero_factura', $factura->numero_factura)
            ->where('cliente_id', $factura->cliente_id)
            ->update([
                'saldo_pendiente' => 0,
                'estado_id' => $estadoPagado?->id ?? 2,
            ]);

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

            BitacoraService::registrar('anular', 'facturas', "Factura {$factura->numero_factura} anulada. Motivo: {$request->motivo}");
        });

        return redirect()
            ->route('facturas.index')
            ->with('success', 'Factura anulada correctamente.');
    }
}