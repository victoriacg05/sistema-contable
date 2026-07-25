<?php

use App\Http\Controllers\CategoriaGastoController;
use App\Http\Controllers\IngresoController;
use App\Models\MetodoPago;
use Illuminate\Support\Facades\DB;

it('shows the most recently created catalog records first', function () {
    DB::table('categorias_gastos')->insert([
        [
            'nombre' => 'Categoría anterior',
            'descripcion' => 'Registro anterior',
            'created_at' => '2026-01-01 08:00:00',
            'updated_at' => '2026-01-01 08:00:00',
        ],
        [
            'nombre' => 'Categoría reciente',
            'descripcion' => 'Registro reciente',
            'created_at' => '2026-01-02 08:00:00',
            'updated_at' => '2026-01-02 08:00:00',
        ],
    ]);

    $categorias = app(CategoriaGastoController::class)
        ->index()
        ->getData()['categorias'];

    expect($categorias->pluck('nombre')->all())->toBe([
        'Categoría reciente',
        'Categoría anterior',
    ]);
});

it('prioritizes creation time over the document date', function () {
    $rolId = DB::table('roles')->insertGetId([
        'nombre' => 'Administrador',
        'descripcion' => 'Rol de prueba',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $usuarioId = DB::table('users')->insertGetId([
        'rol_id' => $rolId,
        'name' => 'Usuario de prueba',
        'email' => 'orden@example.com',
        'password' => 'password',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $metodoPago = MetodoPago::create([
        'nombre' => 'Transferencia',
        'descripcion' => 'Transferencia bancaria',
    ]);

    DB::table('ingresos')->insert([
        [
            'referencia_ingreso' => 'ING-ANTERIOR',
            'usuario_id' => $usuarioId,
            'metodo_pago_id' => $metodoPago->id,
            'origen' => 'Ingreso anterior',
            'descripcion' => '',
            'monto' => 100,
            'fecha' => '2026-07-25',
            'created_at' => '2026-07-25 08:00:00',
            'updated_at' => '2026-07-25 08:00:00',
        ],
        [
            'referencia_ingreso' => 'ING-RECIENTE',
            'usuario_id' => $usuarioId,
            'metodo_pago_id' => $metodoPago->id,
            'origen' => 'Ingreso reciente',
            'descripcion' => '',
            'monto' => 200,
            'fecha' => '2026-01-01',
            'created_at' => '2026-07-25 09:00:00',
            'updated_at' => '2026-07-25 09:00:00',
        ],
    ]);

    $ingresos = app(IngresoController::class)
        ->index()
        ->getData()['ingresos'];

    expect($ingresos->pluck('referencia_ingreso')->all())->toBe([
        'ING-RECIENTE',
        'ING-ANTERIOR',
    ]);
});
