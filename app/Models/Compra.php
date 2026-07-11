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
        'metodo_pago_id',
        'tipo_compra',
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

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class, 'numero_compra', 'numero_compra');
    }

    public function plazos()
    {
        return $this->hasMany(PlazoCompra::class, 'numero_compra', 'numero_compra')
            ->orderBy('numero_cuota');
    }
}