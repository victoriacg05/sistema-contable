<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;
use App\Services\BitacoraService;

class InventarioController extends Controller
{
    public function index()
    {
        $movimientos = DB::table('movimientos_inventario')
            ->join('productos', 'movimientos_inventario.producto_id', '=', 'productos.id')
            ->join('users', 'movimientos_inventario.usuario_id', '=', 'users.id')
            ->join('tipos_movimiento_inventario', 'movimientos_inventario.tipo_movimiento_inventario_id', '=', 'tipos_movimiento_inventario.id')
            ->select(
                'movimientos_inventario.*',
                'productos.nombre as producto_nombre',
                'users.name as usuario_nombre',
                'tipos_movimiento_inventario.nombre as tipo_nombre'
            )
            ->orderByDesc('movimientos_inventario.fecha')
            ->get();

        return view('inventario.index', compact('movimientos'));
    }

    public function create()
    {
        $productos = Producto::where('estado', true)->orderBy('nombre')->get();
        $tipos = DB::table('tipos_movimiento_inventario')->orderBy('nombre')->get();

        return view('inventario.create', compact('productos', 'tipos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'tipo_movimiento_inventario_id' => 'required|exists:tipos_movimiento_inventario,id',
            'cantidad' => 'required|integer|min:1',
            'descripcion' => 'nullable|string|max:500',
            'fecha' => 'required|date',
        ]);

        $tipo = DB::table('tipos_movimiento_inventario')
            ->where('id', $request->tipo_movimiento_inventario_id)
            ->first();

        $producto = Producto::findOrFail($request->producto_id);

        $esEntrada = in_array(strtolower($tipo->nombre), ['entrada', 'compra', 'ajuste positivo', 'devolución']);

        if (!$esEntrada && $producto->stock < $request->cantidad) {
            return back()->withErrors(['cantidad' => 'Stock insuficiente. Disponible: ' . $producto->stock])->withInput();
        }

        $referencia = 'MOV-' . now()->format('YmdHis');

        DB::table('movimientos_inventario')->insert([
            'referencia_movimiento' => $referencia,
            'producto_id' => $request->producto_id,
            'usuario_id' => Auth::id(),
            'tipo_movimiento_inventario_id' => $request->tipo_movimiento_inventario_id,
            'cantidad' => $request->cantidad,
            'descripcion' => $request->descripcion ?? '',
            'fecha' => $request->fecha,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($esEntrada) {
            $producto->increment('stock', $request->cantidad);
        } else {
            $producto->decrement('stock', $request->cantidad);
        }

        BitacoraService::registrar('crear', 'movimientos_inventario', "Movimiento $referencia: {$tipo->nombre} x{$request->cantidad} - {$producto->nombre}");

        return redirect()->route('inventario.index')
            ->with('success', 'Movimiento de inventario registrado correctamente.');
    }
}
