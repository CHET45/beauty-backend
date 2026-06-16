<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_services(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create(), ['admin']);

        $createdId = $this->postJson('/api/admin/services', [
            'name' => 'Haircut',
            'duration_minutes' => 60,
            'price' => 45,
            'is_active' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Haircut')
            ->json('data.id');

        $this->putJson("/api/admin/services/{$createdId}", [
            'name' => 'Premium Haircut',
            'duration_minutes' => 75,
            'price' => 60,
            'is_active' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Premium Haircut')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_non_admin_user_cannot_access_admin_routes(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['admin']);

        $this->getJson('/api/admin/appointments')
            ->assertForbidden();
    }

    public function test_service_with_appointments_cannot_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create(), ['admin']);

        $service = Service::factory()->create();
        $service->appointments()->create([
            'customer_name' => 'Client',
            'customer_phone' => '+37111111111',
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
            'status' => 'pending',
        ]);

        $this->deleteJson("/api/admin/services/{$service->id}")
            ->assertConflict();
    }
}
