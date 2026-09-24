<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public home and access request pages are available without authentication', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/Home')->where('authenticated', false));

    $this->get('/solicitar-acceso')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/RequestAccess'));
});

test('a valid onboarding request is stored for manual provisioning', function () {
    $this->post('/solicitar-acceso', [
        'business_name' => 'Mercado Norte',
        'contact_name' => 'Ana Pérez',
        'email' => 'ANA@EXAMPLE.COM',
        'phone' => '11 5555 5555',
        'store_count' => 3,
        'notes' => 'Necesitamos separar inventario por local.',
    ])->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('onboarding_requests', [
        'business_name' => 'Mercado Norte',
        'contact_name' => 'Ana Pérez',
        'email' => 'ana@example.com',
        'store_count' => 3,
        'status' => 'new',
    ]);
});

test('onboarding request validates required data', function () {
    $this->post('/solicitar-acceso', [
        'business_name' => '',
        'contact_name' => '',
        'email' => 'correo-invalido',
        'store_count' => 0,
    ])->assertSessionHasErrors(['business_name', 'contact_name', 'email', 'store_count']);

    $this->assertDatabaseCount('onboarding_requests', 0);
});
