<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gasto extends Model
{
    protected $table = 'gastos';

    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'numero_comprobante',
        'categoria_gasto_id',
        'tipo_gasto_id',
        'usuario_id',
        'metodo_pago_id',
        'descripcion',
        'monto',
        'fecha',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaGasto::class, 'categoria_gasto_id');
    }

    public function tipoGasto()
    {
        return $this->belongsTo(TipoGasto::class, 'tipo_gasto_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}