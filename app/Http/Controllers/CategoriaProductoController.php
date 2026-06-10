<?php

namespace App\Http\Controllers;

use App\Models\CategoriaProducto;
use Illuminate\Http\Request;

class CategoriaProductoController extends Controller
{
    public function index()
    {
        $categorias = CategoriaProducto::orderBy('nombre')->get();

        return view('categorias_productos.index', compact('categorias'));
    }

    public function create()
    {
        return view('categorias_productos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias_productos,nombre',
            'descripcion' => 'required|string|max:500',
        ]);

        CategoriaProducto::create($request->only('nombre', 'descripcion'));

        return redirect()
            ->route('categorias-productos.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function edit(CategoriaProducto $categorias_producto)
    {
        return view('categorias_productos.edit', [
            'categoria' => $categorias_producto
        ]);
    }

    public function update(Request $request, CategoriaProducto $categorias_producto)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias_productos,nombre,' . $categorias_producto->id,
            'descripcion' => 'required|string|max:500',
        ]);

        $categorias_producto->update($request->only('nombre', 'descripcion'));

        return redirect()
            ->route('categorias-productos.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(CategoriaProducto $categorias_producto)
    {
        $categorias_producto->delete();

        return redirect()
            ->route('categorias-productos.index')
            ->with('success', 'Categoría eliminada correctamente.');
    }
}