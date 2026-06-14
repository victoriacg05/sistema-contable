<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presupuesto extends Model
{
    protected $table = 'presupuesto';

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'anio',
        'mes',
        'categoria_gasto_id',
        'monto_presupuestado',
        'descripcion',
    ];

    public function categoria()
    {
        return $this->belongsTo(
            CategoriaGasto::class,
            'categoria_gasto_id'
        );
    }
}