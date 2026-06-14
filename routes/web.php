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
use App\Http\Controllers\CuentaPagarController;
use App\Http\Controllers\IngresoController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ContabilidadController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\BitacoraController;

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
    Route::put('/facturas/{factura}/anular', [FacturaController::class, 'anular'])
        ->name('facturas.anular');

    Route::resource('compras', CompraController::class);
    Route::put('/compras/{compra}/pagar', [CompraController::class, 'pagar'])
        ->name('compras.pagar');

    Route::resource('usuarios', UserController::class);

    Route::resource('categorias-productos', CategoriaProductoController::class);
    Route::resource('categorias-gastos', CategoriaGastoController::class);
    Route::resource('metodos-pago', MetodoPagoController::class);
    Route::resource('estados', EstadoController::class);

    Route::resource('cuentas-cobrar', CuentaCobrarController::class)
    ->only(['index']);

    Route::get('/cuentas-cobrar/{numero_factura}/{cliente_id}/pago', [CuentaCobrarController::class, 'createPago'])
    ->name('cuentas-cobrar.pago.create');

    Route::post('/cuentas-cobrar/{numero_factura}/{cliente_id}/pago', [CuentaCobrarController::class, 'storePago'])
    ->name('cuentas-cobrar.pago.store');

    Route::resource('cuentas-pagar', CuentaPagarController::class)
    ->only(['index']);

    Route::get('/cuentas-pagar/{numero_compra}/{proveedor_id}/pago', [CuentaPagarController::class, 'createPago'])
        ->name('cuentas-pagar.pago.create');

    Route::post('/cuentas-pagar/{numero_compra}/{proveedor_id}/pago', [CuentaPagarController::class, 'storePago'])
        ->name('cuentas-pagar.pago.store');

    Route::get('/ingresos', [IngresoController::class, 'index'])->name('ingresos.index');
    Route::get('/ingresos/create', [IngresoController::class, 'create'])->name('ingresos.create');
    Route::post('/ingresos', [IngresoController::class, 'store'])->name('ingresos.store');

    Route::get('/ingresos/{referencia_ingreso}/{fecha}/{usuario_id}/edit', [IngresoController::class, 'edit'])
        ->name('ingresos.edit');

    Route::put('/ingresos/{referencia_ingreso}/{fecha}/{usuario_id}', [IngresoController::class, 'update'])
        ->name('ingresos.update');

    Route::delete('/ingresos/{referencia_ingreso}/{fecha}/{usuario_id}', [IngresoController::class, 'destroy'])
        ->name('ingresos.destroy');

    Route::get('/gastos', [GastoController::class, 'index'])->name('gastos.index');
    Route::get('/gastos/create', [GastoController::class, 'create'])->name('gastos.create');
    Route::post('/gastos', [GastoController::class, 'store'])->name('gastos.store');

    Route::get('/presupuesto', [PresupuestoController::class, 'index'])->name('presupuesto.index');
    Route::get('/presupuesto/create', [PresupuestoController::class, 'create'])->name('presupuesto.create');
    Route::post('/presupuesto', [PresupuestoController::class, 'store'])->name('presupuesto.store');

    Route::get('/reportes', [ReporteController::class, 'index'])
    ->name('reportes.index');

    Route::get('/reportes/pdf', [ReporteController::class, 'pdf'])
    ->name('reportes.pdf');

    Route::get('/presupuesto/{anio}/{mes}/{categoria_gasto_id}/edit', [PresupuestoController::class, 'edit'])
        ->name('presupuesto.edit');

    Route::put('/presupuesto/{anio}/{mes}/{categoria_gasto_id}', [PresupuestoController::class, 'update'])
        ->name('presupuesto.update');

    Route::delete('/presupuesto/{anio}/{mes}/{categoria_gasto_id}', [PresupuestoController::class, 'destroy'])
        ->name('presupuesto.destroy');

    Route::get('/gastos/{numero_comprobante}/{categoria_gasto_id}/{fecha}/edit', [GastoController::class, 'edit'])
        ->name('gastos.edit');

    Route::put('/gastos/{numero_comprobante}/{categoria_gasto_id}/{fecha}', [GastoController::class, 'update'])
        ->name('gastos.update');

    Route::delete('/gastos/{numero_comprobante}/{categoria_gasto_id}/{fecha}', [GastoController::class, 'destroy'])
        ->name('gastos.destroy');

    // Contabilidad
    Route::get('/contabilidad/cuentas', [ContabilidadController::class, 'indexCuentas'])->name('contabilidad.cuentas.index');
    Route::get('/contabilidad/cuentas/create', [ContabilidadController::class, 'createCuenta'])->name('contabilidad.cuentas.create');
    Route::post('/contabilidad/cuentas', [ContabilidadController::class, 'storeCuenta'])->name('contabilidad.cuentas.store');
    Route::get('/contabilidad/cuentas/{codigo}/edit', [ContabilidadController::class, 'editCuenta'])->name('contabilidad.cuentas.edit');
    Route::put('/contabilidad/cuentas/{codigo}', [ContabilidadController::class, 'updateCuenta'])->name('contabilidad.cuentas.update');

    Route::get('/contabilidad/asientos', [ContabilidadController::class, 'indexAsientos'])->name('contabilidad.asientos.index');
    Route::get('/contabilidad/asientos/create', [ContabilidadController::class, 'createAsiento'])->name('contabilidad.asientos.create');
    Route::post('/contabilidad/asientos', [ContabilidadController::class, 'storeAsiento'])->name('contabilidad.asientos.store');
    Route::get('/contabilidad/asientos/{numero}/{fecha}', [ContabilidadController::class, 'showAsiento'])->name('contabilidad.asientos.show');

    // Inventario
    Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
    Route::get('/inventario/create', [InventarioController::class, 'create'])->name('inventario.create');
    Route::post('/inventario', [InventarioController::class, 'store'])->name('inventario.store');

    // Consultas
    Route::get('/consultas', [ConsultaController::class, 'index'])->name('consultas.index');
    Route::get('/consultas/buscar', [ConsultaController::class, 'buscar'])->name('consultas.buscar');

    // Bitácora
    Route::get('/bitacora', [BitacoraController::class, 'index'])->name('bitacora.index');
    Route::get('/bitacora/intentos', [BitacoraController::class, 'intentosAcceso'])->name('bitacora.intentos');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

require __DIR__ . '/auth.php';