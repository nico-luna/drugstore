<?php

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('authenticated user can view dashboard with app layout and correct navigation', function () {
    $user = User::create([
        'nombre' => 'Cajero Test',
        'correo' => 'cajero@test.com',
        'usuario' => 'cajero',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => false,
        'estado' => true,
    ]);

    // Give only 'ventas' permission
    $permission = Permission::where('nombre', 'ventas')->first();
    $user->permissions()->attach($permission->id);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard/Index')
        ->where('auth.user.nombre', 'Cajero Test')
        ->where('auth.user.es_admin', false)
        ->has('auth.user.permisos', 1)
        ->where('auth.user.permisos.0', 'ventas')
    );
});

test('unauthenticated users are redirected to login', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});
