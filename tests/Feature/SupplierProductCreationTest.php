<?php

use App\Http\Controllers\ProveedorController;
use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;

it('creates a supplier with new products linked automatically', function () {
    $request = Request::create('/proveedores', 'POST', [
        'identificacion' => '3-101-123456',
        'nombre' => 'Proveedor Nuevo',
        'empresa' => 'Empresa Nueva',
        'telefono' => '2222-2222',
        'correo' => 'proveedor-nuevo@example.com',
        'productos_nuevos' => [
            [
                'categoria_nombre' => 'Productos del proveedor',
                'categoria_descripcion' => 'Categoría creada con el proveedor',
                'nombre' => 'Producto Uno',
                'descripcion' => 'Primer producto del proveedor',
                'stock_minimo' => 2,
                'precio' => 100,
                'porcentaje_ganancia' => 30,
            ],
            [
                'categoria_nombre' => 'Productos del proveedor',
                'categoria_descripcion' => 'Categoría creada con el proveedor',
                'nombre' => 'Producto Dos',
                'descripcion' => 'Segundo producto del proveedor',
                'stock_minimo' => 3,
                'precio' => 200,
                'porcentaje_ganancia' => 25,
            ],
        ],
    ]);

    app(ProveedorController::class)->store($request);

    $proveedor = Proveedor::where('correo', 'proveedor-nuevo@example.com')
        ->firstOrFail();
    $categoria = CategoriaProducto::where('nombre', 'Productos del proveedor')
        ->firstOrFail();
    $productos = Producto::whereIn('nombre', ['Producto Uno', 'Producto Dos'])
        ->orderBy('nombre')
        ->get();

    expect($productos)->toHaveCount(2)
        ->and(CategoriaProducto::where('nombre', 'Productos del proveedor')->count())->toBe(1)
        ->and($productos->pluck('categoria_producto_id')->unique()->all())->toBe([$categoria->id])
        ->and($productos->pluck('stock')->all())->toBe([0, 0])
        ->and($productos->pluck('codigo_barras')->all())->toBe([
            sprintf('PRD-%03d-0002', $categoria->id),
            sprintf('PRD-%03d-0001', $categoria->id),
        ])
        ->and($proveedor->productos()->count())->toBe(2);
});

it('reuses an existing category regardless of accents and letter case', function () {
    $categoria = CategoriaProducto::create([
        'nombre' => 'Plásticos',
        'descripcion' => 'Categoría existente',
    ]);

    $request = Request::create('/proveedores', 'POST', [
        'identificacion' => '3-101-654321',
        'nombre' => 'Proveedor Plásticos',
        'empresa' => 'Empresa Plásticos',
        'telefono' => '8888-8888',
        'correo' => 'plasticos@example.com',
        'productos_nuevos' => [
            [
                'categoria_nombre' => 'PLÁSTICOS',
                'categoria_descripcion' => 'No debe crear otra categoría',
                'nombre' => 'Bolsa reutilizable',
                'descripcion' => 'Producto de prueba',
                'stock_minimo' => 1,
                'precio' => 50,
                'porcentaje_ganancia' => 30,
            ],
        ],
    ]);

    app(ProveedorController::class)->store($request);

    $producto = Producto::where('nombre', 'Bolsa reutilizable')->firstOrFail();

    expect(CategoriaProducto::count())->toBe(1)
        ->and($producto->categoria_producto_id)->toBe($categoria->id);
});
