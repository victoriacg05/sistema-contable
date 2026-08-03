<?php

use App\Http\Controllers\ProveedorController;
use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;

it('creates a supplier with new products linked automatically', function () {
    $categoria = CategoriaProducto::create([
        'nombre' => 'Productos del proveedor',
        'descripcion' => 'Categoría de prueba',
    ]);

    $request = Request::create('/proveedores', 'POST', [
        'identificacion' => '3-101-123456',
        'nombre' => 'Proveedor Nuevo',
        'empresa' => 'Empresa Nueva',
        'telefono' => '2222-2222',
        'correo' => 'proveedor-nuevo@example.com',
        'productos_nuevos' => [
            [
                'categoria_producto_id' => $categoria->id,
                'nombre' => 'Producto Uno',
                'descripcion' => 'Primer producto del proveedor',
                'stock_minimo' => 2,
                'precio' => 100,
                'porcentaje_ganancia' => 30,
            ],
            [
                'categoria_producto_id' => $categoria->id,
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
    $productos = Producto::whereIn('nombre', ['Producto Uno', 'Producto Dos'])
        ->orderBy('nombre')
        ->get();

    expect($productos)->toHaveCount(2)
        ->and($productos->pluck('stock')->all())->toBe([0, 0])
        ->and($productos->pluck('codigo_barras')->all())->toBe([
            sprintf('PRD-%03d-0002', $categoria->id),
            sprintf('PRD-%03d-0001', $categoria->id),
        ])
        ->and($proveedor->productos()->count())->toBe(2);
});
