<?php

use Illuminate\Support\Facades\Validator;

it('always uses Spanish for validation messages', function () {
    expect(config('app.locale'))->toBe('es')
        ->and(config('app.fallback_locale'))->toBe('es');

    $validator = Validator::make([], [
        'email' => ['required', 'email'],
    ]);

    expect($validator->errors()->first('email'))
        ->toBe('El campo correo electrónico es obligatorio.');
});
