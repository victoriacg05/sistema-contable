<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // El sistema es en español: forzamos el idioma sin depender del .env.
        App::setLocale('es');

        // Compartimos las alertas de morosidad con el layout principal para que
        // el aviso aparezca en cualquier pantalla del sistema, no solo en el
        // panel de inicio.
        View::composer('layouts.app', function ($view) {
            $morosasCobrar = null;
            $morosasPagar = null;

            try {
                $alertas = Cache::remember('resumen-global-morosidad', 60, function () {
                    $cobrar = Schema::hasTable('cuentas_cobrar')
                        ? DB::table('cuentas_cobrar')
                            ->where('saldo_pendiente', '>', 0)
                            ->where('fecha_vencimiento', '<', now())
                            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(saldo_pendiente), 0) as monto')
                            ->first()
                        : null;
                    $pagar = Schema::hasTable('cuentas_pagar')
                        ? DB::table('cuentas_pagar')
                            ->where('saldo_pendiente', '>', 0)
                            ->where('fecha_vencimiento', '<', now())
                            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(saldo_pendiente), 0) as monto')
                            ->first()
                        : null;

                    return compact('cobrar', 'pagar');
                });

                $morosasCobrar = $alertas['cobrar'];
                $morosasPagar = $alertas['pagar'];
            } catch (\Throwable $e) {
                Log::warning('No se pudo cargar el resumen global de morosidad.', [
                    'exception' => $e,
                ]);
            }

            $view->with('alertaMorosasCobrar', $morosasCobrar)
                ->with('alertaMorosasPagar', $morosasPagar);
        });
    }
}
