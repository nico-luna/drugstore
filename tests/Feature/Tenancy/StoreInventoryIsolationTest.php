<?php

use App\Domains\Catalog\Models\Product;
use App\Domains\Catalog\Models\StoreInventory;
use App\Domains\Identity\Models\User;
use App\Domains\Sales\Models\Sale;
use App\Domains\Tenancy\Models\Account;
use App\Domains\Tenancy\Models\Store;
use App\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('a sale uses price and stock from the active store only', function () {
    $account = Account::query()->firstOrFail();
    $storeA = Store::query()->where('account_id', $account->id)->firstOrFail();
    $storeB = Store::create([
        'account_id' => $account->id,
        'name' => 'Sucursal Norte',
        'slug' => 'sucursal-norte',
        'is_active' => true,
    ]);

    app(CurrentTenant::class)->initialize($account, $storeA);
    $user = User::create([
        'nombre' => 'Dueño Inventario',
        'correo' => 'inventario@example.test',
        'usuario' => 'inventario-owner',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);

    $product = Product::create([
        'codigo' => 'PRECIO-TIENDA',
        'descripcion' => 'Producto con precio local',
        'precio' => '100.00',
        'existencia' => 10,
        'controla_stock' => true,
        'usuario_id' => $user->idusuario,
        'estado' => true,
    ]);

    StoreInventory::withoutGlobalScopes()->create([
        'account_id' => $account->id,
        'store_id' => $storeB->id,
        'product_id' => $product->codproducto,
        'price' => '250.00',
        'stock' => 4,
        'is_available' => true,
    ]);

    $this->actingAs($user)
        ->withSession(['tenant.account_id' => $account->id, 'tenant.store_id' => $storeB->id])
        ->post('/nueva-venta', [
            'cliente_id' => 1,
            'producto_id' => [$product->codproducto],
            'cantidad' => [2],
        ])
        ->assertSessionHasNoErrors();

    $sale = Sale::withoutGlobalScopes()->firstOrFail();
    expect((float) $sale->total)->toBe(500.0)
        ->and($sale->store_id)->toBe($storeB->id)
        ->and(StoreInventory::withoutGlobalScopes()->where('store_id', $storeA->id)->where('product_id', $product->codproducto)->value('stock'))->toBe(10)
        ->and(StoreInventory::withoutGlobalScopes()->where('store_id', $storeB->id)->where('product_id', $product->codproducto)->value('stock'))->toBe(2);
});

test('a product unavailable in the active store cannot be sold there', function () {
    $account = Account::query()->firstOrFail();
    $storeA = Store::query()->where('account_id', $account->id)->firstOrFail();
    $storeB = Store::create([
        'account_id' => $account->id,
        'name' => 'Sucursal Oeste',
        'slug' => 'sucursal-oeste',
        'is_active' => true,
    ]);

    app(CurrentTenant::class)->initialize($account, $storeA);
    $user = User::create([
        'nombre' => 'Dueño Disponibilidad',
        'correo' => 'disponibilidad@example.test',
        'usuario' => 'availability-owner',
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);
    $product = Product::create([
        'codigo' => 'SOLO-CENTRAL',
        'descripcion' => 'Producto sólo central',
        'precio' => '100.00',
        'existencia' => 10,
        'controla_stock' => true,
        'usuario_id' => $user->idusuario,
        'estado' => true,
    ]);

    StoreInventory::withoutGlobalScopes()->create([
        'account_id' => $account->id,
        'store_id' => $storeB->id,
        'product_id' => $product->codproducto,
        'price' => '100.00',
        'stock' => 10,
        'is_available' => false,
    ]);

    $this->actingAs($user)
        ->withSession(['tenant.account_id' => $account->id, 'tenant.store_id' => $storeB->id])
        ->post('/nueva-venta', [
            'cliente_id' => 1,
            'producto_id' => [$product->codproducto],
            'cantidad' => [1],
        ])
        ->assertSessionHas('error', 'Uno de los productos no está disponible en esta tienda.');

    expect(Sale::withoutGlobalScopes()->count())->toBe(0);
});
