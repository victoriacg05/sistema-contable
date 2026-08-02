<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'identificacion',
        'nombre',
        'empresa',
        'telefono',
        'correo',
        'estado',
    ];

    public function productos()
    {
        return $this->belongsToMany(
            Producto::class,
            'proveedor_producto',
            'proveedor_id',
            'producto_id'
        )->withTimestamps();
    }
}