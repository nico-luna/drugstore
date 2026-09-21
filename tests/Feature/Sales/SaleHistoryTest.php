<?php

use App\Domains\Catalog\Models\Product;
use App\Domains\Customers\Models\Customer;
use App\Domains\Identity\Models\Permission;
use App\Domains\Identity\Models\User;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function histUser(bool $ventas = true, bool $nuevaVenta = false): User
{
    $user = User::factory()->create(['es_admin' => false, 'estado' => true]);
    if ($ventas) {
        $perm = Permission::firstOrCreate(['nombre' => 'ventas']);
        $user->permissions()->attach($perm->id);
    }
    if ($nuevaVenta) {
        $perm = Permission::firstOrCreate(['nombre' => 'nueva_venta']);
        $user->permissions()->attach($perm->id);
    }
    return $user;
}

function makeSale(User $seller, string $estado = 'confirmada', bool $stock = true): array
{
    $customer = Customer::factory()->create(['estado' => true]);
    $product  = Product::factory()->create([
        'controla_stock' => $stock,
        'existencia'     => 10,
        'precio'         => '100.00',
        'estado'         => true,
    ]);
    $sale = Sale::create([
        'id_cliente' => $customer->idcliente,
        'id_usuario' => $seller->idusuario,
        'total'      => '100.00',
        'estado'     => $estado,
        'fecha'      => now(),
    ]);
    SaleItem::create([
        'id_venta'    => $sale->id,
        'id_producto' => $product->codproducto,
        'cantidad'    => 1,
        'precio'      => '100.00',
        'subtotal'    => '100.00',
    ]);
    return [$sale, $product];
}

// ---------------------------------------------------------------------------
// Access control
// ---------------------------------------------------------------------------

test('guest is redirected to login', function () {
    $this->get('/ventas')->assertRedirect('/login');
});

test('user without ventas permission gets 403', function () {
    $user = histUser(ventas: false);
    $this->actingAs($user)->get('/ventas')->assertForbidden();
});

test('user with ventas permission can access history', function () {
    $user = histUser();
    $this->actingAs($user)->get('/ventas')->assertOk();
});

// ---------------------------------------------------------------------------
// TC-CANCEL-01: successful cancellation restores stock
// ---------------------------------------------------------------------------

test('TC-CANCEL-01: cancelling a confirmed sale restores stock and marks anulada', function () {
    $seller    = histUser();
    $canceller = histUser();

    [$sale, $product] = makeSale($seller, stock: true);
    // Simulate stock already decremented by the sale
    $product->decrement('existencia', 1);
    $stockBefore = (int) $product->fresh()->existencia;

    $this->actingAs($canceller)
        ->post("/ventas/{$sale->id}/cancel")
        ->assertRedirect();

    expect($sale->fresh()->estado)->toBe('anulada')
        ->and($sale->fresh()->anulada_por)->toBe($canceller->idusuario)
        ->and((int) $product->fresh()->existencia)->toBe($stockBefore + 1);
});

// ---------------------------------------------------------------------------
// TC-CANCEL-02: cancelling already-cancelled sale returns error flash
// ---------------------------------------------------------------------------

test('TC-CANCEL-02: cancelling an already-cancelled sale flashes error', function () {
    $user = histUser();
    [$sale] = makeSale($user, estado: 'anulada');

    $response = $this->actingAs($user)->post("/ventas/{$sale->id}/cancel");
    $response->assertRedirect()->assertSessionHas('error');
});

// ---------------------------------------------------------------------------
// Date filter
// ---------------------------------------------------------------------------

test('date filter excludes sales outside range', function () {
    $user = histUser();

    Sale::create(['id_cliente' => 1, 'id_usuario' => $user->idusuario, 'total' => '50.00', 'estado' => 'confirmada', 'fecha' => now()]);
    Sale::create(['id_cliente' => 1, 'id_usuario' => $user->idusuario, 'total' => '75.00', 'estado' => 'confirmada', 'fecha' => now()->subMonths(2)]);

    $desde = now()->startOfMonth()->format('Y-m-d');
    $hasta = now()->format('Y-m-d');

    $response = $this->actingAs($user)->get("/ventas?desde={$desde}&hasta={$hasta}");
    $response->assertOk();

    $props = $response->original->getData()['page']['props'];
    expect(count($props['sales']))->toBe(1)
        ->and((float) $props['sales'][0]['total'])->toBe(50.0);
});

// ---------------------------------------------------------------------------
// Detail view via ?view=
// ---------------------------------------------------------------------------

test('detail view returns sale data when view param is provided', function () {
    $user = histUser();
    [$sale] = makeSale($user);

    $response = $this->actingAs($user)->get("/ventas?view={$sale->id}");
    $response->assertOk();

    $props = $response->original->getData()['page']['props'];
    expect($props['detail'])->not->toBeNull()
        ->and((int) $props['detail']['id'])->toBe($sale->id);
});
