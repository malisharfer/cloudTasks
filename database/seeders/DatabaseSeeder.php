<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Soldier;
use App\Models\User;
use App\Enums\ConstraintType;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
        ]);
        User::factory()->create([
            'first_name' => 'newspace',
            'last_name' => 'newspace',
            'password' => '1234567',
            'userable_id' => Soldier::factory()->create([
                'qualifications' => [],
                'capacity' => 10,
                'max_weekends' => 10,
                'max_shifts' => 10,
                'max_nights' => 10,
                'course' => fake()->numberBetween(0, 5),
                'is_reservist' => false,
                'constraints_limit' => ConstraintType::getLimit(),
            ])->id,
        ])->assignRole(['soldier', 'manager']);
    }
}
