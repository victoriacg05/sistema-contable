<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoCuentaCobrar extends Model
{
    protected $table = 'pagos_cuentas_cobrar';

    protected $fillable = [
        'numero_factura',
        'cliente_id',
        'fecha_pago',
        'monto_pagado',
        'metodo_pago_id',
        'observacion',
    ];

    public function metodoPago()
    {
        return $this->belongsTo(
            MetodoPago::class,
            'metodo_pago_id'
        );
    }

    public function cliente()
    {
        return $this->belongsTo(
            Cliente::class,
            'cliente_id'
        );
    }
}