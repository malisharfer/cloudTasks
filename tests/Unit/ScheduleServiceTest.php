<?php

use App\Enums\RecurringType;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Models\User;
use App\Services\Algorithm;
use App\Services\RecurringEvents;
use App\Services\Schedule;

it('should assign shift to soldier', function () {
    Task::factory()->create([
        'name' => 'Clean',
        'start_hour' => '14:00:00',
        'type' => 'Clean',
        'duration' => 1,
        'parallel_weight' => 0.25,
        'recurring' => collect(['type' => RecurringType::CUSTOM, 'dates_in_month' => [6]]),
    ]);

    User::factory()->create([
        'userable_id' => Soldier::factory()->create([
            'qualifications' => (['Clean']),
            'is_reservist' => false,
            'capacity' => 8,
            'max_shifts' => 8,
            'max_nights' => 8,
            'max_weekends' => 8,
        ])->id,
    ]);

    $recurringEvents = new RecurringEvents;
    $recurringEvents->recurringTask();

    $reflection = new ReflectionClass(Algorithm::class);

    $method = $reflection->getMethod('getShiftWithTasks');
    $method->setAccessible(true);
    $shiftWithTasks = $method->invoke(new Algorithm);

    $method = $reflection->getMethod('getSoldiersDetails');
    $method->setAccessible(true);
    $soldiersWithConstraints = $method->invoke(new Algorithm);

    $schedule = new Schedule($shiftWithTasks, $soldiersWithConstraints);
    $schedule->schedule();
    $this->assertDatabaseHas(Shift::class, [
        'id' => 1,
        'soldier_id' => 1,
    ]);
});
