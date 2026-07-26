<?php

use App\Models\User;
use App\Http\Controllers\ReporteController;
use App\Services\AsientoContableService;
use App\Services\IngresoAutomaticoService;
use Database\Seeders\DatosInicialesSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(DatosInicialesSeeder::class);
    $this->actingAs(User::where('email', 'admin@ipacarai.com')->firstOrFail());
});

it('creates a balanced sale entry and updates it without duplicating it', function () {
    $metodoPagoId = DB::table('metodos_pago')
        ->where('nombre', 'Efectivo')
        ->value('id');

    AsientoContableService::registrarVenta(
        now(),
        'FAC-PRUEBA',
        100,
        13,
        0,
        60,
        true,
        $metodoPagoId
    );

    AsientoContableService::registrarVenta(
        now(),
        'FAC-PRUEBA',
        200,
        26,
        0,
        120,
        true,
        $metodoPagoId
    );

    $asientos = DB::table('asientos_contables')
        ->where('descripcion', 'like', '[AUTO:VENTA:FAC-PRUEBA] %')
        ->get();

    expect($asientos)->toHaveCount(1)
        ->and((float) $asientos->first()->total_debe)->toBe(346.0)
        ->and((float) $asientos->first()->total_haber)->toBe(346.0);
});

it('reverses an automatic entry with the opposite debit and credit totals', function () {
    $metodoPagoId = DB::table('metodos_pago')
        ->where('nombre', 'Transferencia bancaria')
        ->value('id');

    AsientoContableService::registrarIngreso(
        now(),
        'ING-PRUEBA',
        250,
        $metodoPagoId,
        'Ingreso de prueba'
    );

    AsientoContableService::revertir(
        now(),
        'INGRESO:ING-PRUEBA',
        'REVERSO-INGRESO:ING-PRUEBA',
        'Reversión de ingreso de prueba'
    );
    AsientoContableService::revertir(
        now(),
        'INGRESO:ING-PRUEBA',
        'REVERSO-INGRESO:ING-PRUEBA',
        'Reversión de ingreso de prueba'
    );

    $original = DB::table('asientos_contables')
        ->where('descripcion', 'like', '[AUTO:INGRESO:ING-PRUEBA] %')
        ->first();
    $reversion = DB::table('asientos_contables')
        ->where('descripcion', 'like', '[AUTO:REVERSO-INGRESO:ING-PRUEBA] %')
        ->first();
    $cantidadReversiones = DB::table('asientos_contables')
        ->where('descripcion', 'like', '[AUTO:REVERSO-INGRESO:ING-PRUEBA] %')
        ->count();

    expect((float) $original->total_debe)->toBe(250.0)
        ->and((float) $original->total_haber)->toBe(250.0)
        ->and((float) $reversion->total_debe)->toBe(250.0)
        ->and((float) $reversion->total_haber)->toBe(250.0)
        ->and($cantidadReversiones)->toBe(1);

    $lineaBancoOriginal = DB::table('detalle_asientos_contables')
        ->where('numero_asiento', $original->numero_asiento)
        ->where('fecha_asiento', $original->fecha)
        ->where('codigo_cuenta', '1.1.2')
        ->first();
    $lineaBancoReversion = DB::table('detalle_asientos_contables')
        ->where('numero_asiento', $reversion->numero_asiento)
        ->where('fecha_asiento', $reversion->fecha)
        ->where('codigo_cuenta', '1.1.2')
        ->first();

    expect((float) $lineaBancoOriginal->debe)->toBe(250.0)
        ->and((float) $lineaBancoOriginal->haber)->toBe(0.0)
        ->and((float) $lineaBancoReversion->debe)->toBe(0.0)
        ->and((float) $lineaBancoReversion->haber)->toBe(250.0);
});

it('posts inventory losses as adjustments instead of cost of sales', function () {
    AsientoContableService::registrarMovimientoInventario(
        now(),
        'MOV-PRUEBA',
        75,
        false,
        'Ajuste negativo'
    );

    $asiento = DB::table('asientos_contables')
        ->where('descripcion', 'like', '[AUTO:INVENTARIO:MOV-PRUEBA] %')
        ->first();

    expect(DB::table('detalle_asientos_contables')
        ->where('numero_asiento', $asiento->numero_asiento)
        ->where('fecha_asiento', $asiento->fecha)
        ->where('codigo_cuenta', '5.2')
        ->where('debe', 75)
        ->exists())->toBeTrue()
        ->and(DB::table('detalle_asientos_contables')
            ->where('numero_asiento', $asiento->numero_asiento)
            ->where('fecha_asiento', $asiento->fecha)
            ->where('codigo_cuenta', '5.1')
            ->exists())->toBeFalse();
});

it('registers cash sales and receivable collections in income without duplicating journals', function () {
    $usuario = User::where('email', 'admin@ipacarai.com')->firstOrFail();
    $metodoPagoId = DB::table('metodos_pago')
        ->where('nombre', 'Efectivo')
        ->value('id');
    $asientosAntes = DB::table('asientos_contables')->count();

    IngresoAutomaticoService::registrarVentaContado(
        'FAC-INGRESO',
        now(),
        $usuario->id,
        $metodoPagoId,
        125
    );
    IngresoAutomaticoService::registrarCobro(
        'PCL-INGRESO',
        'FAC-CREDITO',
        now(),
        $usuario->id,
        $metodoPagoId,
        80
    );

    expect(DB::table('ingresos')
        ->where('referencia_ingreso', 'AUTO-VENTA-FAC-INGRESO')
        ->where('monto', 125)
        ->exists())->toBeTrue()
        ->and(DB::table('ingresos')
            ->where('referencia_ingreso', 'AUTO-COBRO-PCL-INGRESO')
            ->where('monto', 80)
            ->exists())->toBeTrue()
        ->and(DB::table('asientos_contables')->count())->toBe($asientosAntes);
});

it('excludes automatic receipts from additional income in financial reports', function () {
    $usuario = User::where('email', 'admin@ipacarai.com')->firstOrFail();
    $metodoPagoId = DB::table('metodos_pago')
        ->where('nombre', 'Efectivo')
        ->value('id');

    DB::table('ingresos')->insert([
        'referencia_ingreso' => 'ING-MANUAL-PRUEBA',
        'usuario_id' => $usuario->id,
        'metodo_pago_id' => $metodoPagoId,
        'origen' => 'Ingreso adicional',
        'descripcion' => '',
        'monto' => 50,
        'fecha' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    IngresoAutomaticoService::registrarVentaContado(
        'FAC-REPORTE',
        now(),
        $usuario->id,
        $metodoPagoId,
        125
    );

    $vista = app(ReporteController::class)->index(Request::create('/reportes', 'GET', [
        'anio' => now()->year,
        'mes' => now()->month,
    ]));

    expect((float) $vista->getData()['ingresos'])->toBe(50.0)
        ->and((float) $vista->getData()['resultado'])->toBe(50.0)
        ->and($vista->getData())->toHaveKeys([
            'ventasNetas',
            'utilidadBruta',
            'saldoBancos',
            'valorInventario',
            'comparativo',
            'topProductos',
            'presupuestoVsGasto',
        ]);
});
