<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BitacoraService
{
    public static function registrar(string $accion, string $tablaAfectada, string $descripcion = ''): void
    {
        try {
            $usuarioId = Auth::id();

            if (!$usuarioId) {
                return;
            }

            DB::table('bitacora')->insert([
                'usuario_id' => $usuarioId,
                'accion' => $accion,
                'tabla_afectada' => $tablaAfectada,
                'descripcion' => $descripcion,
                'fecha' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Error al registrar en bitácora: ' . $e->getMessage());
        }
    }

    public static function registrarIntentoAcceso(string $email, string $ip, bool $exitoso, string $mensaje = ''): void
    {
        try {
            DB::table('intentos_acceso')->insert([
                'email' => $email,
                'ip_address' => $ip,
                'exitoso' => $exitoso,
                'mensaje' => $mensaje,
                'fecha' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Error al registrar intento de acceso: ' . $e->getMessage());
        }
    }
}
