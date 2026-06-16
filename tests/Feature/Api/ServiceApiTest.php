<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_service_index_returns_only_active_services(): void
    {
        Service::factory()->create(['name' => 'Inactive', 'is_active' => false]);
        $active = Service::factory()->create(['name' => 'Active', 'is_active' => true]);

        $this->getJson('/api/services')
            ->assertOk()
            ->assertJsonPath('data.0.id', $active->id)
            ->assertJsonMissing(['name' => 'Inactive']);
    }

    public function test_public_service_write_routes_are_not_exposed(): void
    {
        $this->postJson('/api/services', [
            'name' => 'Unsafe public create',
            'duration_minutes' => 60,
            'price' => 50,
            'is_active' => true,
        ])->assertMethodNotAllowed();
    }
}
