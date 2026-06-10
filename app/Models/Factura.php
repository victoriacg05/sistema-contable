<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    protected $table = 'facturas';

    protected $primaryKey = 'numero_factura';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'numero_factura',
        'cliente_id',
        'usuario_id',
        'metodo_pago_id',
        'estado_id',
        'tipo_comprobante_id',
        'fecha',
        'subtotal',
        'impuesto',
        'descuento',
        'total',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleFactura::class, 'numero_factura', 'numero_factura');
    }

}