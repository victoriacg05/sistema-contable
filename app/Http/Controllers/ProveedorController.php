<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index()
    {
        $proveedores = Proveedor::orderBy('nombre')->get();

        return view('proveedores.index', compact('proveedores'));
    }

    public function create()
    {
        return view('proveedores.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'identificacion' => 'required|string|max:255|unique:proveedores,identificacion',
            'nombre' => 'required|string|max:255',
            'empresa' => 'required|string|max:255',
            'telefono' => 'required|string|max:255',
            'correo' => 'required|email|max:255|unique:proveedores,correo',
        ]);

        Proveedor::create([
            'identificacion' => $request->identificacion,
            'nombre' => $request->nombre,
            'empresa' => $request->empresa,
            'telefono' => $request->telefono,
            'correo' => $request->correo,
            'estado' => 1,
        ]);

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor creado correctamente.');
    }

    public function edit(Proveedor $proveedor)
    {
        return view('proveedores.edit', compact('proveedor'));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'identificacion' => 'required|string|max:255|unique:proveedores,identificacion,' . $proveedor->id,
            'nombre' => 'required|string|max:255',
            'empresa' => 'required|string|max:255',
            'telefono' => 'required|string|max:255',
            'correo' => 'required|email|max:255|unique:proveedores,correo,' . $proveedor->id,
        ]);

        $proveedor->update([
            'identificacion' => $request->identificacion,
            'nombre' => $request->nombre,
            'empresa' => $request->empresa,
            'telefono' => $request->telefono,
            'correo' => $request->correo,
            'estado' => $request->has('estado') ? 1 : 0,
        ]);

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Proveedor $proveedor)
    {
        $proveedor->delete();

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor eliminado correctamente.');
    }
}