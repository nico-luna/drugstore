<?php

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Models\Permission;
use App\Domains\Customers\Models\Customer;
use App\Domains\Catalog\Models\Product;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('TC-SALE-01: valid sale creation with stock discount and server-side price calculation', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $prodWithStock = Product::create([
        'codigo' => 'PROD-STOCK',
        'descripcion' => 'Gaseosa Cola',
        'precio' => 1500.00,
        'existencia' => 10,
        'controla_stock' => true,
        'usuario_id' => $admin->idusuario,
        'estado' => true,
    ]);

    $prodNoStockCtrl = Product::create([
        'codigo' => 'PROD-NO-STOCK',
        'descripcion' => 'Carga Virtual',
        'precio' => 500.00,
        'existencia' => 0,
        'controla_stock' => false,
        'usuario_id' => $admin->idusuario,
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->post('/nueva-venta', [
        'cliente_id' => 1,
        'producto_id' => [$prodWithStock->codproducto, $prodNoStockCtrl->codproducto],
        'cantidad' => [2, 3],
    ]);

    // Expected total = (1500 * 2) + (500 * 3) = 3000 + 1500 = 4500.00
    $sale = Sale::first();
    expect($sale)->not->toBeNull();
    expect((float) $sale->total)->toBe(4500.00);
    expect($sale->id_cliente)->toBe(1);
    expect($sale->id_usuario)->toBe($admin->idusuario);
    expect($sale->estado)->toBe('confirmada');

    // Check detail items
    expect($sale->items)->toHaveCount(2);

    // Check stock decrement
    expect($prodWithStock->fresh()->existencia)->toBe(8);
    expect($prodNoStockCtrl->fresh()->existencia)->toBe(0);

    $response->assertRedirect("/ventas?view={$sale->id}");
});

test('TC-SALE-02: insufficient stock aborts sale and rolls back completely', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $product = Product::create([
        'codigo' => 'PROD-LOW',
        'descripcion' => 'Chocolatina',
        'precio' => 800.00,
        'existencia' => 2,
        'controla_stock' => true,
        'usuario_id' => $admin->idusuario,
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->post('/nueva-venta', [
        'cliente_id' => 1,
        'producto_id' => [$product->codproducto],
        'cantidad' => [5],
    ]);

    $response->assertSessionHas('error', 'Stock insuficiente para Chocolatina.');

    // Invariant: no sale created and stock untouched
    expect(Sale::count())->toBe(0);
    expect(SaleItem::count())->toBe(0);
    expect($product->fresh()->existencia)->toBe(2);
});

test('TC-SALE-03: inactive product is rejected and rolls back sale', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $product = Product::create([
        'codigo' => 'INACTIVO',
        'descripcion' => 'Producto Inactivo',
        'precio' => 1000.00,
        'existencia' => 10,
        'controla_stock' => true,
        'usuario_id' => $admin->idusuario,
        'estado' => false,
    ]);

    $response = $this->actingAs($admin)->post('/nueva-venta', [
        'cliente_id' => 1,
        'producto_id' => [$product->codproducto],
        'cantidad' => [1],
    ]);

    $response->assertSessionHas('error', 'Uno de los productos ya no está disponible.');
    expect(Sale::count())->toBe(0);
});

test('TC-SALE-04: inactive client is rejected and rolls back sale', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $customer = Customer::create([
        'nombre' => 'Cliente Inactivo',
        'telefono' => '',
        'direccion' => '',
        'usuario_id' => $admin->idusuario,
        'estado' => false,
    ]);

    $product = Product::create([
        'codigo' => 'PROD1',
        'descripcion' => 'Alfajor',
        'precio' => 1000.00,
        'existencia' => 10,
        'controla_stock' => true,
        'usuario_id' => $admin->idusuario,
        'estado' => true,
    ]);

    $response = $this->actingAs($admin)->post('/nueva-venta', [
        'cliente_id' => $customer->idcliente,
        'producto_id' => [$product->codproducto],
        'cantidad' => [1],
    ]);

    $response->assertSessionHas('error', 'El cliente seleccionado no está disponible.');
    expect(Sale::count())->toBe(0);
});

test('TC-SALE-05: duplicate lines for same product are consolidated properly', function () {
    $admin = User::create([
        'nombre' => 'Admin Test',
        'correo' => 'admin@test.com',
        'usuario' => 'admin',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $product = Product::create([
        'codigo' => 'AGUA',
        'descripcion' => 'Agua 500ml',
        'precio' => 500.00,
        'existencia' => 10,
        'controla_stock' => true,
        'usuario_id' => $admin->idusuario,
        'estado' => true,
    ]);

    // Send product twice in separate lines: qty 2 and qty 3 (total 5)
    $response = $this->actingAs($admin)->post('/nueva-venta', [
        'cliente_id' => 1,
        'producto_id' => [$product->codproducto, $product->codproducto],
        'cantidad' => [2, 3],
    ]);

    $sale = Sale::first();
    expect($sale)->not->toBeNull();
    expect((float) $sale->total)->toBe(2500.00);
    expect($product->fresh()->existencia)->toBe(5);
});
