<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Estado;
use App\Models\CuentaPagar;
use App\Models\PlazoCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        return view('compras.create', compact('proveedores', 'productos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
            'precio_unitario' => 'required|numeric|min:0',
            'tipo_compra' => 'required|in:contado,credito',
        ]);

        $subtotal = $request->precio_unitario * $request->cantidad;
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

        DB::transaction(function () use ($request, $subtotal, $impuesto, $total, $esCredito, $cuotas) {
            $producto = Producto::findOrFail($request->producto_id);

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

            DetalleCompra::create([
                'numero_compra' => $numeroCompra,
                'proveedor_id' => $request->proveedor_id,
                'producto_id' => $producto->id,
                'cantidad' => $request->cantidad,
                'precio_unitario' => $request->precio_unitario,
                'subtotal' => $subtotal,
            ]);

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

            $producto->stock += $request->cantidad;
            $producto->save();
        });

        return redirect()
            ->route('compras.index')
            ->with('success', 'Compra registrada correctamente.');
    }

    public function edit(Compra $compra)
    {
        $proveedores = Proveedor::orderBy('nombre')->get();

        $productos = Producto::orderBy('nombre')->get();

        $detalle = $compra->detalles()->first();

        return view('compras.edit', compact(
            'compra',
            'proveedores',
            'productos',
            'detalle'
        ));
    }

    public function update(Request $request, Compra $compra)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
            'precio_unitario' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $compra) {
            $detalleAnterior = $compra->detalles()->first();

            if ($detalleAnterior) {
                $productoAnterior = Producto::find($detalleAnterior->producto_id);

                if ($productoAnterior) {
                    $productoAnterior->stock -= $detalleAnterior->cantidad;
                    $productoAnterior->save();
                }

                $detalleAnterior->delete();
            }

            $producto = Producto::findOrFail($request->producto_id);

            $subtotal = $request->precio_unitario * $request->cantidad;
            $impuesto = $subtotal * 0.13;
            $total = $subtotal + $impuesto;

            $compra->update([
                'proveedor_id' => $request->proveedor_id,
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'total' => $total,
            ]);

            DetalleCompra::create([
                'numero_compra' => $compra->numero_compra,
                'proveedor_id' => $request->proveedor_id,
                'producto_id' => $producto->id,
                'cantidad' => $request->cantidad,
                'precio_unitario' => $request->precio_unitario,
                'subtotal' => $subtotal,
            ]);

            $producto->stock += $request->cantidad;
            $producto->save();
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
            $detalle = $compra->detalles()->first();

            if ($detalle) {
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