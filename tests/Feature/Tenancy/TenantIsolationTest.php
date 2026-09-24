<?php

use App\Domains\Catalog\Models\Product;
use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Account;
use App\Domains\Tenancy\Models\AccountMembership;
use App\Domains\Tenancy\Models\Store;
use App\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function tenancyUser(string $username, Account $account, Store $store): User
{
    app(CurrentTenant::class)->initialize($account, $store);

    return User::create([
        'nombre' => "Administrador {$username}",
        'correo' => "{$username}@example.test",
        'usuario' => $username,
        'clave' => Hash::make('ClaveSegura123!'),
        'es_admin' => true,
        'estado' => true,
    ]);
}

test('products are isolated by account even when their codes match', function () {
    $accountA = Account::query()->firstOrFail();
    $storeA = Store::query()->where('account_id', $accountA->id)->firstOrFail();
    $userA = tenancyUser('admin-a', $accountA, $storeA);

    Product::create([
        'codigo' => 'CODIGO-COMPARTIDO',
        'descripcion' => 'Producto cuenta A',
        'precio' => '100.00',
        'existencia' => 10,
        'controla_stock' => true,
        'usuario_id' => $userA->idusuario,
        'estado' => true,
    ]);

    $accountB = Account::create(['name' => 'Cuenta B', 'slug' => 'cuenta-b', 'is_active' => true]);
    $storeB = Store::create(['account_id' => $accountB->id, 'name' => 'Sucursal B', 'slug' => 'sucursal-b', 'is_active' => true]);
    $userB = tenancyUser('admin-b', $accountB, $storeB);

    Product::create([
        'codigo' => 'CODIGO-COMPARTIDO',
        'descripcion' => 'Producto cuenta B',
        'precio' => '200.00',
        'existencia' => 20,
        'controla_stock' => true,
        'usuario_id' => $userB->idusuario,
        'estado' => true,
    ]);

    $this->actingAs($userA)
        ->withSession(['tenant.account_id' => $accountA->id, 'tenant.store_id' => $storeA->id])
        ->get('/productos')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('products', 1)
            ->where('products.0.descripcion', 'Producto cuenta A')
            ->where('tenant.account.id', $accountA->id)
            ->where('tenant.store.id', $storeA->id));
});

test('a user cannot switch to an account without an active membership', function () {
    $accountA = Account::query()->firstOrFail();
    $storeA = Store::query()->where('account_id', $accountA->id)->firstOrFail();
    $userA = tenancyUser('admin-a', $accountA, $storeA);

    $accountB = Account::create(['name' => 'Cuenta B', 'slug' => 'cuenta-b', 'is_active' => true]);
    $storeB = Store::create(['account_id' => $accountB->id, 'name' => 'Sucursal B', 'slug' => 'sucursal-b', 'is_active' => true]);

    $this->actingAs($userA)
        ->withSession(['tenant.account_id' => $accountA->id, 'tenant.store_id' => $storeA->id])
        ->post('/contexto', ['account_id' => $accountB->id, 'store_id' => $storeB->id])
        ->assertSessionHasErrors(['account_id']);

    expect(session('tenant.account_id'))->toBe($accountA->id)
        ->and(session('tenant.store_id'))->toBe($storeA->id);
});

test('an account owner can switch between active stores of the same account', function () {
    $account = Account::query()->firstOrFail();
    $storeA = Store::query()->where('account_id', $account->id)->firstOrFail();
    $storeB = Store::create([
        'account_id' => $account->id,
        'name' => 'Sucursal Norte',
        'slug' => 'sucursal-norte',
        'is_active' => true,
    ]);
    $user = tenancyUser('owner', $account, $storeA);

    $this->actingAs($user)
        ->withSession(['tenant.account_id' => $account->id, 'tenant.store_id' => $storeA->id])
        ->post('/contexto', ['account_id' => $account->id, 'store_id' => $storeB->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('tenant.store_id', $storeB->id);
});

test('a global legacy admin does not gain admin access in an account where they are staff', function () {
    $accountA = Account::query()->firstOrFail();
    $storeA = Store::query()->where('account_id', $accountA->id)->firstOrFail();
    $user = tenancyUser('legacy-admin', $accountA, $storeA);

    $accountB = Account::create(['name' => 'Cuenta B', 'slug' => 'cuenta-b', 'is_active' => true]);
    $storeB = Store::create(['account_id' => $accountB->id, 'name' => 'Sucursal B', 'slug' => 'sucursal-b', 'is_active' => true]);

    AccountMembership::create([
        'account_id' => $accountB->id,
        'user_id' => $user->idusuario,
        'default_store_id' => $storeB->id,
        'role' => 'staff',
        'is_active' => true,
    ]);
    $user->stores()->attach($storeB->id);

    $this->actingAs($user)
        ->withSession(['tenant.account_id' => $accountB->id, 'tenant.store_id' => $storeB->id])
        ->get('/configuracion')
        ->assertForbidden();
});

test('product code uniqueness is enforced inside each account instead of globally', function () {
    $accountA = Account::query()->firstOrFail();
    $storeA = Store::query()->where('account_id', $accountA->id)->firstOrFail();
    $userA = tenancyUser('admin-a', $accountA, $storeA);

    Product::create([
        'codigo' => 'MISMO-CODIGO',
        'descripcion' => 'Producto cuenta A',
        'precio' => '100.00',
        'existencia' => 10,
        'controla_stock' => true,
        'usuario_id' => $userA->idusuario,
        'estado' => true,
    ]);

    $accountB = Account::create(['name' => 'Cuenta B', 'slug' => 'cuenta-b', 'is_active' => true]);
    $storeB = Store::create(['account_id' => $accountB->id, 'name' => 'Sucursal B', 'slug' => 'sucursal-b', 'is_active' => true]);
    $userB = tenancyUser('admin-b', $accountB, $storeB);

    $this->actingAs($userB)
        ->withSession(['tenant.account_id' => $accountB->id, 'tenant.store_id' => $storeB->id])
        ->post('/productos', [
            'codigo' => 'MISMO-CODIGO',
            'descripcion' => 'Producto cuenta B',
            'precio' => '200.00',
            'existencia' => 20,
            'controla_stock' => true,
        ])
        ->assertSessionHasNoErrors();

    expect(Product::withoutGlobalScopes()->where('codigo', 'MISMO-CODIGO')->count())->toBe(2);
});

test('settings can be created independently for a second store', function () {
    $account = Account::query()->firstOrFail();
    $storeA = Store::query()->where('account_id', $account->id)->firstOrFail();
    $storeB = Store::create([
        'account_id' => $account->id,
        'name' => 'Sucursal Sur',
        'slug' => 'sucursal-sur',
        'is_active' => true,
    ]);
    $user = tenancyUser('settings-owner', $account, $storeA);

    $this->actingAs($user)
        ->withSession(['tenant.account_id' => $account->id, 'tenant.store_id' => $storeB->id])
        ->put('/configuracion', [
            'nombre' => 'Sucursal Sur',
            'telefono' => '1234',
            'email' => 'sur@example.test',
            'direccion' => 'Calle Sur 123',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('configuracion', [
        'account_id' => $account->id,
        'store_id' => $storeB->id,
        'nombre' => 'Sucursal Sur',
    ]);
    expect(\Illuminate\Support\Facades\DB::table('configuracion')->where('account_id', $account->id)->count())->toBe(2);
});
