<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();

            $request->session()->regenerate();

            $this->registrarIntentoAccesoAlTerminar(
                $request->email,
                $request->ip(),
                true,
                'Inicio de sesión exitoso'
            );

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->registrarIntentoAccesoAlTerminar(
                $request->email,
                $request->ip(),
                false,
                'Credenciales incorrectas'
            );

            throw $e;
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function registrarIntentoAccesoAlTerminar(
        string $email,
        string $ip,
        bool $exitoso,
        string $mensaje
    ): void {
        app()->terminating(function () use ($email, $ip, $exitoso, $mensaje) {
            BitacoraService::registrarIntentoAcceso(
                $email,
                $ip,
                $exitoso,
                $mensaje
            );
        });
    }
}
