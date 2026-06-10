<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $table = 'compras';

    protected $primaryKey = 'numero_compra';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'numero_compra',
        'proveedor_id',
        'usuario_id',
        'estado_id',
        'fecha',
        'subtotal',
        'impuesto',
        'total',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class, 'estado_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class, 'numero_compra', 'numero_compra');
    }
}