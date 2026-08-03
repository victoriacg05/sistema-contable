<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            config([
                'cache.default' => 'file',
                'session.driver' => 'file',
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // El sistema es en español: forzamos el idioma sin depender del .env.
        App::setLocale('es');

        if (config('performance.log_slow_queries')) {
            DB::listen(function ($query) {
                if ($query->time >= config('performance.slow_query_ms')) {
                    Log::warning('Consulta lenta detectada.', [
                        'sql' => $query->sql,
                        'time_ms' => $query->time,
                        'connection' => $query->connectionName,
                    ]);
                }
            });
        }

        View::composer('layouts.app', function ($view) {
            $alertas = Cache::remember(
                'resumen-global-morosidad',
                now()->addSeconds(config('performance.morosidad_cache_seconds')),
                function () {
                    return [
                        'cobrar' => DB::table('cuentas_cobrar')
                            ->where('saldo_pendiente', '>', 0)
                            ->where('fecha_vencimiento', '<', now())
                            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(saldo_pendiente), 0) as monto')
                            ->first(),
                        'pagar' => DB::table('cuentas_pagar')
                            ->where('saldo_pendiente', '>', 0)
                            ->where('fecha_vencimiento', '<', now())
                            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(saldo_pendiente), 0) as monto')
                            ->first(),
                    ];
                }
            );

            $view->with('alertaMorosasCobrar', $alertas['cobrar'])
                ->with('alertaMorosasPagar', $alertas['pagar']);
        });
    }
}
