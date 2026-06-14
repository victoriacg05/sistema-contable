<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoCuentaPagar extends Model
{
    protected $table = 'pagos_cuentas_pagar';

    protected $fillable = [
        'numero_compra',
        'proveedor_id',
        'fecha_pago',
        'monto_pagado',
        'metodo_pago_id',
        'observacion',
    ];

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}