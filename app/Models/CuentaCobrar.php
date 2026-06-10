<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuentaCobrar extends Model
{
    protected $table = 'cuentas_cobrar';

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'numero_factura',
        'cliente_id',
        'monto_original',
        'saldo_pendiente',
        'fecha_emision',
        'fecha_vencimiento',
        'estado_id',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function factura()
    {
        return $this->belongsTo(
            Factura::class,
            'numero_factura',
            'numero_factura'
        );
    }
}