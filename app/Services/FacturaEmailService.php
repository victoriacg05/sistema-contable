<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Factura;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FacturaEmailService
{
    public static function enviarAlTerminar(Cliente $cliente, Factura $factura): void
    {
        $destinatario = $cliente->email;
        $nombreCliente = $cliente->nombre;
        $numeroFactura = $factura->numero_factura;
        $subtotal = (float) $factura->subtotal;
        $impuesto = (float) $factura->impuesto;
        $descuento = (float) $factura->descuento;
        $total = (float) $factura->total;

        app()->terminating(function () use (
            $destinatario,
            $nombreCliente,
            $numeroFactura,
            $subtotal,
            $impuesto,
            $descuento,
            $total
        ) {
            try {
                Mail::raw(
                    "Estimado/a {$nombreCliente},\n\n" .
                    "Adjuntamos la información de su factura electrónica.\n\n" .
                    "Número de factura: {$numeroFactura}\n" .
                    "Subtotal: ₡" . number_format($subtotal, 2) . "\n" .
                    "Impuesto: ₡" . number_format($impuesto, 2) . "\n" .
                    "Descuento: ₡" . number_format($descuento, 2) . "\n" .
                    "Total: ₡" . number_format($total, 2) . "\n\n" .
                    "Gracias por su compra.\n\n" .
                    "Distribuidora Ipacaraí",
                    function ($message) use ($destinatario, $numeroFactura) {
                        $message->to($destinatario)
                            ->subject('Factura Electrónica ' . $numeroFactura);
                    }
                );
            } catch (\Throwable $e) {
                Log::error('No se pudo enviar la factura electrónica.', [
                    'factura' => $numeroFactura,
                    'destinatario' => $destinatario,
                    'exception' => $e,
                ]);
            }
        });
    }
}
