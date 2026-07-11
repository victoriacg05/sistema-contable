<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlazoCompra extends Model
{
    protected $table = 'plazos_compra';

    protected $fillable = [
        'numero_compra',
        'proveedor_id',
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

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'numero_compra', 'numero_compra');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}
