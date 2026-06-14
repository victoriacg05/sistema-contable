<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BitacoraController extends Controller
{
    public function index()
    {
        $registros = DB::table('bitacora')
            ->join('users', 'bitacora.usuario_id', '=', 'users.id')
            ->select('bitacora.*', 'users.name as usuario_nombre')
            ->orderByDesc('bitacora.fecha')
            ->paginate(50);

        return view('bitacora.index', compact('registros'));
    }

    public function intentosAcceso()
    {
        $intentos = DB::table('intentos_acceso')
            ->orderByDesc('fecha')
            ->paginate(50);

        return view('bitacora.intentos', compact('intentos'));
    }
}
