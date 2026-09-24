<?php

use App\Domains\Identity\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('password recovery request does not reveal whether an email exists', function () {
    Notification::fake();
    $user = User::factory()->create(['correo' => 'known@example.test']);

    $this->post('/olvide-mi-clave', ['correo' => 'known@example.test'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');
    $this->post('/olvide-mi-clave', ['correo' => 'unknown@example.test'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('user can reset their password with a valid token', function () {
    $user = User::factory()->create(['correo' => 'reset@example.test']);
    $token = Password::broker()->createToken($user);

    $this->post('/restablecer-clave', [
        'token' => $token,
        'correo' => 'reset@example.test',
        'password' => 'NuevaClaveSegura9!',
        'password_confirmation' => 'NuevaClaveSegura9!',
    ])->assertRedirect(route('login'))
        ->assertSessionHas('success');

    expect(Hash::check('NuevaClaveSegura9!', $user->fresh()->clave))->toBeTrue();
});

test('password reset rejects weak passwords', function () {
    $user = User::factory()->create(['correo' => 'weak@example.test']);
    $token = Password::broker()->createToken($user);

    $this->post('/restablecer-clave', [
        'token' => $token,
        'correo' => 'weak@example.test',
        'password' => 'corta',
        'password_confirmation' => 'corta',
    ])->assertSessionHasErrors('password');
});
