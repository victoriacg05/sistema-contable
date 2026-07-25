<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    public const IMPUESTO = 0.13;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_producto_id',
        'codigo_barras',
        'nombre',
        'descripcion',
        'stock',
        'stock_minimo',
        'precio',
        'porcentaje_ganancia',
        'estado',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'porcentaje_ganancia' => 'decimal:2',
    ];

    public function getPrecioVentaSinImpuestoAttribute(): float
    {
        return round(
            (float) $this->precio * (1 + ((float) $this->porcentaje_ganancia / 100)),
            2
        );
    }

    public function getPrecioVentaConImpuestoAttribute(): float
    {
        return round($this->precio_venta_sin_impuesto * (1 + self::IMPUESTO), 2);
    }

    public function getPrecioCompraConImpuestoAttribute(): float
    {
        return round((float) $this->precio * (1 + self::IMPUESTO), 2);
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_producto_id');
    }
}