<?php

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Models\Permission;
use App\Domains\Customers\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('unauthenticated users cannot access customers index', function () {
    $response = $this->get('/clientes');
    $response->assertRedirect('/login');
});

test('users without clientes permission cannot access customers index', function () {
    $user = User::create([
        'nombre' => 'Cajero Test',
        'correo' => 'cajero@test.com',
        'usuario' => 'cajero',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => false,
        'estado' => true,
    ]);

    $response = $this->actingAs($user)->get('/clientes');
    $response->assertStatus(403);
});

test('authorized user can access customers index and see default customer', function () {
    $user = User::create([
        'nombre' => 'Cajero Clientes',
        'correo' => 'cajero_cli@test.com',
        'usuario' => 'cajerocli',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => false,
        'estado' => true,
    ]);
    $permClientes = Permission::where('nombre', 'clientes')->first();
    $user->permissions()->attach($permClientes->id);

    $response = $this->actingAs($user)->get('/clientes');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Customers/Index')
        ->has('customers', 1)
        ->where('customers.0.nombre', 'Público en general')
    );
});

test('user can create a new customer', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->post('/clientes', [
        'nombre' => 'Juan Perez',
        'telefono' => '1122334455',
        'direccion' => 'Av. Siempre Viva 123',
    ]);

    $response->assertRedirect(route('clientes.index'));
    $this->assertDatabaseHas('cliente', [
        'nombre' => 'Juan Perez',
        'telefono' => '1122334455',
        'direccion' => 'Av. Siempre Viva 123',
        'usuario_id' => $admin->idusuario,
        'estado' => true,
    ]);
});

test('user can update an existing customer', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $customer = Customer::create([
        'nombre' => 'Maria Gomez',
        'telefono' => '99887766',
        'direccion' => 'Calle 1',
        'usuario_id' => $admin->idusuario,
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->put("/clientes/{$customer->idcliente}", [
        'nombre' => 'Maria Gomez Editada',
        'telefono' => '99887700',
        'direccion' => 'Calle 2',
    ]);

    $response->assertRedirect(route('clientes.index'));
    $this->assertDatabaseHas('cliente', [
        'idcliente' => $customer->idcliente,
        'nombre' => 'Maria Gomez Editada',
        'telefono' => '99887700',
        'direccion' => 'Calle 2',
    ]);
});

test('TC-PERM-03: default customer 1 cannot be deactivated', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->post('/clientes/1/toggle');
    $response->assertSessionHas('error', 'El cliente Público en general no puede desactivarse.');

    $defaultCustomer = Customer::find(1);
    expect($defaultCustomer->estado)->toBeTrue();
});

test('user can toggle active status of non-default customer', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $customer = Customer::create([
        'nombre' => 'Carlos Lopez',
        'telefono' => '55554444',
        'direccion' => 'Calle 3',
        'usuario_id' => $admin->idusuario,
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->post("/clientes/{$customer->idcliente}/toggle");
    $response->assertSessionHas('success', 'Estado del cliente actualizado.');
    expect($customer->fresh()->estado)->toBeFalse();
});
