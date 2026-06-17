<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_customer_can_book_available_slot(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $startsAt = CarbonImmutable::tomorrow()->setTime(10, 0);

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'customer_name' => 'Anna',
            'phone_country_code' => '+371',
            'customer_phone' => '11111111',
            'starts_at' => $startsAt->toIso8601String(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.service_id', $service->id)
            ->assertJsonPath('data.phone_country_code', '+371')
            ->assertJsonPath('data.customer_phone', '11111111')
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_customer_cannot_double_book_same_slot(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $startsAt = CarbonImmutable::tomorrow()->setTime(10, 0);

        $payload = [
            'service_id' => $service->id,
            'customer_name' => 'Anna',
            'phone_country_code' => '+371',
            'customer_phone' => '11111111',
            'starts_at' => $startsAt->toIso8601String(),
        ];

        $this->postJson('/api/appointments', $payload)->assertCreated();

        $this->postJson('/api/appointments', [
            ...$payload,
            'customer_name' => 'Maria',
            'customer_phone' => '22222222',
        ])->assertUnprocessable();
    }

    public function test_customer_can_book_with_optional_email_and_notes(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $startsAt = CarbonImmutable::tomorrow()->setTime(10, 0);

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'customer_name' => 'Anna',
            'phone_country_code' => '+371',
            'customer_phone' => '11111111',
            'customer_email' => 'anna@example.com',
            'starts_at' => $startsAt->toIso8601String(),
            'notes' => 'Please call before.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.customer_email', 'anna@example.com')
            ->assertJsonPath('data.notes', 'Please call before.');
    }

    public function test_booking_requires_a_country_code(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'customer_name' => 'Anna',
            'customer_phone' => '11111111',
            'starts_at' => CarbonImmutable::tomorrow()->setTime(10, 0)->toIso8601String(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('phone_country_code');
    }

    public function test_customer_can_look_up_their_appointments_by_phone(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'customer_name' => 'Anna',
            'phone_country_code' => '+371',
            'customer_phone' => '11111111',
            'starts_at' => CarbonImmutable::tomorrow()->setTime(10, 0)->toIso8601String(),
        ])->assertCreated();

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'customer_name' => 'Someone Else',
            'phone_country_code' => '+371',
            'customer_phone' => '99999999',
            'starts_at' => CarbonImmutable::tomorrow()->setTime(12, 0)->toIso8601String(),
        ])->assertCreated();

        $this->getJson('/api/appointments?'.http_build_query([
            'phone' => '11111111',
            'phone_country_code' => '+371',
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customer_phone', '11111111');
    }

    public function test_appointment_lookup_requires_a_phone(): void
    {
        $this->getJson('/api/appointments')
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('phone');
    }

    public function test_available_slots_hide_conflicting_times(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $startsAt = CarbonImmutable::tomorrow()->setTime(10, 0);

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'customer_name' => 'Anna',
            'phone_country_code' => '+371',
            'customer_phone' => '11111111',
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

    public function test_available_slots_hide_times_within_the_lead_window(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::today()->setTime(10, 5));
        $service = Service::factory()->create(['duration_minutes' => 60]);

        $response = $this->getJson('/api/available-slots?'.http_build_query([
            'service_id' => $service->id,
            'date' => CarbonImmutable::today()->format('Y-m-d'),
        ]))->assertOk();

        $times = collect($response->json('data.slots'))->pluck('time');

        // now = 10:05, lead = 15 min => earliest bookable start is 10:30.
        $this->assertFalse($times->contains('09:00'));
        $this->assertFalse($times->contains('10:00'));
        $this->assertTrue($times->contains('10:30'));
    }

    public function test_customer_cannot_book_within_the_lead_window(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::today()->setTime(9, 50));
        $service = Service::factory()->create(['duration_minutes' => 60]);

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'customer_name' => 'Anna',
            'phone_country_code' => '+371',
            'customer_phone' => '11111111',
            'starts_at' => CarbonImmutable::today()->setTime(10, 0)->toIso8601String(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('starts_at');
    }
}
