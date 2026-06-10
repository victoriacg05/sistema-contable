<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\CategoriaProducto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::with('categoria')
            ->orderBy('nombre')
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
        $request->validate([
            'categoria_producto_id' => 'required|exists:categorias_productos,id',
            'codigo_barras' => 'required|string|max:255|unique:productos,codigo_barras',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'precio' => 'required|numeric|min:0',
        ]);

        Producto::create([
            'categoria_producto_id' => $request->categoria_producto_id,
            'codigo_barras' => $request->codigo_barras,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'stock' => $request->stock,
            'stock_minimo' => $request->stock_minimo,
            'precio' => $request->precio,
            'estado' => 1,
        ]);

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto)
    {
        $categorias = CategoriaProducto::orderBy('nombre')->get();

        return view('productos.edit', compact('producto', 'categorias'));
    }

    public function update(Request $request, Producto $producto)
    {
        $request->validate([
            'categoria_producto_id' => 'required|exists:categorias_productos,id',
            'codigo_barras' => 'required|string|max:255|unique:productos,codigo_barras,' . $producto->id,
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string|max:500',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'precio' => 'required|numeric|min:0',
        ]);

        $producto->update([
            'categoria_producto_id' => $request->categoria_producto_id,
            'codigo_barras' => $request->codigo_barras,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'stock' => $request->stock,
            'stock_minimo' => $request->stock_minimo,
            'precio' => $request->precio,
            'estado' => $request->has('estado') ? 1 : 0,
        ]);

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto)
    {
        $producto->delete();

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto eliminado correctamente.');
    }
}