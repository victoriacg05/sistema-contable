<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSlowRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $response = $next($request);
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

        $response->headers->set(
            'Server-Timing',
            'app;dur=' . number_format($durationMs, 2, '.', '')
        );

        if ($durationMs >= config('performance.slow_request_ms')) {
            Log::warning('Solicitud lenta detectada.', [
                'method' => $request->method(),
                'path' => $request->path(),
                'duration_ms' => round($durationMs, 2),
                'user_id' => $request->user()?->id,
            ]);
        }

        return $response;
    }
}
