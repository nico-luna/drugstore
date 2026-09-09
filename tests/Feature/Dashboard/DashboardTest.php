<?php

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Models\Permission;
use App\Domains\Customers\Models\Customer;
use App\Domains\Catalog\Models\Product;
use App\Domains\Sales\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('unauthenticated users cannot access dashboard', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('admin sees all dashboard metric cards', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    // Create 1 product and 1 sale
    Product::create([
        'codigo' => 'PROD1',
        'descripcion' => 'Producto Demo',
        'precio' => 100.00,
        'existencia' => 10,
        'controla_stock' => true,
        'usuario_id' => $admin->idusuario,
        'estado' => true,
    ]);

    Sale::create([
        'id_cliente' => 1,
        'total' => 200.00,
        'id_usuario' => $admin->idusuario,
        'estado' => 'confirmada',
        'fecha' => now(),
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard/Index')
        ->has('cards', 4)
        ->where('cards.0.label', 'Clientes activos')
        ->where('cards.0.value', '1')
        ->where('cards.1.label', 'Productos activos')
        ->where('cards.1.value', '1')
        ->where('cards.2.label', 'Ventas de hoy')
        ->where('cards.2.value', '1')
        ->where('cards.3.label', 'Total de hoy')
    );
});

test('user with only clientes permission sees only clientes card', function () {
    $user = User::create([
        'nombre' => 'Solo Clientes',
        'correo' => 'clientes@test.com',
        'usuario' => 'soloclientes',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => false,
        'estado' => true,
    ]);
    $perm = Permission::where('nombre', 'clientes')->first();
    $user->permissions()->attach($perm->id);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard/Index')
        ->has('cards', 1)
        ->where('cards.0.label', 'Clientes activos')
    );
});
