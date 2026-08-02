<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users are always redirected to the dashboard after login', function () {
    $user = User::factory()->create();

    $this->get('/clientes')->assertRedirect(route('login'));

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('password whitespace is preserved during authentication', function () {
    $user = User::factory()->create([
        'password' => Hash::make(' password '),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => ' password ',
    ]);

    $this->assertAuthenticated();
});

test('csrf token can be refreshed', function () {
    $response = $this->getJson(route('csrf.refresh'));

    $response
        ->assertOk()
        ->assertJsonStructure(['token', 'authenticated']);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
