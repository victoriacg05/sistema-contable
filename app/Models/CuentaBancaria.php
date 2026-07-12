<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuentaBancaria extends Model
{
    protected $table = 'cuentas_bancarias';

    protected $fillable = [
        'codigo_cuenta',
        'banco_nombre',
        'numero_cuenta',
        'moneda',
        'saldo',
        'estado',
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
        'estado' => 'boolean',
    ];

    public function movimientos()
    {
        return $this->hasMany(MovimientoBancario::class, 'cuenta_bancaria_id')
            ->orderByDesc('fecha');
    }
}
