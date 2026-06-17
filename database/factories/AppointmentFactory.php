<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = CarbonImmutable::tomorrow()->setTime(10, 0);
        $duration = 60;

        return [
            'service_id' => Service::factory(),
            'customer_name' => fake()->name(),
            'phone_country_code' => '+371',
            'customer_phone' => fake()->numerify('########'),
            'customer_email' => fake()->optional()->safeEmail(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes($duration),
            'status' => AppointmentStatus::Pending,
            'notes' => null,
        ];
    }
}
