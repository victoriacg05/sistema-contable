<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProveedorController extends Controller
{
    public function index()
    {
        $proveedores = Proveedor::orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25);

        return view('proveedores.index', compact('proveedores'));
    }

    public function create()
    {
        $productos = Producto::where('estado', true)
            ->orderBy('nombre')
            ->get();

        return view('proveedores.create', compact('productos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'identificacion' => 'required|string|max:255|unique:proveedores,identificacion',
            'nombre' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.]+$/'],
            'empresa' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.&,\-]+$/'],
            'telefono' => ['required', 'string', 'max:20', 'regex:/^[245678]\d{3}-?\d{4}$/'],
            'correo' => ['required', 'email', 'max:255', 'unique:proveedores,correo', 'regex:/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/'],
            'productos' => 'nullable|array',
            'productos.*' => 'integer|exists:productos,id',
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            'empresa.regex' => 'El nombre de la empresa contiene caracteres no válidos.',
            'telefono.regex' => 'El teléfono debe tener 8 dígitos y no puede iniciar con 0, 1, 3 o 9. Formato: 2XXX-XXXX o 8XXX-XXXX.',
            'correo.regex' => 'El formato del correo electrónico no es válido.',
        ]);

        DB::transaction(function () use ($request) {
            $proveedor = Proveedor::create([
                'identificacion' => $request->identificacion,
                'nombre' => $request->nombre,
                'empresa' => $request->empresa,
                'telefono' => $request->telefono,
                'correo' => $request->correo,
                'estado' => 1,
            ]);

            $proveedor->productos()->sync($request->input('productos', []));
        });

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor creado correctamente.');
    }

    public function edit(Proveedor $proveedor)
    {
        $productos = Producto::where('estado', true)
            ->orWhereHas('proveedores', function ($query) use ($proveedor) {
                $query->where('proveedores.id', $proveedor->id);
            })
            ->orderBy('nombre')
            ->get();
        $productosSeleccionados = $proveedor->productos()->pluck('productos.id')->all();

        return view('proveedores.edit', compact(
            'proveedor',
            'productos',
            'productosSeleccionados'
        ));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'identificacion' => 'required|string|max:255|unique:proveedores,identificacion,' . $proveedor->id,
            'nombre' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.]+$/'],
            'empresa' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.&,\-]+$/'],
            'telefono' => ['required', 'string', 'max:20', 'regex:/^[245678]\d{3}-?\d{4}$/'],
            'correo' => ['required', 'email', 'max:255', 'unique:proveedores,correo,' . $proveedor->id, 'regex:/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/'],
            'productos' => 'nullable|array',
            'productos.*' => 'integer|exists:productos,id',
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            'empresa.regex' => 'El nombre de la empresa contiene caracteres no válidos.',
            'telefono.regex' => 'El teléfono debe tener 8 dígitos y no puede iniciar con 0, 1, 3 o 9. Formato: 2XXX-XXXX o 8XXX-XXXX.',
            'correo.regex' => 'El formato del correo electrónico no es válido.',
        ]);

        DB::transaction(function () use ($request, $proveedor) {
            $proveedor->update([
                'identificacion' => $request->identificacion,
                'nombre' => $request->nombre,
                'empresa' => $request->empresa,
                'telefono' => $request->telefono,
                'correo' => $request->correo,
                'estado' => $request->has('estado') ? 1 : 0,
            ]);

            $proveedor->productos()->sync($request->input('productos', []));
        });

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