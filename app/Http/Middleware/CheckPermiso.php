<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        if (!$request->user() || !$request->user()->tienePermiso($permiso)) {
            abort(403, 'No tiene permisos para acceder a este recurso.');
        }

        return $next($request);
    }
}
