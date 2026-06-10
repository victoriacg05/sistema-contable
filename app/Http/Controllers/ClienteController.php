<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::orderBy('nombre')->get();

        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'identificacion' => 'required|string|max:255|unique:clientes,identificacion',
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:clientes,email',
            'telefono' => 'required|string|max:255',
            'direccion' => 'required|string|max:500',
        ]);

        Cliente::create([
            'identificacion' => $request->identificacion,
            'nombre' => $request->nombre,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'direccion' => $request->direccion,
            'estado' => 1,
        ]);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $request->validate([
            'identificacion' => 'required|string|max:255|unique:clientes,identificacion,' . $cliente->id,
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:clientes,email,' . $cliente->id,
            'telefono' => 'required|string|max:255',
            'direccion' => 'required|string|max:500',
        ]);

        $cliente->update([
            'identificacion' => $request->identificacion,
            'nombre' => $request->nombre,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'direccion' => $request->direccion,
            'estado' => $request->has('estado') ? 1 : 0,
        ]);

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return redirect()
            ->route('clientes.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}