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
            ])->id,
        ])->assignRole('manager');

        User::factory()->create([
            'first_name' => 'meshabetz mishmarot',
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['hatasa', 'tazpit', 'shmira'],
                'capacity' => 6,
                'max_weekends' => 6,
                'max_shifts' => 7,
                'max_nights' => 6,
                'is_reservist' => false,
            ])->id,
        ])->assignRole(['soldier', 'shifts-assignment']);

        for ($i = 0; $i < 15; $i++) {
            $user = User::factory()->create([
                'first_name' => 'mefaked',
                'userable_id' => Soldier::factory()->create([
                    'qualifications' => ['pikud'],
                    'capacity' => 5.5,
                    'max_weekends' => 5.5,
                    'max_shifts' => 10,
                    'max_nights' => 5.5,
                    'course' => fake()->numberBetween(0, 5),
                    'is_reservist' => false,
                ])->id,
            ])->assignRole('soldier');
            $this->createConstraints($user->id);
        }

        for ($i = 0; $i < 40; $i++) {
            $user = User::factory()->create([
                'first_name' => 'chayal pashut',
                'userable_id' => Soldier::factory()->create([
                    'qualifications' => ['hatasa', 'tazpit', 'shmira'],
                    'capacity' => 6,
                    'max_weekends' => 6,
                    'max_shifts' => 7,
                    'max_nights' => 6,
                    'course' => fake()->numberBetween(0, 5),
                    'is_reservist' => false,
                ])->id,
            ])->assignRole('soldier');
            $this->createConstraints($user->id);
        }

        for ($i = 0; $i < 30; $i++) {
            $user = User::factory()->create([
                'first_name' => 'chayal beinony',
                'userable_id' => Soldier::factory()->create([
                    'qualifications' => ['hatasa', 'tazpit', 'shmira', 'tichnun', 'pianuach'],
                    'capacity' => 5.5,
                    'max_weekends' => 5.5,
                    'max_shifts' => 6,
                    'max_nights' => 5.5,
                    'course' => fake()->numberBetween(0, 5),
                    'is_reservist' => false,
                ])->id,
            ])->assignRole('soldier');
            $this->createConstraints($user->id);
        }

        for ($i = 0; $i < 25; $i++) {
            $user = User::factory()->create([
                'first_name' => 'chayal vatik',
                'userable_id' => Soldier::factory()->create([
                    'qualifications' => ['hatasa', 'bakara', 'tichnun', 'pianuach'],
                    'capacity' => 5,
                    'max_weekends' => 5,
                    'max_shifts' => 5,
                    'max_nights' => 5,
                    'course' => fake()->numberBetween(0, 5),
                    'is_reservist' => false,
                ])->id,
            ])->assignRole('soldier');
            $this->createConstraints($user->id);
        }

        for ($i = 0; $i < 10; $i++) {
            $user = User::factory()->create([
                'first_name' => 'menahalan',
                'userable_id' => Soldier::factory()->create([
                    'qualifications' => ['minhal', 'bdikat ziud'],
                    'capacity' => 0,
                    'max_weekends' => 0,
                    'max_shifts' => 4,
                    'max_nights' => 0,
                    'course' => fake()->numberBetween(0, 5),
                    'is_reservist' => false,
                ])->id,
            ])->assignRole('soldier');
            $this->createConstraints($user->id);
        }

        for ($i = 0; $i < 8; $i++) {
            $user = User::factory()->create([
                'first_name' => 'navat',
                'userable_id' => Soldier::factory()->create([
                    'qualifications' => ['nivut'],
                    'capacity' => 10,
                    'max_weekends' => 10,
                    'max_shifts' => 12,
                    'max_nights' => 10,
                    'course' => fake()->numberBetween(0, 5),
                    'is_reservist' => false,
                ])->id,
            ])->assignRole('soldier');
            $this->createConstraints($user->id);
        }

        // Tasks
        // tichnun
        Task::factory()->create([
            'name' => 'tichnun א-ה בוקר',
            'start_hour' => '10:00:00',
            'duration' => 4,
            'parallel_weight' => 0,
            'type' => 'tichnun',
            'is_weekend' => false,
            'is_night' => false,
            'color' => '#b54b4b',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'tichnun א-ה לילה',
            'start_hour' => '02:00:00',
            'duration' => 4,
            'parallel_weight' => 1,
            'type' => 'tichnun',
            'is_weekend' => false,
            'is_night' => true,
            'color' => '#b54b4b',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'tichnun 1 סופ"ש',
            'start_hour' => '10:30:00',
            'duration' => 25,
            'parallel_weight' => 2.5,
            'type' => 'tichnun',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#b54b4b',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'tichnun 2 סופ"ש',
            'start_hour' => '11:10:00',
            'duration' => 25,
            'parallel_weight' => 2.5,
            'type' => 'tichnun',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#b54b4b',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        // bakara
        Task::factory()->create([
            'name' => 'bakara ארבע פעמים בשבוע בוקר',
            'start_hour' => '10:00:00',
            'duration' => 4,
            'parallel_weight' => 0,
            'type' => 'bakara',
            'is_weekend' => false,
            'is_night' => false,
            'color' => '#4bb5ac',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'bakara שלוש פעמים בשבוע לילה',
            'start_hour' => '03:20:00',
            'duration' => 4,
            'parallel_weight' => 1,
            'type' => 'bakara',
            'is_weekend' => false,
            'is_night' => true,
            'color' => '#4bb5ac',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'bakara סופ"ש',
            'start_hour' => '10:05:00',
            'duration' => 26,
            'parallel_weight' => 2.5,
            'type' => 'bakara',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#4bb5ac',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        // pikud
        Task::factory()->create([
            'name' => 'pikud 1 א-ה בוקר',
            'start_hour' => '10:00:00',
            'duration' => 4,
            'parallel_weight' => 0,
            'type' => 'pikud',
            'is_weekend' => false,
            'is_night' => false,
            'color' => '#4bb569',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'pikud 2 א-ה בוקר',
            'start_hour' => '10:00:00',
            'duration' => 4,
            'parallel_weight' => 0,
            'type' => 'pikud',
            'is_weekend' => false,
            'is_night' => false,
            'color' => '#4bb569',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'pikud  1 סופש',
            'start_hour' => '10:00:00',
            'duration' => 25,
            'parallel_weight' => 2.5,
            'type' => 'pikud',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#4bb569',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'pikud 2 סופש',
            'start_hour' => '10:00:00',
            'duration' => 25,
            'parallel_weight' => 2.5,
            'type' => 'pikud',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#4bb569',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'pikud לילה א-ה',
            'start_hour' => '03:00:00',
            'duration' => 4,
            'parallel_weight' => 1,
            'type' => 'pikud',
            'is_weekend' => false,
            'is_night' => true,
            'color' => '#4bb569',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        // minhal
        Task::factory()->create([
            'name' => 'minhal שלוש פעמים בשבוע בוקר',
            'start_hour' => '13:00:00',
            'duration' => 2,
            'parallel_weight' => 0,
            'type' => 'minhal',
            'is_weekend' => false,
            'is_night' => false,
            'color' => '#c5d649',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::TUESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        // hatasa
        Task::factory()->create([
            'name' => 'hatasa א-ה בוקר',
            'start_hour' => '09:00:00',
            'duration' => 6,
            'parallel_weight' => 0,
            'type' => 'hatasa',
            'is_weekend' => false,
            'is_night' => false,
            'color' => '#d649b5',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'hatasa א-ה לילה',
            'start_hour' => '02:00:00',
            'duration' => 6,
            'parallel_weight' => 1,
            'type' => 'hatasa',
            'is_weekend' => false,
            'is_night' => true,
            'color' => '#d649b5',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'hatasa 1 סופש',
            'start_hour' => '06:00:00',
            'duration' => 26,
            'parallel_weight' => 2.5,
            'type' => 'hatasa',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#d649b5',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'hatasa 2 סופש',
            'start_hour' => '06:00:00',
            'duration' => 26,
            'parallel_weight' => 2.5,
            'type' => 'hatasa',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#d649b5',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        // shmira
        Task::factory()->create([
            'name' => 'shmira א-ה בוקר',
            'start_hour' => '07:00:00',
            'duration' => 12,
            'parallel_weight' => 0,
            'type' => 'shmira',
            'is_weekend' => false,
            'is_night' => false,
            'color' => '#ee8559',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'shmira  א-ה לילה',
            'start_hour' => '01:00:00',
            'duration' => 7,
            'parallel_weight' => 1,
            'type' => 'shmira',
            'is_weekend' => false,
            'is_night' => true,
            'color' => '#ee8559',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'shmira 1 סופש',
            'start_hour' => '06:00:00',
            'duration' => 26,
            'parallel_weight' => 2.5,
            'type' => 'shmira',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#ee8559',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'shmira סופש 2',
            'start_hour' => '06:00:00',
            'duration' => 26,
            'parallel_weight' => 2.5,
            'type' => 'shmira',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#ee8559',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        // pianuach
        Task::factory()->create([
            'name' => 'pianuach א-ה בוקר',
            'start_hour' => '07:00:00',
            'duration' => 7,
            'parallel_weight' => 0,
            'type' => 'pianuach',
            'is_weekend' => false,
            'is_night' => false,
            'color' => '#3574fb',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'pianuach א-ה לילה',
            'start_hour' => '01:00:00',
            'duration' => 5,
            'parallel_weight' => 1,
            'type' => 'pianuach',
            'is_weekend' => false,
            'is_night' => true,
            'color' => '#3574fb',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'pianuach 1 סופש',
            'start_hour' => '07:00:00',
            'duration' => 27,
            'parallel_weight' => 2.5,
            'type' => 'pianuach',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#3574fb',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'pianuach 2 סופש',
            'start_hour' => '07:00:00',
            'duration' => 27,
            'parallel_weight' => 2.5,
            'type' => 'pianuach',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#3574fb',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        // nivut
        Task::factory()->create([
            'name' => 'nivut א-ה בוקר',
            'start_hour' => '11:00:00',
            'duration' => 2,
            'parallel_weight' => 0,
            'type' => 'nivut',
            'is_weekend' => false,
            'is_night' => false,
            'color' => '#ed8d8d8b',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'nivut  א-ה לילה',
            'start_hour' => '01:00:00',
            'duration' => 2.5,
            'parallel_weight' => 1,
            'type' => 'nivut',
            'is_weekend' => false,
            'is_night' => true,
            'color' => '#ed8d8d8b',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'nivut 1 סופש',
            'start_hour' => '11:00:00',
            'duration' => 28,
            'parallel_weight' => 2.5,
            'type' => 'nivut',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#ed8d8d8b',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'nivut 2 סופש',
            'start_hour' => '11:00:00',
            'duration' => 28,
            'parallel_weight' => 2.5,
            'type' => 'nivut',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#ed8d8d8b',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        // tazpit
        Task::factory()->create([
            'name' => 'tazpit א-ה בוקר 1',
            'start_hour' => '08:00:00',
            'duration' => 10,
            'parallel_weight' => 0,
            'type' => 'tazpit',
            'is_weekend' => false,
            'is_night' => false,
            'color' => '#77ff23',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'tazpit א-ה בוקר 2',
            'start_hour' => '08:00:00',
            'duration' => 10,
            'parallel_weight' => 0,
            'type' => 'tazpit',
            'is_weekend' => false,
            'is_night' => false,
            'color' => '#77ff23',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'tazpit  א-ה לילה',
            'start_hour' => '01:30:00',
            'duration' => 6,
            'parallel_weight' => 1,
            'type' => 'tazpit',
            'is_weekend' => false,
            'is_night' => true,
            'color' => '#77ff23',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY, DaysInWeek::MONDAY, DaysInWeek::TUESDAY, DaysInWeek::WEDNESDAY, DaysInWeek::THURSDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'tazpit 1 סופש',
            'start_hour' => '11:45:00',
            'duration' => 25,
            'parallel_weight' => 2.5,
            'type' => 'tazpit',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#77ff23',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        Task::factory()->create([
            'name' => 'tazpit 2 סופש',
            'start_hour' => '11:00:00',
            'duration' => 25,
            'parallel_weight' => 2.5,
            'type' => 'tazpit',
            'is_weekend' => true,
            'is_night' => false,
            'color' => '#77ff23',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::FRIDAY],
            ]),
        ]);
        // bdikat ziud
        Task::factory()->create([
            'name' => 'bdikat ziud פעם בשבוע בוקר',
            'start_hour' => '11:15:00',
            'duration' => 3.5,
            'parallel_weight' => 0,
            'type' => 'bdikat ziud',
            'is_weekend' => false,
            'is_night' => false,
            'color' => '#a7b2c3',
            'recurring' => collect([
                'type' => RecurringType::WEEKLY,
                'days_in_week' => [DaysInWeek::SUNDAY],
            ]),
        ]);
    }

    protected function createConstraints(int $userId)
    {
        for ($i = 0; $i < count(ConstraintType::cases()); $i++) {
            $times = ConstraintType::getLimit()[ConstraintType::cases()[$i]->value] > 0
                ? random_int(0, ConstraintType::getLimit()[ConstraintType::cases()[$i]->value]) :
                random_int(0, random_int(0, 5));
            for ($j = 0; $j < $times; $j++) {
                $startDate = call_user_func([$this, ConstraintType::cases()[$i]->name]);
                Constraint::factory()->create([
                    'soldier_id' => User::find($userId)->userable_id,
                    'constraint_type' => ConstraintType::cases()[$i],
                    'start_date' => $startDate,
                    'end_date' => $startDate->copy()->addHours(random_int(1, 5)),
                ]);
            }
        }
    }

    protected function getDatesOfMonth($month = null)
    {
        $month ??= now()->addMonth();

        return CarbonPeriod::between($month->startOfMonth(), $month->copy()->endOfMonth());
    }

    protected function getThursday()
    {
        $period = $this->getDatesOfMonth();

        return collect($period)
            ->filter(
                fn ($date) => Carbon::parse($date)->isThursday()
            )->all();
    }

    protected function getWeekends()
    {
        $period = $this->getDatesOfMonth();

        return collect($period)
            ->filter(
                fn ($date) => Carbon::parse($date)->isFriday() || Carbon::parse($date)->isSaturday()
            )
            ->all();
    }

    protected function getTime()
    {
        return Carbon::now()->subSeconds(rand(0, 30 * 24 * 60 * 60));
    }

    protected function getNightHour()
    {
        $time = $this->getTime();
        if ($time->hour < 20 && $time->hour >= 8) {
            return $time->addHours(20 - $time->hour);
        }

        return $time;
    }

    protected function NOT_WEEKEND()
    {
        $time = $this->getTime();

        return Carbon::parse(collect($this->getWeekends())->random())
            ->setTime(
                $time->hour,
                $time->minute,
                $time->second
            );
    }

    protected function LOW_PRIORITY_NOT_WEEKEND()
    {
        $time = $this->getTime();

        return Carbon::parse(collect($this->getWeekends())->random())
            ->setTime(
                $time->hour,
                $time->minute,
                $time->second
            );
    }

    protected function NOT_TASK()
    {
        $time = $this->getTime();

        return Carbon::parse(collect($this->getDatesOfMonth())->random())
            ->setTime(
                $time->hour,
                $time->minute,
                $time->second
            );
    }

    protected function LOW_PRIORITY_NOT_TASK()
    {
        $time = $this->getTime();

        return Carbon::parse(collect($this->getDatesOfMonth())->random())
            ->setTime(
                $time->hour,
                $time->minute,
                $time->second
            );
    }

    protected function NOT_EVENING()
    {
        $date = collect($this->getDatesOfMonth())->random();
        $nightTime = $this->getNightHour();

        return $date->copy()->setTime($nightTime->hour, $nightTime->minute, $nightTime->second);
    }

    protected function NOT_THURSDAY_EVENING()
    {
        $date = collect($this->getThursday())->random();
        $nightTime = $this->getNightHour();

        return $date->copy()->setTime($nightTime->hour, $nightTime->minute, $nightTime->second);
    }

    protected function VACATION()
    {
        $time = $this->getTime();

        return Carbon::parse(collect($this->getDatesOfMonth())->random())
            ->setTime(
                $time->hour,
                $time->minute,
                $time->second
            );
    }

    protected function MEDICAL()
    {
        $time = $this->getTime();

        return Carbon::parse(collect($this->getDatesOfMonth())->random())
            ->setTime(
                $time->hour,
                $time->minute,
                $time->second
            );
    }

    protected function SCHOOL()
    {
        $time = $this->getTime();

        return Carbon::parse(collect($this->getDatesOfMonth())->random())
            ->setTime(
                $time->hour,
                $time->minute,
                $time->second
            );
    }
}
