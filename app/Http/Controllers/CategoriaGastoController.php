<?php

namespace App\Http\Controllers;

use App\Models\CategoriaGasto;
use Illuminate\Http\Request;

class CategoriaGastoController extends Controller
{
    public function index()
    {
        $categorias = CategoriaGasto::orderBy('nombre')->get();

        return view('categorias_gastos.index', compact('categorias'));
    }

    public function create()
    {
        return view('categorias_gastos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias_gastos,nombre',
            'descripcion' => 'required|string|max:500',
        ]);

        CategoriaGasto::create($request->only('nombre', 'descripcion'));

        return redirect()
            ->route('categorias-gastos.index')
            ->with('success', 'Categoría de gasto creada correctamente.');
    }

    public function edit(CategoriaGasto $categorias_gasto)
    {
        return view('categorias_gastos.edit', [
            'categoria' => $categorias_gasto
        ]);
    }

    public function update(Request $request, CategoriaGasto $categorias_gasto)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:categorias_gastos,nombre,' . $categorias_gasto->id,
            'descripcion' => 'required|string|max:500',
        ]);

        $categorias_gasto->update($request->only('nombre', 'descripcion'));

        return redirect()
            ->route('categorias-gastos.index')
            ->with('success', 'Categoría de gasto actualizada correctamente.');
    }

    public function destroy(CategoriaGasto $categorias_gasto)
    {
        $categorias_gasto->delete();

        return redirect()
            ->route('categorias-gastos.index')
            ->with('success', 'Categoría de gasto eliminada correctamente.');
    }
}