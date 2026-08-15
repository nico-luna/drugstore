<?php

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Services\AuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('TC-AUTH-01: login screen can be rendered', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
});

test('TC-AUTH-01: users can authenticate using the login screen', function () {
    $user = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $response = $this->post('/login', [
        'usuario' => 'admin',
        'clave' => 'ClaveSegura123!',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard'));
});

test('TC-AUTH-02: users cannot authenticate with invalid password', function () {
    User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $response = $this->from('/login')->post('/login', [
        'usuario' => 'admin',
        'clave' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['usuario' => 'Usuario o contraseña incorrectos.']);

    // Check failed attempt recorded
    $this->assertDatabaseCount('intentos_login', 1);
});

test('TC-AUTH-03: account or ip is locked out after 5 consecutive failed attempts', function () {
    User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->from('/login')->post('/login', [
            'usuario' => 'admin',
            'clave' => 'wrong-password',
        ]);
    }

    // 6th attempt should return lockout message
    $response = $this->from('/login')->post('/login', [
        'usuario' => 'admin',
        'clave' => 'ClaveSegura123!',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'usuario' => 'Demasiados intentos. Esperá 15 minutos antes de volver a probar.',
    ]);
});

test('TC-AUTH-05: password complexity validation rejects short passwords', function () {
    expect(AuthenticationService::validatePasswordStrength('corta'))->not->toBeNull();
});

test('TC-AUTH-06: password complexity validation rejects simple passwords', function () {
    expect(AuthenticationService::validatePasswordStrength('largaperosinreglas'))->not->toBeNull();
    expect(AuthenticationService::validatePasswordStrength('ClaveLocal9!Segura'))->toBeNull();
});

test('TC-AUTH-01: authenticated users can log out', function () {
    $user = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/login');
});
