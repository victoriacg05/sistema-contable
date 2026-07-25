<?php

use App\Http\Controllers\ProductoController;
use App\Models\CategoriaProducto;
use App\Models\Producto;
use Illuminate\Http\Request;

it('generates the product code from the selected category', function () {
    $categoria = CategoriaProducto::create([
        'nombre' => 'Bebidas',
        'descripcion' => 'Productos de prueba',
    ]);

    Producto::create([
        'categoria_producto_id' => $categoria->id,
        'codigo_barras' => sprintf('PRD-%03d-0007', $categoria->id),
        'nombre' => 'Producto existente',
        'descripcion' => 'Producto de prueba',
        'stock' => 1,
        'stock_minimo' => 0,
        'precio' => 100,
        'estado' => true,
    ]);

    $request = Request::create('/productos', 'POST', [
        'categoria_producto_id' => $categoria->id,
        'codigo_barras' => 'CODIGO-MANUAL-IGNORADO',
        'nombre' => 'Producto nuevo',
        'descripcion' => 'Producto generado automáticamente',
        'stock' => 5,
        'stock_minimo' => 1,
        'precio' => 250,
    ]);

    app(ProductoController::class)->store($request);

    $producto = Producto::where('nombre', 'Producto nuevo')->firstOrFail();

    expect($producto->codigo_barras)
        ->toBe(sprintf('PRD-%03d-0008', $categoria->id));
});

it('regenerates the code when the product category changes', function () {
    $categoriaOriginal = CategoriaProducto::create([
        'nombre' => 'Abarrotes',
        'descripcion' => 'Categoría original',
    ]);
    $categoriaNueva = CategoriaProducto::create([
        'nombre' => 'Limpieza',
        'descripcion' => 'Categoría nueva',
    ]);
    $producto = Producto::create([
        'categoria_producto_id' => $categoriaOriginal->id,
        'codigo_barras' => 'PRD-001-0042',
        'nombre' => 'Producto',
        'descripcion' => 'Descripción original',
        'stock' => 1,
        'stock_minimo' => 0,
        'precio' => 100,
        'estado' => true,
    ]);

    $request = Request::create('/productos/' . $producto->id, 'PUT', [
        'categoria_producto_id' => $categoriaNueva->id,
        'codigo_barras' => 'CODIGO-MANUAL-IGNORADO',
        'nombre' => 'Producto editado',
        'descripcion' => 'Descripción actualizada',
        'stock' => 2,
        'stock_minimo' => 1,
        'precio' => 150,
        'estado' => 1,
    ]);

    app(ProductoController::class)->update($request, $producto);

    expect($producto->fresh()->codigo_barras)
        ->toBe(sprintf('PRD-%03d-0001', $categoriaNueva->id));
});
