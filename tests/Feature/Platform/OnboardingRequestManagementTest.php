<?php

use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createOnboardingRequest(): int
{
    return DB::table('onboarding_requests')->insertGetId([
        'business_name' => 'Kiosco Demo',
        'contact_name' => 'Persona Demo',
        'email' => 'demo@example.test',
        'phone' => '',
        'store_count' => 2,
        'notes' => null,
        'status' => 'new',
        'source_ip' => '127.0.0.1',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('platform administrator can review and classify onboarding requests', function () {
    $admin = User::factory()->create(['es_admin' => true, 'is_platform_admin' => true]);
    $requestId = createOnboardingRequest();

    $this->actingAs($admin)
        ->get('/plataforma/solicitudes')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Platform/OnboardingRequests')
            ->has('requests', 1)
            ->where('requests.0.business_name', 'Kiosco Demo'));

    $this->actingAs($admin)
        ->put("/plataforma/solicitudes/{$requestId}", ['status' => 'contacted'])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('onboarding_requests', ['id' => $requestId, 'status' => 'contacted']);
});

test('ordinary account administrator cannot access the platform inbox', function () {
    $admin = User::factory()->create(['es_admin' => true, 'is_platform_admin' => false]);

    $this->actingAs($admin)->get('/plataforma/solicitudes')->assertForbidden();
});
