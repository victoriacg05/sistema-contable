<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'categoria_producto_id',
        'codigo_barras',
        'nombre',
        'descripcion',
        'stock',
        'stock_minimo',
        'precio',
        'estado',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_producto_id');
    }
}