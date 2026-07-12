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

    Route::middleware('permiso:ver_clientes')->group(function () {
        Route::resource('clientes', ClienteController::class);
    });

    Route::middleware('permiso:ver_proveedores')->group(function () {
        Route::resource('proveedores', ProveedorController::class)
            ->parameters(['proveedores' => 'proveedor']);
    });

    Route::middleware('permiso:ver_productos')->group(function () {
        Route::resource('productos', ProductoController::class);
    });

    Route::middleware('permiso:ver_facturas')->group(function () {
        Route::resource('facturas', FacturaController::class);
        Route::get('/facturas/{factura}/pdf', [FacturaController::class, 'pdf'])
            ->name('facturas.pdf');
        Route::put('/facturas/{factura}/pagar', [FacturaController::class, 'pagar'])
            ->name('facturas.pagar');
        Route::put('/facturas/{factura}/anular', [FacturaController::class, 'anular'])
            ->name('facturas.anular');
    });

    Route::middleware('permiso:ver_compras')->group(function () {
        Route::get('/compras/clientes', [CompraController::class, 'clientes'])
            ->name('compras.clientes');
        Route::resource('compras', CompraController::class);
        Route::put('/compras/{compra}/pagar', [CompraController::class, 'pagar'])
            ->name('compras.pagar');
    });

    Route::middleware('permiso:ver_usuarios')->group(function () {
        Route::resource('usuarios', UserController::class);
    });

    Route::middleware('permiso:ver_catalogos')->group(function () {
        Route::resource('categorias-productos', CategoriaProductoController::class);
        Route::resource('categorias-gastos', CategoriaGastoController::class);
        Route::resource('metodos-pago', MetodoPagoController::class);
        Route::resource('estados', EstadoController::class);
    });

    Route::middleware('permiso:ver_cuentas_cobrar')->group(function () {
        Route::resource('cuentas-cobrar', CuentaCobrarController::class)
            ->only(['index']);

        Route::get('/cuentas-cobrar/{numero_factura}/{cliente_id}/pago', [CuentaCobrarController::class, 'createPago'])
            ->name('cuentas-cobrar.pago.create');

        Route::post('/cuentas-cobrar/{numero_factura}/{cliente_id}/pago', [CuentaCobrarController::class, 'storePago'])
            ->middleware('permiso:registrar_pago_cobrar')
            ->name('cuentas-cobrar.pago.store');
    });

    Route::middleware('permiso:ver_cuentas_pagar')->group(function () {
        Route::resource('cuentas-pagar', CuentaPagarController::class)
            ->only(['index']);

        Route::get('/cuentas-pagar/{numero_compra}/{proveedor_id}/pago', [CuentaPagarController::class, 'createPago'])
            ->name('cuentas-pagar.pago.create');

        Route::post('/cuentas-pagar/{numero_compra}/{proveedor_id}/pago', [CuentaPagarController::class, 'storePago'])
            ->middleware('permiso:registrar_pago_pagar')
            ->name('cuentas-pagar.pago.store');
    });

    Route::get('/ingresos', [IngresoController::class, 'index'])
        ->middleware('permiso:ver_ingresos')->name('ingresos.index');
    Route::get('/ingresos/create', [IngresoController::class, 'create'])
        ->middleware('permiso:crear_ingresos')->name('ingresos.create');
    Route::post('/ingresos', [IngresoController::class, 'store'])
        ->middleware('permiso:crear_ingresos')->name('ingresos.store');

    Route::get('/ingresos/{referencia_ingreso}/{fecha}/{usuario_id}/edit', [IngresoController::class, 'edit'])
        ->middleware('permiso:editar_ingresos')->name('ingresos.edit');

    Route::put('/ingresos/{referencia_ingreso}/{fecha}/{usuario_id}', [IngresoController::class, 'update'])
        ->middleware('permiso:editar_ingresos')->name('ingresos.update');

    Route::delete('/ingresos/{referencia_ingreso}/{fecha}/{usuario_id}', [IngresoController::class, 'destroy'])
        ->middleware('permiso:eliminar_ingresos')->name('ingresos.destroy');

    Route::get('/gastos', [GastoController::class, 'index'])
        ->middleware('permiso:ver_gastos')->name('gastos.index');
    Route::get('/gastos/create', [GastoController::class, 'create'])
        ->middleware('permiso:crear_gastos')->name('gastos.create');
    Route::post('/gastos', [GastoController::class, 'store'])
        ->middleware('permiso:crear_gastos')->name('gastos.store');

    Route::get('/presupuesto', [PresupuestoController::class, 'index'])
        ->middleware('permiso:ver_presupuesto')->name('presupuesto.index');
    Route::get('/presupuesto/create', [PresupuestoController::class, 'create'])
        ->middleware('permiso:crear_presupuesto')->name('presupuesto.create');
    Route::post('/presupuesto', [PresupuestoController::class, 'store'])
        ->middleware('permiso:crear_presupuesto')->name('presupuesto.store');
    Route::get('/presupuesto/disponible', [PresupuestoController::class, 'disponible'])
        ->middleware('permiso:ver_presupuesto')->name('presupuesto.disponible');
    Route::get('/presupuesto/{anio}/{mes}', [PresupuestoController::class, 'show'])
        ->whereNumber(['anio', 'mes'])
        ->middleware('permiso:ver_presupuesto')
        ->name('presupuesto.show');

    Route::get('/reportes', [ReporteController::class, 'index'])
        ->middleware('permiso:ver_reportes')->name('reportes.index');

    Route::get('/reportes/pdf', [ReporteController::class, 'pdf'])
        ->middleware('permiso:generar_reportes')->name('reportes.pdf');

    Route::get('/presupuesto/{anio}/{mes}/{categoria_gasto_id}/edit', [PresupuestoController::class, 'edit'])
        ->whereNumber(['anio', 'mes', 'categoria_gasto_id'])
        ->middleware('permiso:editar_presupuesto')
        ->name('presupuesto.edit');

    Route::put('/presupuesto/{anio}/{mes}/{categoria_gasto_id}', [PresupuestoController::class, 'update'])
        ->whereNumber(['anio', 'mes', 'categoria_gasto_id'])
        ->middleware('permiso:editar_presupuesto')
        ->name('presupuesto.update');

    Route::delete('/presupuesto/{anio}/{mes}/{categoria_gasto_id}', [PresupuestoController::class, 'destroy'])
        ->whereNumber(['anio', 'mes', 'categoria_gasto_id'])
        ->middleware('permiso:eliminar_presupuesto')
        ->name('presupuesto.destroy');

    Route::delete('/presupuesto/{anio}/{mes}', [PresupuestoController::class, 'destroyPeriodo'])
        ->whereNumber(['anio', 'mes'])
        ->middleware('permiso:eliminar_presupuesto')
        ->name('presupuesto.destroyPeriodo');

    Route::get('/gastos/{numero_comprobante}/{categoria_gasto_id}/{fecha}/edit', [GastoController::class, 'edit'])
        ->middleware('permiso:editar_gastos')->name('gastos.edit');

    Route::put('/gastos/{numero_comprobante}/{categoria_gasto_id}/{fecha}', [GastoController::class, 'update'])
        ->middleware('permiso:editar_gastos')->name('gastos.update');

    Route::delete('/gastos/{numero_comprobante}/{categoria_gasto_id}/{fecha}', [GastoController::class, 'destroy'])
        ->middleware('permiso:eliminar_gastos')->name('gastos.destroy');

    // Contabilidad
    Route::get('/contabilidad/cuentas', [ContabilidadController::class, 'indexCuentas'])
        ->middleware('permiso:ver_contabilidad')->name('contabilidad.cuentas.index');
    Route::get('/contabilidad/cuentas/create', [ContabilidadController::class, 'createCuenta'])
        ->middleware('permiso:editar_cuentas')->name('contabilidad.cuentas.create');
    Route::post('/contabilidad/cuentas', [ContabilidadController::class, 'storeCuenta'])
        ->middleware('permiso:editar_cuentas')->name('contabilidad.cuentas.store');
    Route::get('/contabilidad/cuentas/{codigo}/edit', [ContabilidadController::class, 'editCuenta'])
        ->middleware('permiso:editar_cuentas')->name('contabilidad.cuentas.edit');
    Route::get('/contabilidad/cuentas/{codigo}/movimientos', [ContabilidadController::class, 'showCuenta'])
        ->middleware('permiso:ver_contabilidad')->name('contabilidad.cuentas.show');
    Route::put('/contabilidad/cuentas/{codigo}', [ContabilidadController::class, 'updateCuenta'])
        ->middleware('permiso:editar_cuentas')->name('contabilidad.cuentas.update');

    Route::get('/contabilidad/asientos', [ContabilidadController::class, 'indexAsientos'])
        ->middleware('permiso:ver_contabilidad')->name('contabilidad.asientos.index');
    Route::get('/contabilidad/asientos/create', [ContabilidadController::class, 'createAsiento'])
        ->middleware('permiso:crear_asientos')->name('contabilidad.asientos.create');
    Route::post('/contabilidad/asientos', [ContabilidadController::class, 'storeAsiento'])
        ->middleware('permiso:crear_asientos')->name('contabilidad.asientos.store');
    Route::get('/contabilidad/asientos/{numero}/{fecha}', [ContabilidadController::class, 'showAsiento'])
        ->middleware('permiso:ver_contabilidad')->name('contabilidad.asientos.show');

    // Inventario
    Route::get('/inventario', [InventarioController::class, 'index'])
        ->middleware('permiso:ver_inventario')->name('inventario.index');
    Route::get('/inventario/create', [InventarioController::class, 'create'])
        ->middleware('permiso:crear_movimientos')->name('inventario.create');
    Route::post('/inventario', [InventarioController::class, 'store'])
        ->middleware('permiso:crear_movimientos')->name('inventario.store');

    // Consultas
    Route::get('/consultas', [ConsultaController::class, 'index'])
        ->middleware('permiso:ver_consultas')->name('consultas.index');
    Route::get('/consultas/buscar', [ConsultaController::class, 'buscar'])
        ->middleware('permiso:ver_consultas')->name('consultas.buscar');

    // Bitácora
    Route::get('/bitacora', [BitacoraController::class, 'index'])
        ->middleware('permiso:ver_bitacora')->name('bitacora.index');
    Route::get('/bitacora/intentos', [BitacoraController::class, 'intentosAcceso'])
        ->middleware('permiso:ver_intentos_acceso')->name('bitacora.intentos');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

require __DIR__ . '/auth.php';