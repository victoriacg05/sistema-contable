<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\UserController;

use App\Http\Controllers\CategoriaProductoController;
use App\Http\Controllers\CategoriaGastoController;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\EstadoController;
use App\Http\Controllers\CuentaCobrarController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::resource('clientes', ClienteController::class);

    Route::resource('proveedores', ProveedorController::class)
        ->parameters(['proveedores' => 'proveedor']);

    Route::resource('productos', ProductoController::class);

    Route::resource('facturas', FacturaController::class);
    Route::put('/facturas/{factura}/pagar', [FacturaController::class, 'pagar'])
        ->name('facturas.pagar');

    Route::resource('compras', CompraController::class);
    Route::put('/compras/{compra}/pagar', [CompraController::class, 'pagar'])
        ->name('compras.pagar');

    Route::resource('usuarios', UserController::class);

    Route::resource('categorias-productos', CategoriaProductoController::class);
    Route::resource('categorias-gastos', CategoriaGastoController::class);
    Route::resource('metodos-pago', MetodoPagoController::class);
    Route::resource('estados', EstadoController::class);

    Route::resource('cuentas-cobrar', CuentaCobrarController::class);

    Route::resource('cuentas-cobrar', CuentaCobrarController::class)
    ->only(['index']);

    Route::get('/cuentas-cobrar/{numero_factura}/{cliente_id}/pago', [CuentaCobrarController::class, 'createPago'])
    ->name('cuentas-cobrar.pago.create');

    Route::post('/cuentas-cobrar/{numero_factura}/{cliente_id}/pago', [CuentaCobrarController::class, 'storePago'])
    ->name('cuentas-cobrar.pago.store');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

require __DIR__ . '/auth.php';