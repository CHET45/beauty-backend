<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedAdmin();
        $this->seedServices();
        $this->seedSpecialists();
    }

    private function seedAdmin(): void
    {
        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');

        if (! $adminEmail || ! $adminPassword) {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Admin',
                'password' => Hash::make($adminPassword),
                'is_admin' => true,
            ]
        );
    }

    private function seedServices(): void
    {
        $services = [
            ['name' => "Women's haircut", 'description' => 'Wash, haircut, and styling tailored to your face shape.', 'duration_minutes' => 60, 'price' => 35],
            ['name' => "Men's haircut", 'description' => 'Classic or modern haircut with styling.', 'duration_minutes' => 45, 'price' => 25],
            ['name' => 'Hair coloring', 'description' => 'Single-tone coloring with professional products.', 'duration_minutes' => 120, 'price' => 70],
            ['name' => 'Manicure', 'description' => 'Hardware manicure with gel polish.', 'duration_minutes' => 90, 'price' => 30],
            ['name' => 'Pedicure', 'description' => 'Hygienic pedicure with polish.', 'duration_minutes' => 90, 'price' => 35],
            ['name' => 'Makeup', 'description' => 'Day or evening makeup for any occasion.', 'duration_minutes' => 60, 'price' => 40],
        ];

        foreach ($services as $service) {
            Service::query()->updateOrCreate(
                ['name' => $service['name']],
                [...$service, 'is_active' => true],
            );
        }
    }

    private function seedSpecialists(): void
    {
        // Each specialist offers a subset of services (many-to-many); some
        // services share several specialists so they can take clients in parallel.
        $specialists = [
            [
                'name' => 'Elena Petrova',
                'title' => 'Senior stylist',
                'phone' => '+371 2911 2233',
                'bio' => 'Twelve years shaping cuts and color that suit each face.',
                'photo_url' => 'https://i.pravatar.cc/300?img=5',
                'services' => ["Women's haircut", "Men's haircut", 'Hair coloring'],
            ],
            [
                'name' => 'Marcus Reid',
                'title' => 'Barber',
                'phone' => '+371 2940 1234',
                'bio' => 'Classic barbering with a precise, modern finish.',
                'photo_url' => 'https://i.pravatar.cc/300?img=12',
                'services' => ["Men's haircut", "Women's haircut"],
            ],
            [
                'name' => 'Sofia Larsen',
                'title' => 'Nail artist',
                'phone' => '+371 2945 8821',
                'bio' => 'Manicures and pedicures with a clean, lasting result.',
                'photo_url' => 'https://i.pravatar.cc/300?img=32',
                'services' => ['Manicure', 'Pedicure'],
            ],
            [
                'name' => 'Aria Novak',
                'title' => 'Makeup artist',
                'phone' => '+371 2903 5551',
                // No photo on purpose — exercises the placeholder avatar.
                'photo_url' => null,
                'bio' => 'Day-to-evening makeup tailored to the occasion.',
                'services' => ['Makeup', 'Hair coloring'],
            ],
        ];

        foreach ($specialists as $data) {
            $serviceNames = $data['services'];
            unset($data['services']);

            $specialist = Specialist::query()->updateOrCreate(
                ['name' => $data['name']],
                [...$data, 'is_active' => true],
            );

            $serviceIds = Service::query()->whereIn('name', $serviceNames)->pluck('id');
            $specialist->services()->sync($serviceIds);
        }
    }
}
