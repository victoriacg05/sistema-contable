<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleCompra extends Model
{
    protected $table = 'detalle_compras';

    public $incrementing = false;

    protected $fillable = [
        'numero_compra',
        'proveedor_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function compra()
    {
        return $this->belongsTo(Compra::class, 'numero_compra', 'numero_compra');
    }
}