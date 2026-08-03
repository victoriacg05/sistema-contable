<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Services\CodigoProductoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProveedorController extends Controller
{
    public function __construct(
        private readonly CodigoProductoService $codigoProductoService
    ) {
    }

    public function index()
    {
        $proveedores = Proveedor::orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25);

        return view('proveedores.index', compact('proveedores'));
    }

    public function create()
    {
        $categorias = CategoriaProducto::orderBy('nombre')->get();

        return view('proveedores.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'identificacion' => 'required|string|max:255|unique:proveedores,identificacion',
            'nombre' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.]+$/'],
            'empresa' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s.&,\-]+$/'],
            'telefono' => ['required', 'string', 'max:20', 'regex:/^[245678]\d{3}-?\d{4}$/'],
            'correo' => ['required', 'email', 'max:255', 'unique:proveedores,correo', 'regex:/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/'],
            'productos_nuevos' => 'required|array|min:1|max:50',
            'productos_nuevos.*.categoria_producto_id' => 'required|integer|exists:categorias_productos,id',
            'productos_nuevos.*.nombre' => 'required|string|max:255',
            'productos_nuevos.*.descripcion' => 'required|string|max:500',
            'productos_nuevos.*.stock_minimo' => 'required|integer|min:0|max:2147483647',
            'productos_nuevos.*.precio' => 'required|numeric|min:0|max:99999999.99',
            'productos_nuevos.*.porcentaje_ganancia' => 'required|numeric|min:0.01|max:999.99',
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            'empresa.regex' => 'El nombre de la empresa contiene caracteres no válidos.',
            'telefono.regex' => 'El teléfono debe tener 8 dígitos y no puede iniciar con 0, 1, 3 o 9. Formato: 2XXX-XXXX o 8XXX-XXXX.',
            'correo.regex' => 'El formato del correo electrónico no es válido.',
            'productos_nuevos.required' => 'Debe agregar al menos un producto nuevo para el proveedor.',
            'productos_nuevos.min' => 'Debe agregar al menos un producto nuevo para el proveedor.',
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

            $productosNuevos = collect($request->input('productos_nuevos'));

            CategoriaProducto::whereIn(
                'id',
                $productosNuevos->pluck('categoria_producto_id')->unique()
            )
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);

            $productosIds = $productosNuevos->map(function (array $datos) {
                return Producto::create([
                    'categoria_producto_id' => $datos['categoria_producto_id'],
                    'codigo_barras' => $this->codigoProductoService->siguiente(
                        (int) $datos['categoria_producto_id']
                    ),
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'],
                    'stock' => 0,
                    'stock_minimo' => $datos['stock_minimo'],
                    'precio' => $datos['precio'],
                    'porcentaje_ganancia' => $datos['porcentaje_ganancia'],
                    'estado' => 1,
                ])->id;
            });

            $proveedor->productos()->sync($productosIds->all());
        }, 3);

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor y productos creados correctamente.');
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