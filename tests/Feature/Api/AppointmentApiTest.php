<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_book_available_slot(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $startsAt = CarbonImmutable::tomorrow()->setTime(10, 0);

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'customer_name' => 'Anna',
            'customer_phone' => '+37111111111',
            'starts_at' => $startsAt->toIso8601String(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.service_id', $service->id)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_customer_cannot_double_book_same_slot(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $startsAt = CarbonImmutable::tomorrow()->setTime(10, 0);

        $payload = [
            'service_id' => $service->id,
            'customer_name' => 'Anna',
            'customer_phone' => '+37111111111',
            'starts_at' => $startsAt->toIso8601String(),
        ];

        $this->postJson('/api/appointments', $payload)->assertCreated();

        $this->postJson('/api/appointments', [
            ...$payload,
            'customer_name' => 'Maria',
            'customer_phone' => '+37122222222',
        ])->assertUnprocessable();
    }

    public function test_available_slots_hide_conflicting_times(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $startsAt = CarbonImmutable::tomorrow()->setTime(10, 0);

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'customer_name' => 'Anna',
            'customer_phone' => '+37111111111',
            'starts_at' => $startsAt->toIso8601String(),
        ])->assertCreated();

        $this->getJson('/api/available-slots?'.http_build_query([
            'service_id' => $service->id,
            'date' => $startsAt->format('Y-m-d'),
        ]))
            ->assertOk()
            ->assertJsonFragment([
                'time' => '10:00',
                'available' => false,
            ]);
    }
}
