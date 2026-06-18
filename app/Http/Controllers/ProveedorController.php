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
            'nombre' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.]+$/'],
            'empresa' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.&,\-]+$/'],
            'telefono' => ['required', 'string', 'max:20', 'regex:/^[245678]\d{3}-?\d{4}$/'],
            'correo' => ['required', 'email', 'max:255', 'unique:proveedores,correo', 'regex:/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/'],
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            'empresa.regex' => 'El nombre de la empresa contiene caracteres no válidos.',
            'telefono.regex' => 'El teléfono debe tener 8 dígitos y no puede iniciar con 0, 1, 3 o 9. Formato: 2XXX-XXXX o 8XXX-XXXX.',
            'correo.regex' => 'El formato del correo electrónico no es válido.',
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
            'nombre' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.]+$/'],
            'empresa' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.&,\-]+$/'],
            'telefono' => ['required', 'string', 'max:20', 'regex:/^[245678]\d{3}-?\d{4}$/'],
            'correo' => ['required', 'email', 'max:255', 'unique:proveedores,correo,' . $proveedor->id, 'regex:/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/'],
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            'empresa.regex' => 'El nombre de la empresa contiene caracteres no válidos.',
            'telefono.regex' => 'El teléfono debe tener 8 dígitos y no puede iniciar con 0, 1, 3 o 9. Formato: 2XXX-XXXX o 8XXX-XXXX.',
            'correo.regex' => 'El formato del correo electrónico no es válido.',
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