<?php

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('unauthenticated users cannot access users index', function () {
    $response = $this->get('/usuarios');
    $response->assertRedirect('/login');
});

test('users without usuarios permission cannot access users index', function () {
    $user = User::create([
        'nombre' => 'Cajero Test',
        'correo' => 'cajero@test.com',
        'usuario' => 'cajero',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => false,
        'estado' => true,
    ]);

    $response = $this->actingAs($user)->get('/usuarios');
    $response->assertStatus(403);
});

test('admin can access users index and see list', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->get('/usuarios');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Users/Index')
        ->has('users', 1)
        ->has('permissions', 6)
    );
});

test('admin can create a new user with permissions', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $permisoVentas = Permission::where('nombre', 'ventas')->first();

    $response = $this->actingAs($admin)->post('/usuarios', [
        'nombre' => 'Nuevo Empleado',
        'correo' => 'empleado@test.com',
        'usuario' => 'empleado',
        'clave' => 'ClaveSegura123!',
        'es_admin' => false,
        'permisos' => [$permisoVentas->id],
    ]);

    $response->assertRedirect(route('usuarios.index'));
    $this->assertDatabaseHas('usuario', [
        'usuario' => 'empleado',
        'correo' => 'empleado@test.com',
        'es_admin' => false,
    ]);

    $newUser = User::where('usuario', 'empleado')->first();
    expect($newUser->permissions)->toHaveCount(1);
    expect($newUser->hasPermission('ventas'))->toBeTrue();
});

test('TC-PERM-02: system prevents deactivating the last active administrator', function () {
    $admin = User::create([
        'nombre' => 'Unico Admin',
        'correo' => 'unico@admin.com',
        'usuario' => 'unicoadmin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $otherUser = User::create([
        'nombre' => 'Supervisor',
        'correo' => 'supervisor@test.com',
        'usuario' => 'supervisor',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => false,
        'estado' => true,
    ]);
    $permUsuarios = Permission::where('nombre', 'usuarios')->first();
    $otherUser->permissions()->attach($permUsuarios->id);

    // Try to deactivate the single admin from another authorized user
    $response = $this->actingAs($otherUser)->post("/usuarios/{$admin->idusuario}/toggle");
    $response->assertSessionHas('error', 'Solo un administrador puede modificar otra cuenta administradora.');
    expect($admin->fresh()->estado)->toBeTrue();
});

test('user cannot deactivate their own account', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->post("/usuarios/{$admin->idusuario}/toggle");
    $response->assertSessionHas('error', 'No podés desactivar tu propia cuenta.');
    expect($admin->fresh()->estado)->toBeTrue();
});
