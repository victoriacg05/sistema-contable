<?php

use App\Http\Controllers\ProductoController;
use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Services\CodigoProductoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        'porcentaje_ganancia' => 20,
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
        'porcentaje_ganancia' => 20,
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
        'porcentaje_ganancia' => 20,
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
        'porcentaje_ganancia' => 25,
        'estado' => 1,
    ]);

    app(ProductoController::class)->update($request, $producto);

    expect($producto->fresh()->codigo_barras)
        ->toBe(sprintf('PRD-%03d-0001', $categoriaNueva->id));
});

it('normalizes legacy product codes when a product listing is opened', function () {
    $categoria = CategoriaProducto::create([
        'nombre' => 'Limpieza',
        'descripcion' => 'Productos existentes',
    ]);
    $primerProducto = Producto::create([
        'categoria_producto_id' => $categoria->id,
        'codigo_barras' => 'LIM-001',
        'nombre' => 'Limpiador',
        'descripcion' => 'Producto existente',
        'stock' => 1,
        'stock_minimo' => 0,
        'precio' => 100,
        'porcentaje_ganancia' => 20,
        'estado' => true,
    ]);
    $segundoProducto = Producto::create([
        'categoria_producto_id' => $categoria->id,
        'codigo_barras' => 'LIM-002',
        'nombre' => 'Desinfectante',
        'descripcion' => 'Producto existente',
        'stock' => 1,
        'stock_minimo' => 0,
        'precio' => 100,
        'porcentaje_ganancia' => 20,
        'estado' => true,
    ]);

    app(CodigoProductoService::class)->normalizarExistentes();

    expect($primerProducto->fresh()->codigo_barras)
        ->toBe(sprintf('PRD-%03d-0001', $categoria->id))
        ->and($segundoProducto->fresh()->codigo_barras)
        ->toBe(sprintf('PRD-%03d-0002', $categoria->id));
});

it('calculates supplier and customer prices from the wholesale cost', function () {
    $producto = new Producto([
        'precio' => 100,
        'porcentaje_ganancia' => 25,
    ]);

    expect($producto->precio_compra_con_impuesto)->toBe(113.0)
        ->and($producto->precio_venta_sin_impuesto)->toBe(125.0)
        ->and($producto->precio_venta_con_impuesto)->toBe(141.25);
});

it('converts existing sale prices into wholesale costs with thirty percent profit', function () {
    $categoria = CategoriaProducto::create([
        'nombre' => 'Productos anteriores',
        'descripcion' => 'Categoría de prueba',
    ]);
    $producto = Producto::create([
        'categoria_producto_id' => $categoria->id,
        'codigo_barras' => 'PRD-001-0099',
        'nombre' => 'Producto anterior',
        'descripcion' => 'Precio almacenado con ganancia',
        'stock' => 1,
        'stock_minimo' => 0,
        'precio' => 130,
        'porcentaje_ganancia' => 0,
        'estado' => true,
    ]);

    app(CodigoProductoService::class)->convertirPreciosExistentes();
    app(CodigoProductoService::class)->convertirPreciosExistentes();

    $producto->refresh();

    expect((float) $producto->precio)->toBe(100.0)
        ->and((float) $producto->porcentaje_ganancia)->toBe(30.0)
        ->and($producto->precio_venta_sin_impuesto)->toBe(130.0)
        ->and((float) DB::table('respaldo_precios_productos_20260725')
            ->where('producto_id', $producto->id)
            ->value('precio_anterior'))->toBe(130.0);
});

it('does not reapply the price conversion after it has been rolled back', function () {
    DB::table('estado_conversion_precios_productos_20260725')->updateOrInsert(
        ['id' => 1],
        ['estado' => 'revertida']
    );

    $categoria = CategoriaProducto::create([
        'nombre' => 'Productos restaurados',
        'descripcion' => 'Categoría de prueba',
    ]);
    $producto = Producto::create([
        'categoria_producto_id' => $categoria->id,
        'codigo_barras' => 'PRD-001-0100',
        'nombre' => 'Producto restaurado',
        'descripcion' => 'Precio restaurado por una reversión',
        'stock' => 1,
        'stock_minimo' => 0,
        'precio' => 130,
        'porcentaje_ganancia' => 0,
        'estado' => true,
    ]);

    app(CodigoProductoService::class)->convertirPreciosExistentes();

    $producto->refresh();

    expect((float) $producto->precio)->toBe(130.0)
        ->and((float) $producto->porcentaje_ganancia)->toBe(0.0);
});
