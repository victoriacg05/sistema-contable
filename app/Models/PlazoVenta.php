<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlazoVenta extends Model
{
    protected $table = 'plazos_venta';

    protected $fillable = [
        'numero_factura',
        'cliente_id',
        'numero_cuota',
        'fecha_vencimiento',
        'monto',
        'saldo_pendiente',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'monto' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'numero_factura', 'numero_factura');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
