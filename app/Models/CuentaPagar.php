<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuentaPagar extends Model
{
    protected $table = 'cuentas_pagar';

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'numero_compra',
        'proveedor_id',
        'monto_original',
        'saldo_pendiente',
        'fecha_emision',
        'fecha_vencimiento',
        'estado_id',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function compra()
    {
        return $this->belongsTo(
            Compra::class,
            'numero_compra',
            'numero_compra'
        );
    }
}