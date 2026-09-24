<?php

use App\Domains\Catalog\Models\Product;
use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Account;
use App\Domains\Tenancy\Models\AccountMembership;
use App\Domains\Tenancy\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function organizationUser(string $username, bool $admin): User
{
    return User::create([
        'nombre' => 'Usuario '.$username,
        'correo' => $username.'@example.test',
        'usuario' => $username,
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => $admin,
        'estado' => true,
    ]);
}

test('staff cannot open organization management', function () {
    $staff = organizationUser('organization-staff', false);

    $this->actingAs($staff)->get('/organizacion')->assertForbidden();
});

test('owner can create a store with settings and unavailable inventory', function () {
    $owner = organizationUser('organization-owner', true);
    $product = Product::create([
        'codigo' => 'ORG-PRODUCT',
        'descripcion' => 'Producto organización',
        'precio' => '125.00',
        'existencia' => 8,
        'controla_stock' => true,
        'usuario_id' => $owner->idusuario,
        'estado' => true,
    ]);

    $this->actingAs($owner)
        ->post('/organizacion/tiendas', ['name' => 'Sucursal Centro'])
        ->assertSessionHasNoErrors();

    $store = Store::query()->where('name', 'Sucursal Centro')->firstOrFail();
    $this->assertDatabaseHas('configuracion', ['store_id' => $store->id, 'nombre' => 'Sucursal Centro']);
    $this->assertDatabaseHas('store_inventory', [
        'store_id' => $store->id,
        'product_id' => $product->codproducto,
        'stock' => 0,
        'is_available' => false,
    ]);
});

test('the active store cannot be deactivated', function () {
    $owner = organizationUser('active-store-owner', true);
    $store = Store::query()->firstOrFail();

    $this->actingAs($owner)
        ->post("/organizacion/tiendas/{$store->id}/toggle")
        ->assertSessionHas('error', 'No podés desactivar la tienda activa.');

    expect($store->fresh()->is_active)->toBeTrue();
});

test('the last active owner cannot be demoted', function () {
    $owner = organizationUser('last-owner', true);
    $membership = AccountMembership::query()->where('user_id', $owner->idusuario)->firstOrFail();
    $store = Store::query()->firstOrFail();

    $this->actingAs($owner)
        ->put("/organizacion/miembros/{$membership->id}", [
            'role' => 'staff',
            'is_active' => true,
            'default_store_id' => $store->id,
            'store_ids' => [$store->id],
        ])
        ->assertSessionHasErrors('role');

    expect($membership->fresh()->role)->toBe('owner');
});

test('owner can restrict a staff member to one store', function () {
    $account = Account::query()->firstOrFail();
    $storeA = Store::query()->firstOrFail();
    $storeB = Store::create([
        'account_id' => $account->id,
        'name' => 'Sucursal Este',
        'slug' => 'sucursal-este',
        'is_active' => true,
    ]);
    $owner = organizationUser('access-owner', true);
    $staff = organizationUser('access-staff', false);
    $membership = AccountMembership::query()->where('user_id', $staff->idusuario)->firstOrFail();

    $this->actingAs($owner)
        ->put("/organizacion/miembros/{$membership->id}", [
            'role' => 'staff',
            'is_active' => true,
            'default_store_id' => $storeB->id,
            'store_ids' => [$storeB->id],
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('store_user', ['store_id' => $storeA->id, 'user_id' => $staff->idusuario]);
    $this->assertDatabaseHas('store_user', ['store_id' => $storeB->id, 'user_id' => $staff->idusuario]);
    expect($membership->fresh()->default_store_id)->toBe($storeB->id);
});
