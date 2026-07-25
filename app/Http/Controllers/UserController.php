<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::with('role')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $roles = Role::orderBy('nombre')->get();

        return view('usuarios.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'],
            'email' => ['required', 'email', 'unique:users,email', 'regex:/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/'],
            'password' => 'required|min:6',
            'rol_id' => 'required|exists:roles,id',
            'fecha_activacion' => 'nullable|date',
        ], [
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'email.regex' => 'El formato del correo electrónico no es válido.',
        ]);

        $fechaActivacion = $request->filled('fecha_activacion')
            ? Carbon::parse($request->fecha_activacion)->startOfDay()
            : null;

        // Si la fecha de activación es futura, el usuario queda deshabilitado
        // hasta esa fecha. Sin fecha (o fecha ya alcanzada), acceso inmediato.
        $estado = ($fechaActivacion && $fechaActivacion->isFuture()) ? 0 : 1;

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol_id' => $request->rol_id,
            'estado' => $estado,
            'fecha_activacion' => $fechaActivacion,
        ]);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        $roles = Role::orderBy('nombre')->get();

        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/'],
            'email' => ['required', 'email', 'unique:users,email,' . $usuario->id, 'regex:/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/'],
            'password' => 'nullable|min:6',
            'rol_id' => 'required|exists:roles,id',
        ], [
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'email.regex' => 'El formato del correo electrónico no es válido.',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'rol_id' => $request->rol_id,
            'estado' => $request->has('estado') ? 1 : 0,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $usuario->update($data);

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        $usuario->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}