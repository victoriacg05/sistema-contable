<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoCuentaCobrar extends Model
{
    protected $table = 'pagos_clientes';

    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'numero_factura',
        'cliente_id',
        'referencia_pago',
        'usuario_id',
        'fecha_pago',
        'monto',
        'metodo_pago_id',
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