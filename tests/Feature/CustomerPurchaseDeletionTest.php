<?php

use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\DatosInicialesSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(DatosInicialesSeeder::class);
    $this->actingAs(User::where('email', 'admin@ipacarai.com')->firstOrFail());
});

it('shows and processes deletion from customer purchases', function () {
    $usuario = User::where('email', 'admin@ipacarai.com')->firstOrFail();
    $cliente = Cliente::create([
        'identificacion' => 'CLI-ELIM-' . str()->random(6),
        'nombre' => 'Cliente para eliminar',
        'email' => str()->random(8) . '@example.com',
        'telefono' => '',
        'direccion' => '',
        'estado' => true,
    ]);
    $categoria = CategoriaProducto::create([
        'nombre' => 'Categoría cliente ' . str()->random(6),
        'descripcion' => 'Prueba de eliminación',
    ]);
    $producto = Producto::create([
        'categoria_producto_id' => $categoria->id,
        'codigo_barras' => 'PRD-CLI-' . str()->random(6),
        'nombre' => 'Producto vendido',
        'descripcion' => '',
        'stock' => 10,
        'stock_minimo' => 1,
        'precio' => 100,
        'porcentaje_ganancia' => 30,
        'estado' => true,
    ]);
    $estadoPagado = DB::table('estados')->where('nombre', 'Pagado')->value('id');
    $metodoPago = DB::table('metodos_pago')->where('nombre', 'Efectivo')->value('id');
    $tipoComprobante = DB::table('tipos_comprobante')->value('id');
    $tipoSalida = DB::table('tipos_movimiento_inventario')->where('nombre', 'Salida')->value('id');
    $numeroFactura = 'FAC-ELIMINAR-' . str()->random(6);

    DB::table('facturas')->insert([
        'numero_factura' => $numeroFactura,
        'cliente_id' => $cliente->id,
        'usuario_id' => $usuario->id,
        'metodo_pago_id' => $metodoPago,
        'estado_id' => $estadoPagado,
        'tipo_comprobante_id' => $tipoComprobante,
        'fecha' => now()->toDateString(),
        'subtotal' => 100,
        'impuesto' => 13,
        'descuento' => 0,
        'total' => 113,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('detalle_facturas')->insert([
        'numero_factura' => $numeroFactura,
        'cliente_id' => $cliente->id,
        'producto_id' => $producto->id,
        'cantidad' => 5,
        'precio_unitario' => 20,
        'subtotal' => 100,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('movimientos_inventario')->insert([
        'referencia_movimiento' => $numeroFactura,
        'producto_id' => $producto->id,
        'usuario_id' => $usuario->id,
        'tipo_movimiento_inventario_id' => $tipoSalida,
        'cantidad' => 5,
        'descripcion' => "Venta a cliente {$numeroFactura}",
        'fecha' => now()->toDateString(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(route('compras.clientes'))
        ->assertOk()
        ->assertSee('Eliminar')
        ->assertSee(route('facturas.destroy', $numeroFactura), false);

    $this->delete(route('facturas.destroy', $numeroFactura), [
        'origen' => 'compras-clientes',
    ])
        ->assertRedirect(route('compras.clientes'))
        ->assertSessionHas('success', 'Factura eliminada correctamente.');

    expect($producto->fresh()->stock)->toBe(15)
        ->and(DB::table('facturas')
            ->where('numero_factura', $numeroFactura)
            ->where('cliente_id', $cliente->id)
            ->exists())->toBeFalse()
        ->and(DB::table('movimientos_inventario')
            ->where('referencia_movimiento', $numeroFactura)
            ->exists())->toBeFalse();
});
