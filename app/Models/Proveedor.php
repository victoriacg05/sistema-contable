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
}