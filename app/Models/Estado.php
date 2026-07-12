<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class Estado extends Model
{
    protected $table = 'estados';

    public const PENDIENTE = 'Pendiente';
    public const PAGADO = 'Pagado';
    public const PARCIAL = 'Parcial';
    public const VENCIDO = 'Vencido';
    public const ANULADO = 'Anulado';
    public const ACTIVO = 'Activo';
    public const INACTIVO = 'Inactivo';
    public const APROBADO = 'Aprobado';

    /** @var array<string, int> Cache de resolución nombre → id. */
    private static array $cache = [];

    /**
     * Resuelve el id de un estado por su nombre de forma insensible a
     * mayúsculas/minúsculas. Evita los IDs numéricos mágicos que se rompen si
     * el orden del seeder cambia. Falla de forma explícita si el estado no
     * existe, en lugar de corromper datos silenciosamente.
     */
    public static function idPorNombre(string $nombre): int
    {
        $clave = mb_strtolower(trim($nombre));

        if (isset(self::$cache[$clave])) {
            return self::$cache[$clave];
        }

        $id = static::whereRaw('LOWER(nombre) = ?', [$clave])->value('id');

        if ($id === null) {
            throw new RuntimeException(
                "No existe el estado «{$nombre}». Ejecute el seeder de datos iniciales (DatosInicialesSeeder)."
            );
        }

        return self::$cache[$clave] = (int) $id;
    }
}
