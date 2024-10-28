<?php

namespace Database\Seeders;

use App\Models\Soldier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Task;
use App\Enums\RecurrenceType;
use App\Models\Department;
use App\Services\ReccurenceEvents;
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'first_name' => "name",
            'last_name' => "family",
            'password' => Hash::make(1234567),
            'userable_id' => Soldier::factory()->create()->id,
            'userable_type' => "App\Models\Soldier",
        ]);

        $this->call([
            PermissionSeeder::class,
        ]);
        $user->assignRole('manager');

        Department::factory()->create([
            'name' => 'a1',
        ]);
        Task::factory()->create([
            'name' => 'תכנון',
            'type' => 'תכנון',
            'start_hour' => '08:30:00',
            'duration' => 5,
            'parallel_weight' => 0,
            'department_name' => 'a1',
            'recurrence' => collect(['type' => RecurrenceType::WEEKLY, 'days_in_week' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']]),
        ]);
        Task::factory()->create([
            'name' => 'תכנון סוף שבוע',
            'type' => 'תכנון',
            'start_hour' => '08:30:00',
            'duration' => 5,
            'parallel_weight' => 1,
            'department_name' => 'a1',
            'recurrence' => collect(['type' => RecurrenceType::WEEKLY, 'days_in_week' => ['Friday', 'Saturday']]),
        ]);
        Task::factory()->create([
            'name' => 'הטסה',
            'type' => 'הטסה',
            'start_hour' => '09:00:00',
            'duration' => 6,
            'parallel_weight' => 0,
            'department_name' => 'a1',
            'recurrence' => collect(['type' => RecurrenceType::WEEKLY, 'days_in_week' => ['Sunday', 'Wednesday']]),
        ]);
        Task::factory()->create([
            'name' => 'הטסת לילה',
            'type' => 'הטסה',
            'start_hour' => '00:00:00',
            'duration' => 12,
            'parallel_weight' => 0.5,
            'department_name' => 'a1',
            'recurrence' => collect(['type' => RecurrenceType::WEEKLY, 'days_in_week' => ['Monday', 'Thursday']]),
        ]);
        Task::factory()->create([
            'name' => 'בקרה',
            'type' => 'בקרה',
            'start_hour' => '10:00:00',
            'duration' => 6,
            'parallel_weight' => 0,
            'department_name' => 'a1',
            'recurrence' => collect(['type' => RecurrenceType::CUSTOM, 'dates_in_month' => [10]]),
        ]);
        User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['תכנון', 'בקרה'],
                'capacity' => 0,
                'max_nights' => 0,
                'max_weekends' => 0,
                'max_shifts' => 1,
            ])->id,
        ]);
        User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['תכנון', 'הטסה'],
                'capacity' => 2,
                'max_nights' => 1,
                'max_weekends' => 2,
                'max_shifts' => 8,
            ])->id,
        ]);
        User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['תכנון', 'הטסה'],
                'capacity' => 2,
                'max_nights' => 1,
                'max_weekends' => 2,
                'max_shifts' => 8,
            ])->id,
        ]);
        User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['תכנון', 'הטסה'],
                'capacity' => 3,
                'max_nights' => 1,
                'max_weekends' => 2,
                'max_shifts' => 12,
            ])->id,
        ]);
        User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['תכנון', 'הטסה'],
                'capacity' => 3,
                'max_nights' => 2,
                'max_weekends' => 2,
                'max_shifts' => 8,
            ])->id,
        ]);
        User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['תכנון', 'הטסה'],
                'capacity' => 0,
                'max_nights' => 0,
                'max_weekends' => 0,
                'max_shifts' => 10,
            ])->id,
        ]);
        User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['תכנון', 'הטסה'],
                'capacity' => 3,
                'max_nights' => 0,
                'max_weekends' => 3,
                'max_shifts' => 10,
            ])->id,
        ]);
        User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['תכנון', 'הטסה'],
                'capacity' => 3,
                'max_nights' => 0,
                'max_weekends' => 3,
                'max_shifts' => 10,
            ])->id,
        ]);
        User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['תכנון', 'הטסה'],
                'capacity' => 1,
                'max_nights' => 1,
                'max_weekends' => 1,
                'max_shifts' => 4,
            ])->id,
        ]);
        $reccurenceEvents = new ReccurenceEvents;
        $reccurenceEvents->recurrenceTask();

    }
}
