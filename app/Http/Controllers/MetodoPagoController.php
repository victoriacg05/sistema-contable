<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use Illuminate\Http\Request;

class MetodoPagoController extends Controller
{
    public function index()
    {
        $metodos = MetodoPago::orderBy('nombre')->get();

        return view('metodos_pago.index', compact('metodos'));
    }

    public function create()
    {
        return view('metodos_pago.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:metodos_pago,nombre',
            'descripcion' => 'required|string|max:500',
        ]);

        MetodoPago::create($request->only('nombre', 'descripcion'));

        return redirect()
            ->route('metodos-pago.index')
            ->with('success', 'Método de pago creado correctamente.');
    }

    public function edit(MetodoPago $metodos_pago)
    {
        return view('metodos_pago.edit', [
            'metodo' => $metodos_pago
        ]);
    }

    public function update(Request $request, MetodoPago $metodos_pago)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:metodos_pago,nombre,' . $metodos_pago->id,
            'descripcion' => 'required|string|max:500',
        ]);

        $metodos_pago->update($request->only('nombre', 'descripcion'));

        return redirect()
            ->route('metodos-pago.index')
            ->with('success', 'Método de pago actualizado correctamente.');
    }

    public function destroy(MetodoPago $metodos_pago)
    {
        $metodos_pago->delete();

        return redirect()
            ->route('metodos-pago.index')
            ->with('success', 'Método de pago eliminado correctamente.');
    }
}