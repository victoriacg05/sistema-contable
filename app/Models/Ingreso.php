<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingreso extends Model
{
    protected $table = 'ingresos';

    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'referencia_ingreso',
        'usuario_id',
        'metodo_pago_id',
        'origen',
        'descripcion',
        'monto',
        'fecha',
    ];

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}