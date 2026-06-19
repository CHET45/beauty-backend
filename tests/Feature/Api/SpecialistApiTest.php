<?php

namespace Tests\Feature\Api;

use App\Models\Service;
use App\Models\Specialist;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpecialistApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_lists_active_specialists_for_a_service(): void
    {
        $service = Service::factory()->create();

        $offering = Specialist::factory()->create(['name' => 'Elena']);
        $offering->services()->attach($service);

        $inactive = Specialist::factory()->inactive()->create(['name' => 'Hidden']);
        $inactive->services()->attach($service);

        // Offers a different service only — must not appear.
        $other = Specialist::factory()->create(['name' => 'Other']);
        $other->services()->attach(Service::factory()->create());

        $this->getJson("/api/services/{$service->id}/specialists")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Elena');
    }

    public function test_customer_can_book_with_a_specialist_who_offers_the_service(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $specialist = Specialist::factory()->create();
        $specialist->services()->attach($service);

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'customer_name' => 'Anna',
            'phone_country_code' => '+371',
            'customer_phone' => '20123456',
            'starts_at' => CarbonImmutable::tomorrow()->setTime(10, 0)->toIso8601String(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.specialist_id', $specialist->id)
            ->assertJsonPath('data.specialist.name', $specialist->name);
    }

    public function test_booking_rejects_a_specialist_who_does_not_offer_the_service(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        // Specialist exists but offers a different service.
        $specialist = Specialist::factory()->create();
        $specialist->services()->attach(Service::factory()->create());

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'customer_name' => 'Anna',
            'phone_country_code' => '+371',
            'customer_phone' => '20123456',
            'starts_at' => CarbonImmutable::tomorrow()->setTime(10, 0)->toIso8601String(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('specialist_id');
    }

    public function test_customer_can_book_without_choosing_a_specialist(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        Specialist::factory()->create()->services()->attach($service);

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'customer_name' => 'Anna',
            'phone_country_code' => '+371',
            'customer_phone' => '20123456',
            'starts_at' => CarbonImmutable::tomorrow()->setTime(10, 0)->toIso8601String(),
        ])
            ->assertCreated()
            ->assertJsonPath('data.specialist_id', null);
    }

    public function test_two_specialists_can_be_booked_in_the_same_slot(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $a = Specialist::factory()->create();
        $b = Specialist::factory()->create();
        $a->services()->attach($service);
        $b->services()->attach($service);

        $startsAt = CarbonImmutable::tomorrow()->setTime(10, 0)->toIso8601String();

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'specialist_id' => $a->id,
            'customer_name' => 'Anna',
            'phone_country_code' => '+371',
            'customer_phone' => '20123456',
            'starts_at' => $startsAt,
        ])->assertCreated();

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'specialist_id' => $b->id,
            'customer_name' => 'Maria',
            'phone_country_code' => '+371',
            'customer_phone' => '20987654',
            'starts_at' => $startsAt,
        ])->assertCreated();
    }

    public function test_same_specialist_cannot_be_double_booked(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $specialist = Specialist::factory()->create();
        $specialist->services()->attach($service);

        $startsAt = CarbonImmutable::tomorrow()->setTime(10, 0)->toIso8601String();

        $payload = [
            'service_id' => $service->id,
            'specialist_id' => $specialist->id,
            'customer_name' => 'Anna',
            'phone_country_code' => '+371',
            'customer_phone' => '20123456',
            'starts_at' => $startsAt,
        ];

        $this->postJson('/api/appointments', $payload)->assertCreated();

        $this->postJson('/api/appointments', [
            ...$payload,
            'customer_name' => 'Maria',
            'customer_phone' => '20987654',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('starts_at');
    }

    public function test_any_booking_is_rejected_when_the_whole_pool_is_busy(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $only = Specialist::factory()->create();
        $only->services()->attach($service);

        $startsAt = CarbonImmutable::tomorrow()->setTime(10, 0)->toIso8601String();

        // The only specialist for this service is taken at this slot.
        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'specialist_id' => $only->id,
            'customer_name' => 'Anna',
            'phone_country_code' => '+371',
            'customer_phone' => '20123456',
            'starts_at' => $startsAt,
        ])->assertCreated();

        // "Any" must not slip through when no one in the pool is free.
        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'customer_name' => 'Maria',
            'phone_country_code' => '+371',
            'customer_phone' => '20987654',
            'starts_at' => $startsAt,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('starts_at');
    }

    public function test_available_slots_are_per_specialist(): void
    {
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $busy = Specialist::factory()->create();
        $free = Specialist::factory()->create();
        $busy->services()->attach($service);
        $free->services()->attach($service);

        $day = CarbonImmutable::tomorrow();

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'specialist_id' => $busy->id,
            'customer_name' => 'Anna',
            'phone_country_code' => '+371',
            'customer_phone' => '20123456',
            'starts_at' => $day->setTime(10, 0)->toIso8601String(),
        ])->assertCreated();

        $this->getJson('/api/available-slots?'.http_build_query([
            'service_id' => $service->id,
            'specialist_id' => $busy->id,
            'date' => $day->format('Y-m-d'),
        ]))
            ->assertOk()
            ->assertJsonFragment(['time' => '10:00', 'available' => false]);

        $this->getJson('/api/available-slots?'.http_build_query([
            'service_id' => $service->id,
            'specialist_id' => $free->id,
            'date' => $day->format('Y-m-d'),
        ]))
            ->assertOk()
            ->assertJsonFragment(['time' => '10:00', 'available' => true]);
    }

    public function test_admin_can_manage_specialists_and_assign_services(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create(), ['admin']);

        $service = Service::factory()->create();

        $id = $this->postJson('/api/admin/specialists', [
            'name' => 'Elena Petrova',
            'title' => 'Senior stylist',
            'phone' => '+371 2911 2233',
            'photo_url' => 'https://example.com/elena.jpg',
            'is_active' => true,
            'service_ids' => [$service->id],
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Elena Petrova')
            ->assertJsonPath('data.service_ids', [$service->id])
            ->json('data.id');

        $this->putJson("/api/admin/specialists/{$id}", [
            'name' => 'Elena P.',
            'phone' => '+371 2911 2233',
            'is_active' => false,
            'service_ids' => [],
        ])
            ->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.service_ids', []);

        $this->deleteJson("/api/admin/specialists/{$id}")
            ->assertNoContent();
    }

    public function test_admin_can_store_a_specialist_photo_in_the_database(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create(), ['admin']);

        $photo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        $id = $this->postJson('/api/admin/specialists', [
            'name' => 'Photo specialist',
            'phone' => '+371 2000 0001',
            'photo_url' => $photo,
            'is_active' => true,
            'service_ids' => [],
        ])
            ->assertCreated()
            ->assertJsonPath('data.photo_url', $photo)
            ->json('data.id');

        $this->assertDatabaseHas('specialists', [
            'id' => $id,
            'photo_url' => $photo,
        ]);
    }

    public function test_admin_rejects_invalid_specialist_photo_data(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create(), ['admin']);

        $this->postJson('/api/admin/specialists', [
            'name' => 'Invalid photo specialist',
            'phone' => '+371 2000 0002',
            'photo_url' => 'data:image/png;base64,'.base64_encode('not-a-png'),
            'is_active' => true,
            'service_ids' => [],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('photo_url');
    }

    public function test_specialist_with_appointments_cannot_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create(), ['admin']);

        $service = Service::factory()->create();
        $specialist = Specialist::factory()->create();
        $specialist->services()->attach($service);
        $specialist->appointments()->create([
            'service_id' => $service->id,
            'customer_name' => 'Client',
            'phone_country_code' => '+371',
            'customer_phone' => '20123456',
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
            'status' => 'pending',
        ]);

        $this->deleteJson("/api/admin/specialists/{$specialist->id}")
            ->assertConflict();
    }

    public function test_admin_can_assign_a_valid_specialist_to_a_booking(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create(), ['admin']);

        $service = Service::factory()->create();
        $specialist = Specialist::factory()->create();
        $specialist->services()->attach($service);

        $appointment = $service->appointments()->create([
            'customer_name' => 'Client',
            'phone_country_code' => '+371',
            'customer_phone' => '20123456',
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
            'status' => 'pending',
        ]);

        $this->patchJson("/api/admin/appointments/{$appointment->id}", [
            'specialist_id' => $specialist->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.specialist_id', $specialist->id);
    }

    public function test_admin_can_assign_an_active_specialist_outside_the_service_list(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create(), ['admin']);

        $service = Service::factory()->create();
        $specialist = Specialist::factory()->create();
        $specialist->services()->attach(Service::factory()->create());

        $appointment = $service->appointments()->create([
            'customer_name' => 'Client',
            'phone_country_code' => '+371',
            'customer_phone' => '20123456',
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
            'status' => 'pending',
        ]);

        $this->patchJson("/api/admin/appointments/{$appointment->id}", [
            'specialist_id' => $specialist->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.specialist_id', $specialist->id);
    }

    public function test_admin_cannot_confirm_or_complete_an_unassigned_booking(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create(), ['admin']);

        $appointment = Service::factory()->create()->appointments()->create([
            'customer_name' => 'Client',
            'phone_country_code' => '+371',
            'customer_phone' => '20123456',
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
            'status' => 'pending',
        ]);

        foreach (['confirmed', 'completed'] as $status) {
            $this->patchJson("/api/admin/appointments/{$appointment->id}/status", [
                'status' => $status,
            ])
                ->assertUnprocessable()
                ->assertJsonValidationErrorFor('status');
        }
    }
}
