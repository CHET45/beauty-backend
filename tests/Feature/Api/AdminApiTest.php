<?php

namespace Tests\Feature\Api;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
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

    public function test_admin_can_filter_and_sort_appointments(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create(), ['admin']);

        $haircut = Service::factory()->create(['name' => 'Haircut']);
        $manicure = Service::factory()->create(['name' => 'Manicure']);

        Appointment::factory()->for($haircut)->create([
            'customer_name' => 'Anna Berzina',
            'starts_at' => CarbonImmutable::tomorrow()->setTime(9, 0),
            'ends_at' => CarbonImmutable::tomorrow()->setTime(10, 0),
        ]);
        Appointment::factory()->for($manicure)->create([
            'customer_name' => 'Zane Kalnina',
            'starts_at' => CarbonImmutable::tomorrow()->setTime(14, 0),
            'ends_at' => CarbonImmutable::tomorrow()->setTime(15, 0),
        ]);

        // Filter by customer name.
        $this->getJson('/api/admin/appointments?'.http_build_query(['name' => 'anna']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customer_name', 'Anna Berzina');

        // Filter by service.
        $this->getJson('/api/admin/appointments?'.http_build_query(['service_id' => $manicure->id]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customer_name', 'Zane Kalnina');

        // Sort by name A–Z.
        $this->getJson('/api/admin/appointments?'.http_build_query(['sort' => 'customer_name', 'direction' => 'asc']))
            ->assertOk()
            ->assertJsonPath('data.0.customer_name', 'Anna Berzina')
            ->assertJsonPath('data.1.customer_name', 'Zane Kalnina');

        // Sort latest first.
        $this->getJson('/api/admin/appointments?'.http_build_query(['sort' => 'starts_at', 'direction' => 'desc']))
            ->assertOk()
            ->assertJsonPath('data.0.customer_name', 'Zane Kalnina');
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
