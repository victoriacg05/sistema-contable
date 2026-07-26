<?php

use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatosInicialesSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(DatosInicialesSeeder::class);
    $this->actingAs(User::where('email', 'admin@ipacarai.com')->firstOrFail());
});

it('shows a guided read only consultation center', function () {
    $this->get(route('consultas.index'))
        ->assertOk()
        ->assertSee('Centro de consultas')
        ->assertSee('Solo lectura')
        ->assertSee('Facturas y ventas')
        ->assertSee('Movimientos de inventario')
        ->assertSee('Asientos contables');
});

it('searches products without modifying their information', function () {
    $categoria = CategoriaProducto::create([
        'nombre' => 'Consulta de productos',
        'descripcion' => 'Categoría para consulta',
    ]);
    $producto = Producto::create([
        'categoria_producto_id' => $categoria->id,
        'codigo_barras' => 'PRD-999-0001',
        'nombre' => 'Producto consultable',
        'descripcion' => 'Producto de prueba',
        'stock' => 12,
        'stock_minimo' => 3,
        'precio' => 100,
        'porcentaje_ganancia' => 30,
        'estado' => true,
    ]);

    $this->get(route('consultas.buscar', [
        'modulo' => 'productos',
        'termino' => 'consultable',
    ]))
        ->assertOk()
        ->assertSee('Producto consultable')
        ->assertSee('PRD-999-0001')
        ->assertSee('₡100.00');

    $producto->refresh();

    expect($producto->stock)->toBe(12)
        ->and((float) $producto->precio)->toBe(100.0);
});

it('denies consultation access without the required permission', function () {
    $rol = Role::create([
        'nombre' => 'Sin consultas',
        'descripcion' => 'Rol sin acceso al módulo',
    ]);
    $usuario = User::create([
        'rol_id' => $rol->id,
        'name' => 'Usuario sin consultas',
        'email' => 'sin-consultas@example.com',
        'password' => Hash::make('password'),
        'estado' => true,
    ]);

    $this->actingAs($usuario)
        ->get(route('consultas.index'))
        ->assertForbidden();
});
