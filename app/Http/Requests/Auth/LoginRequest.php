<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Limpiar datos antes de validar
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => trim(strtolower($this->email)),
            'password' => trim($this->password),
        ]);
    }

    /**
     * Reglas de validación
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Intentar autenticación
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            'email' => trim(strtolower($this->email)),
            'password' => trim($this->password),
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Credenciales incorrectas.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Control de fecha de activación: si el usuario tiene una fecha de
        // activación futura, no puede iniciar sesión hasta que se alcance.
        $user = Auth::user();

        if ($user->fecha_activacion && $user->fecha_activacion->isFuture()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Tu cuenta estará disponible a partir del '
                    . $user->fecha_activacion->format('d/m/Y')
                    . '. No podrás iniciar sesión antes de esa fecha.',
            ]);
        }
    }

    /**
     * Control de intentos
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Key para rate limiter
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('email')) . '|' . $this->ip()
        );
    }
}
