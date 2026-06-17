<?php

namespace Database\Seeders;

use App\Models\Service;
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
}
