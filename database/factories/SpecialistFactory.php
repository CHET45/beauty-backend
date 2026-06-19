<?php

namespace Database\Factories;

use App\Models\Specialist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Specialist>
 */
class SpecialistFactory extends Factory
{
    protected $model = Specialist::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'title' => fake()->optional()->jobTitle(),
            'phone' => fake()->phoneNumber(),
            'bio' => fake()->optional()->sentence(),
            'photo_url' => fake()->optional()->imageUrl(200, 200, 'people'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
