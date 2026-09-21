<?php

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Models\Permission;
use App\Domains\Catalog\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('unauthenticated users cannot access products index', function () {
    $response = $this->get('/productos');
    $response->assertRedirect('/login');
});

test('users without productos permission cannot access products index', function () {
    $user = User::create([
        'nombre' => 'Cajero Test',
        'correo' => 'cajero@test.com',
        'usuario' => 'cajero',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => false,
        'estado' => true,
    ]);

    $response = $this->actingAs($user)->get('/productos');
    $response->assertStatus(403);
});

test('authorized user can access products index and see list', function () {
    $user = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    Product::create([
        'codigo' => '7791234567890',
        'descripcion' => 'Coca Cola 2.25L',
        'precio' => 3000.00,
        'existencia' => 15,
        'controla_stock' => true,
        'usuario_id' => $user->idusuario,
        'estado' => true,
    ]);

    $response = $this->actingAs($user)->get('/productos');
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Products/Index')
        ->has('products', 1)
        ->where('products.0.codigo', '7791234567890')
    );
});

test('user can create a new product', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->post('/productos', [
        'codigo' => '7790001112223',
        'descripcion' => 'Alfajor Triple',
        'precio' => 1200.50,
        'existencia' => 50,
        'controla_stock' => true,
    ]);

    $response->assertRedirect(route('productos.index'));
    $this->assertDatabaseHas('producto', [
        'codigo' => '7790001112223',
        'descripcion' => 'Alfajor Triple',
        'precio' => 1200.50,
        'existencia' => 50,
        'controla_stock' => true,
        'usuario_id' => $admin->idusuario,
        'estado' => true,
    ]);
});

test('duplicate product code is rejected', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    Product::create([
        'codigo' => 'CODIGO123',
        'descripcion' => 'Producto 1',
        'precio' => 500,
        'existencia' => 10,
        'controla_stock' => true,
        'usuario_id' => $admin->idusuario,
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->post('/productos', [
        'codigo' => 'CODIGO123',
        'descripcion' => 'Producto 2',
        'precio' => 600,
        'existencia' => 5,
        'controla_stock' => true,
    ]);

    $response->assertSessionHasErrors(['codigo' => 'Ya existe un producto con ese código.']);
});

test('user can update an existing product', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $product = Product::create([
        'codigo' => 'AGUA500',
        'descripcion' => 'Agua Mineral 500ml',
        'precio' => 800,
        'existencia' => 20,
        'controla_stock' => true,
        'usuario_id' => $admin->idusuario,
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->put("/productos/{$product->codproducto}", [
        'codigo' => 'AGUA500',
        'descripcion' => 'Agua Mineral 500ml Sin Gas',
        'precio' => 850.00,
        'existencia' => 25,
        'controla_stock' => true,
    ]);

    $response->assertRedirect(route('productos.index'));
    $this->assertDatabaseHas('producto', [
        'codproducto' => $product->codproducto,
        'descripcion' => 'Agua Mineral 500ml Sin Gas',
        'precio' => 850.00,
        'existencia' => 25,
    ]);
});

test('user can toggle active status of product', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $product = Product::create([
        'codigo' => 'CHICLE',
        'descripcion' => 'Chicle Menta',
        'precio' => 200,
        'existencia' => 100,
        'controla_stock' => true,
        'usuario_id' => $admin->idusuario,
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->post("/productos/{$product->codproducto}/toggle");
    $response->assertSessionHas('success', 'Estado del producto actualizado.');
    expect($product->fresh()->estado)->toBeFalse();
});
