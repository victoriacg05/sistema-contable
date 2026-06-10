<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleFactura extends Model
{
    protected $table = 'detalle_facturas';

    public $incrementing = false;

    protected $fillable = [
        'numero_factura',
        'cliente_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'numero_factura', 'numero_factura');
    }
}