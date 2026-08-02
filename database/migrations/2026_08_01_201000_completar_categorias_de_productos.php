<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $catalogo = [
            'Bebidas' => [
                'proveedor' => [
                    'identificacion' => '3-101-900001',
                    'nombre' => 'María Fernández',
                    'empresa' => 'Distribuidora de Bebidas Tropicales S.A.',
                    'telefono' => '2222-4100',
                    'correo' => 'ventas@bebidastropicales.cr',
                ],
                'productos' => [
                    ['nombre' => 'Agua embotellada 600ml', 'descripcion' => 'Agua purificada en botella individual de 600ml.', 'stock' => 120, 'stock_minimo' => 30, 'precio' => 476.51],
                    ['nombre' => 'Refresco gaseoso de cola 2.5L', 'descripcion' => 'Refresco gaseoso sabor cola en presentación familiar de 2.5 litros.', 'stock' => 60, 'stock_minimo' => 15, 'precio' => 1361.47],
                    ['nombre' => 'Jugo de naranja 1L', 'descripcion' => 'Jugo sabor naranja en envase de un litro.', 'stock' => 50, 'stock_minimo' => 12, 'precio' => 1225.32],
                    ['nombre' => 'Bebida hidratante 600ml', 'descripcion' => 'Bebida hidratante para actividad física en botella de 600ml.', 'stock' => 70, 'stock_minimo' => 20, 'precio' => 884.96],
                ],
            ],
            'Alimentos' => [
                'proveedor' => [
                    'identificacion' => '3-101-900002',
                    'nombre' => 'Carlos Rodríguez',
                    'empresa' => 'Alimentos del Valle S.A.',
                    'telefono' => '2222-4200',
                    'correo' => 'ventas@alimentosdelvalle.cr',
                ],
                'productos' => [
                    ['nombre' => 'Arroz blanco 1kg', 'descripcion' => 'Arroz blanco de grano entero en bolsa de un kilogramo.', 'stock' => 100, 'stock_minimo' => 25, 'precio' => 953.03],
                    ['nombre' => 'Frijoles negros 900g', 'descripcion' => 'Frijoles negros seleccionados en bolsa de 900 gramos.', 'stock' => 80, 'stock_minimo' => 20, 'precio' => 1157.25],
                    ['nombre' => 'Azúcar blanca 1kg', 'descripcion' => 'Azúcar blanca granulada en bolsa de un kilogramo.', 'stock' => 90, 'stock_minimo' => 20, 'precio' => 748.81],
                    ['nombre' => 'Aceite vegetal 1L', 'descripcion' => 'Aceite vegetal para cocina en botella de un litro.', 'stock' => 60, 'stock_minimo' => 15, 'precio' => 1497.62],
                ],
            ],
            'Plásticos' => [
                'proveedor' => [
                    'identificacion' => '3-101-900003',
                    'nombre' => 'Andrea Vargas',
                    'empresa' => 'Empaques Plásticos del Centro S.A.',
                    'telefono' => '2222-4300',
                    'correo' => 'ventas@empaquesplasticos.cr',
                ],
                'productos' => [
                    ['nombre' => 'Bolsas plásticas para basura 10 unidades', 'descripcion' => 'Paquete de diez bolsas plásticas resistentes para basura.', 'stock' => 80, 'stock_minimo' => 20, 'precio' => 1225.32],
                    ['nombre' => 'Vasos plásticos desechables 25 unidades', 'descripcion' => 'Paquete de vasos plásticos desechables para bebidas.', 'stock' => 70, 'stock_minimo' => 15, 'precio' => 1021.10],
                    ['nombre' => 'Envases plásticos con tapa 500ml', 'descripcion' => 'Paquete de envases plásticos de 500ml con tapa.', 'stock' => 60, 'stock_minimo' => 15, 'precio' => 1497.62],
                    ['nombre' => 'Film plástico adherente 30m', 'descripcion' => 'Rollo de film plástico adherente de 30 metros.', 'stock' => 55, 'stock_minimo' => 12, 'precio' => 1157.25],
                ],
            ],
            'Cartón' => [
                'proveedor' => [
                    'identificacion' => '3-101-900004',
                    'nombre' => 'Roberto Solano',
                    'empresa' => 'Cartonera Nacional S.A.',
                    'telefono' => '2222-4400',
                    'correo' => 'ventas@cartoneranacional.cr',
                ],
                'productos' => [
                    ['nombre' => 'Caja de cartón mediana', 'descripcion' => 'Caja de cartón corrugado mediana para almacenamiento y transporte.', 'stock' => 100, 'stock_minimo' => 25, 'precio' => 612.66],
                    ['nombre' => 'Caja de cartón grande', 'descripcion' => 'Caja de cartón corrugado grande para almacenamiento y transporte.', 'stock' => 80, 'stock_minimo' => 20, 'precio' => 953.03],
                    ['nombre' => 'Lámina de cartón corrugado', 'descripcion' => 'Lámina de cartón corrugado para protección y embalaje.', 'stock' => 120, 'stock_minimo' => 30, 'precio' => 510.55],
                    ['nombre' => 'Rollo de cartón protector', 'descripcion' => 'Rollo de cartón flexible para proteger productos durante el transporte.', 'stock' => 45, 'stock_minimo' => 10, 'precio' => 1701.84],
                ],
            ],
        ];

        DB::transaction(function () use ($catalogo) {
            foreach ($catalogo as $nombreCategoria => $datos) {
                $nombresCategoria = match ($nombreCategoria) {
                    'Plásticos' => ['Plásticos', 'Plasticos'],
                    'Cartón' => ['Cartón', 'Carton'],
                    default => [$nombreCategoria],
                };

                $categoriaId = DB::table('categorias_productos')
                    ->whereIn('nombre', $nombresCategoria)
                    ->value('id');

                if (! $categoriaId) {
                    $descripcionCategoria = match ($nombreCategoria) {
                        'Bebidas' => 'Productos líquidos para consumo.',
                        'Alimentos' => 'Productos alimenticios de consumo general.',
                        'Plásticos' => 'Productos y empaques elaborados en plástico.',
                        'Cartón' => 'Productos de cartón para empaque y protección.',
                    };

                    $categoriaId = DB::table('categorias_productos')->insertGetId([
                        'nombre' => $nombreCategoria,
                        'descripcion' => $descripcionCategoria,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('proveedores')->updateOrInsert(
                    ['identificacion' => $datos['proveedor']['identificacion']],
                    [
                        ...$datos['proveedor'],
                        'estado' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $proveedorId = DB::table('proveedores')
                    ->where('identificacion', $datos['proveedor']['identificacion'])
                    ->value('id');

                foreach ($datos['productos'] as $producto) {
                    $productoId = DB::table('productos')
                        ->where('categoria_producto_id', $categoriaId)
                        ->where('nombre', $producto['nombre'])
                        ->value('id');

                    if (! $productoId) {
                        $productoId = DB::table('productos')->insertGetId([
                            'categoria_producto_id' => $categoriaId,
                            'codigo_barras' => $this->siguienteCodigo((int) $categoriaId),
                            ...$producto,
                            'porcentaje_ganancia' => 30,
                            'estado' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('proveedor_producto')->insertOrIgnore([
                        'proveedor_id' => $proveedorId,
                        'producto_id' => $productoId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Los productos pueden tener compras, ventas o movimientos posteriores.
    }

    private function siguienteCodigo(int $categoriaId): string
    {
        $prefijo = sprintf('PRD-%03d-', $categoriaId);
        $ultimaSecuencia = DB::table('productos')
            ->where('categoria_producto_id', $categoriaId)
            ->where('codigo_barras', 'like', $prefijo . '%')
            ->pluck('codigo_barras')
            ->reduce(function (int $maximo, string $codigo) use ($prefijo) {
                $secuencia = substr($codigo, strlen($prefijo));

                return ctype_digit($secuencia)
                    ? max($maximo, (int) $secuencia)
                    : $maximo;
            }, 0);

        return $prefijo . sprintf('%04d', $ultimaSecuencia + 1);
    }
};
