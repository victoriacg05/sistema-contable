<?php

use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\DatosInicialesSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(DatosInicialesSeeder::class);
    $this->actingAs(User::where('email', 'admin@ipacarai.com')->firstOrFail());
});

function crearCompraParaEliminar(float $saldoPendiente = 100): array
{
    $usuario = User::where('email', 'admin@ipacarai.com')->firstOrFail();
    $proveedor = Proveedor::create([
        'identificacion' => 'PROV-ELIMINAR-' . str()->random(6),
        'nombre' => 'Proveedor para eliminar',
        'empresa' => 'Pruebas',
        'telefono' => '',
        'correo' => str()->random(8) . '@example.com',
        'estado' => true,
    ]);
    $categoria = CategoriaProducto::create([
        'nombre' => 'Categoría ' . str()->random(6),
        'descripcion' => 'Prueba de eliminación',
    ]);
    $producto = Producto::create([
        'categoria_producto_id' => $categoria->id,
        'codigo_barras' => 'PRD-ELIM-' . str()->random(6),
        'nombre' => 'Producto para eliminar',
        'descripcion' => '',
        'stock' => 15,
        'stock_minimo' => 1,
        'precio' => 100,
        'porcentaje_ganancia' => 30,
        'estado' => true,
    ]);
    $estadoPendiente = DB::table('estados')->where('nombre', 'Pendiente')->value('id');
    $metodoPago = DB::table('metodos_pago')->where('nombre', 'Efectivo')->value('id');
    $tipoEntrada = DB::table('tipos_movimiento_inventario')->where('nombre', 'Entrada')->value('id');
    $numeroCompra = 'COM-ELIMINAR-' . str()->random(6);

    DB::table('compras')->insert([
        'numero_compra' => $numeroCompra,
        'proveedor_id' => $proveedor->id,
        'usuario_id' => $usuario->id,
        'estado_id' => $estadoPendiente,
        'metodo_pago_id' => $metodoPago,
        'cuenta_bancaria_id' => null,
        'tipo_compra' => 'credito',
        'fecha' => now()->toDateString(),
        'subtotal' => 100,
        'impuesto' => 13,
        'total' => 113,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('detalle_compras')->insert([
        'numero_compra' => $numeroCompra,
        'proveedor_id' => $proveedor->id,
        'producto_id' => $producto->id,
        'cantidad' => 5,
        'precio_unitario' => 20,
        'subtotal' => 100,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('cuentas_pagar')->insert([
        'numero_compra' => $numeroCompra,
        'proveedor_id' => $proveedor->id,
        'monto_original' => 113,
        'saldo_pendiente' => $saldoPendiente,
        'fecha_emision' => now()->toDateString(),
        'fecha_vencimiento' => now()->addMonth()->toDateString(),
        'estado_id' => $estadoPendiente,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('movimientos_inventario')->insert([
        'referencia_movimiento' => $numeroCompra,
        'producto_id' => $producto->id,
        'usuario_id' => $usuario->id,
        'tipo_movimiento_inventario_id' => $tipoEntrada,
        'cantidad' => 5,
        'descripcion' => "Compra a proveedor {$numeroCompra}",
        'fecha' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return compact('numeroCompra', 'proveedor', 'producto');
}

it('eliminates a pending supplier purchase and reverses its inventory trace', function () {
    ['numeroCompra' => $numeroCompra, 'proveedor' => $proveedor, 'producto' => $producto]
        = crearCompraParaEliminar();

    $this->delete(route('compras.destroy', $numeroCompra))
        ->assertRedirect(route('compras.index'))
        ->assertSessionHas('success', 'Compra eliminada correctamente.');

    expect($producto->fresh()->stock)->toBe(10)
        ->and(DB::table('compras')
            ->where('numero_compra', $numeroCompra)
            ->where('proveedor_id', $proveedor->id)
            ->exists())->toBeFalse()
        ->and(DB::table('movimientos_inventario')
            ->where('referencia_movimiento', $numeroCompra)
            ->exists())->toBeFalse();
});

it('explains why a settled supplier purchase cannot be deleted', function () {
    ['numeroCompra' => $numeroCompra] = crearCompraParaEliminar(0);

    $this->from(route('compras.index'))
        ->delete(route('compras.destroy', $numeroCompra))
        ->assertRedirect(route('compras.index'))
        ->assertSessionHasErrors([
            'compra' => 'No se puede eliminar una compra que ya fue liquidada.',
        ]);

    $this->get(route('compras.index'))
        ->assertOk()
        ->assertSee('No fue posible eliminar la compra')
        ->assertSee('No se puede eliminar una compra que ya fue liquidada.');
});
