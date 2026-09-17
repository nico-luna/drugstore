<?php

use App\Domains\Identity\Models\Permission;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function settingsUser(bool $withPerm = true): User
{
    $user = User::factory()->create(['es_admin' => false, 'estado' => true]);
    if ($withPerm) {
        $perm = Permission::firstOrCreate(['nombre' => 'configuracion']);
        $user->permissions()->attach($perm->idpermiso);
    }
    return $user;
}

// ---------------------------------------------------------------------------
// Access control
// ---------------------------------------------------------------------------

test('guest is redirected to login', function () {
    $this->get('/configuracion')->assertRedirect('/login');
});

test('user without configuracion permission gets 403', function () {
    $user = settingsUser(withPerm: false);
    $this->actingAs($user)->get('/configuracion')->assertForbidden();
});

test('user with configuracion permission can view settings', function () {
    $user = settingsUser();
    $this->actingAs($user)->get('/configuracion')->assertOk();
});

// ---------------------------------------------------------------------------
// Read: shows current config values
// ---------------------------------------------------------------------------

test('settings page shows current config row values', function () {
    // The migration seeds id=1 row; update it directly
    DB::table('configuracion')->where('id', 1)->update([
        'nombre'   => 'Farmacia Test',
        'telefono' => '123456',
        'email'    => 'test@example.com',
        'direccion' => 'Av. Siempreviva 742',
    ]);

    $user = settingsUser();
    $response = $this->actingAs($user)->get('/configuracion');
    $response->assertOk();

    $props = $response->original->getData()['page']['props'];
    expect($props['config']['nombre'])->toBe('Farmacia Test')
        ->and($props['config']['email'])->toBe('test@example.com');
});

// ---------------------------------------------------------------------------
// Update: valid data persists
// ---------------------------------------------------------------------------

test('TC-CONF-01: valid update persists all fields and lowercases email', function () {
    $user = settingsUser();
    $response = $this->actingAs($user)->put('/configuracion', [
        'nombre'    => 'Farmacia Nueva',
        'telefono'  => '011-1234',
        'email'     => 'ADMIN@FARMACIA.COM',
        'direccion' => 'Calle Falsa 123',
    ]);

    $response->assertRedirect(route('configuracion.edit'));
    $this->followRedirects($response)->assertSessionHas('success');

    $row = DB::table('configuracion')->where('id', 1)->first();
    expect($row->nombre)->toBe('Farmacia Nueva')
        ->and($row->email)->toBe('admin@farmacia.com')
        ->and($row->telefono)->toBe('011-1234')
        ->and($row->direccion)->toBe('Calle Falsa 123');
});

// ---------------------------------------------------------------------------
// Validation rules
// ---------------------------------------------------------------------------

test('TC-CONF-02: nombre is required', function () {
    $user = settingsUser();
    $this->actingAs($user)
        ->put('/configuracion', ['nombre' => '', 'telefono' => '', 'email' => '', 'direccion' => ''])
        ->assertSessionHasErrors('nombre');
});

test('TC-CONF-03: nombre max 100 chars', function () {
    $user = settingsUser();
    $this->actingAs($user)
        ->put('/configuracion', ['nombre' => str_repeat('a', 101), 'telefono' => '', 'email' => '', 'direccion' => ''])
        ->assertSessionHasErrors('nombre');
});

test('TC-CONF-04: invalid email is rejected', function () {
    $user = settingsUser();
    $this->actingAs($user)
        ->put('/configuracion', ['nombre' => 'Test', 'telefono' => '', 'email' => 'not-an-email', 'direccion' => ''])
        ->assertSessionHasErrors('email');
});