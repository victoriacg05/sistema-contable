<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Services\CodigoProductoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoController extends Controller
{
    public function __construct(
        private readonly CodigoProductoService $codigoProductoService
    ) {
    }

    public function index()
    {
        $productos = Producto::with('categoria')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return view('productos.index', compact('productos'));
    }

    public function create()
    {
        $categorias = CategoriaProducto::orderBy('nombre')->get();

        return view('productos.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'categoria_producto_id' => 'required|exists:categorias_productos,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'precio' => 'required|numeric|min:0',
            'porcentaje_ganancia' => 'required|numeric|min:0.01|max:999.99',
        ]);

        DB::transaction(function () use ($datos) {
            CategoriaProducto::whereKey($datos['categoria_producto_id'])
                ->lockForUpdate()
                ->firstOrFail();

            Producto::create([
                ...$datos,
                'codigo_barras' => $this->codigoProductoService->siguiente((int) $datos['categoria_producto_id']),
                'estado' => 1,
            ]);
        });

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function codigoSugerido(Request $request)
    {
        $datos = $request->validate([
            'categoria_producto_id' => 'required|exists:categorias_productos,id',
        ]);

        return response()->json([
            'codigo' => $this->codigoProductoService->siguiente((int) $datos['categoria_producto_id']),
        ]);
    }

    public function edit(Producto $producto)
    {
        $producto->refresh();
        $categorias = CategoriaProducto::orderBy('nombre')->get();
        $permiteGananciaCero = $this->permiteGananciaCero($producto);

        return view('productos.edit', compact('producto', 'categorias', 'permiteGananciaCero'));
    }

    public function update(Request $request, Producto $producto)
    {
        $gananciaMinima = $this->permiteGananciaCero($producto) ? 0 : 0.01;
        $datos = $request->validate([
            'categoria_producto_id' => 'required|exists:categorias_productos,id',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'precio' => 'required|numeric|min:0',
            'porcentaje_ganancia' => "required|numeric|min:{$gananciaMinima}|max:999.99",
        ]);

        DB::transaction(function () use ($datos, $producto, $request) {
            CategoriaProducto::whereKey($datos['categoria_producto_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $productoBloqueado = Producto::whereKey($producto->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $productoBloqueado->categoria_producto_id !== (int) $datos['categoria_producto_id']) {
                $datos['codigo_barras'] = $this->codigoProductoService->siguiente((int) $datos['categoria_producto_id']);
            }

            $productoBloqueado->update([
                ...$datos,
                'estado' => $request->has('estado') ? 1 : 0,
            ]);
        });

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    private function permiteGananciaCero(Producto $producto): bool
    {
        return (float) $producto->porcentaje_ganancia === 0.0
            && $this->codigoProductoService->conversionPreciosRevertida();
    }

    public function destroy(Producto $producto)
    {
        $producto->delete();

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto eliminado correctamente.');
    }

}