<?php

namespace Database\Seeders;

use App\Enums\ConstraintType;
use App\Enums\DaysInWeek;
use App\Enums\RecurringType;
use App\Models\Constraint;
use App\Models\Soldier;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
        ]);

        // Soldiers

        User::factory()->create([
            'first_name' => 'name',
            'last_name' => 'family',
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

        User::factory()->create([
            'first_name' => 'מפקד',
            'last_name' => 'מדור 1',
            'password' => '1111111',
            'userable_id' => Soldier::factory()->create([
                'qualifications' => [],
                'capacity' => 10,
                'max_weekends' => 10,
                'max_shifts' => 10,
                'max_nights' => 10,
                'course' => 1,
                'is_reservist' => false,
                'constraints_limit' => ConstraintType::getLimit(),
            ])->id,
        ])->assignRole(['soldier']);

        User::factory()->create([
            'first_name' => 'מפקד',
            'last_name' => 'צוות 1',
            'password' => '1111111',
            'userable_id' => Soldier::factory()->create([
                'qualifications' => [],
                'capacity' => 10,
                'max_weekends' => 10,
                'max_shifts' => 10,
                'max_nights' => 10,
                'course' => 1,
                'is_reservist' => false,
                'constraints_limit' => ConstraintType::getLimit(),
            ])->id,
        ])->assignRole(['soldier']);

        User::factory()->create([
            'first_name' => 'חייל',
            'last_name' => 'א',
            'password' => '1111111',
            'userable_id' => Soldier::factory()->create([
                'qualifications' => [],
                'capacity' => 10,
                'max_weekends' => 10,
                'max_shifts' => 10,
                'max_nights' => 10,
                'course' => 1,
                'is_reservist' => false,
                'constraints_limit' => ConstraintType::getLimit(),
            ])->id,
        ])->assignRole(['soldier']);
        User::factory()->create([
            'first_name' => 'חייל',
            'last_name' => 'ב',
            'password' => '1111111',
            'userable_id' => Soldier::factory()->create([
                'qualifications' => [],
                'capacity' => 10,
                'max_weekends' => 10,
                'max_shifts' => 10,
                'max_nights' => 10,
                'course' => 1,
                'is_reservist' => false,
                'constraints_limit' => ConstraintType::getLimit(),
            ])->id,
        ])->assignRole(['soldier']);
        User::factory()->create([
            'first_name' => 'משבץ',
            'last_name' => '1',
            'password' => '1111111',
            'userable_id' => Soldier::factory()->create([
                'qualifications' => [],
                'capacity' => 10,
                'max_weekends' => 10,
                'max_shifts' => 10,
                'max_nights' => 10,
                'course' => 1,
                'is_reservist' => false,
                'constraints_limit' => ConstraintType::getLimit(),
            ])->id,
        ])->assignRole(['soldier','shifts-assignment']);
}
}