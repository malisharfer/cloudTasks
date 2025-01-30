<?php

use App\Enums\ConstraintType;
use App\Models\Constraint;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Services\Assignment;
use App\Services\Helpers;
use App\Services\MaxData;
use App\Services\Range;
use App\Services\Shift as ShiftService;
use App\Services\Soldier as SoldierService;

it('should return object of shift service type', function () {
    $shift = Shift::factory()->create();
    $shiftService = new ShiftService(
        $shift->id,
        $shift->task->type,
        $shift->start_date,
        $shift->end_date,
        $shift->parallel_weight,
        $shift->task->is_night,
        $shift->is_weekend,
        $shift->task->in_parallel,
        $shift->task->concurrent_tasks
    );
    expect(Helpers::buildShift($shift))->toBeInstanceOf(App\Services\Shift::class);
    expect(Helpers::buildShift($shift))->toEqual($shiftService);
});

it('should return object of soldier service type', function () {
    $soldier = Soldier::factory()->create(['qualifications' => []]);
    $soldierService = new SoldierService(
        $soldier->id,
        new MaxData($soldier->capacity, 0),
        new MaxData($soldier->max_shifts, 0),
        new MaxData($soldier->max_nights, 0),
        new MaxData($soldier->max_weekends, 0),
        $soldier->qualifications,
        [],
        []
    );
    expect(Helpers::buildSoldier($soldier, [], [], []))->toBeInstanceOf(App\Services\Soldier::class);
    expect(Helpers::buildSoldier($soldier, [], [], []))->toEqual($soldierService);
});

it('should return array of constraint service type of constraints that have not expired', function () {
    $pastConstraints = Constraint::factory()->count(4)->create([
        'soldier_id' => Soldier::factory()->create()->id,
        'constraint_type' => ConstraintType::NOT_TASK->value,
        'start_date' => now()->subMonth()->subDays(2),
        'end_date' => now()->subMonth()->subDay(),
    ]);
    $futureConstraints = Constraint::factory()->count(3)->create([
        'soldier_id' => Soldier::factory()->create()->id,
        'constraint_type' => ConstraintType::NOT_TASK->value,
        'start_date' => now()->isLastOfMonth() ? now()->subDays(6) : now()->addHours(7),
        'end_date' => now()->isLastOfMonth() ? now()->subDays(5) : now()->addHours(8),
    ]);
    $constraints = collect([...$pastConstraints, ...$futureConstraints]);
    $range = new Range(now()->startOfMonth(), now()->endOfMonth());
    expect(Helpers::buildConstraints($constraints, $range))->toHaveCount(3);
});

it('should return the capacity hold of soldiers paramaters', function () {
    $shifts = Shift::factory()->count(random_int(0, 10))->create();
    $shifts = $shifts->map(fn ($shift) => Helpers::buildShift($shift));
    $result = [
        'count' => $shifts->count(),
        'points' => $shifts->sum('points'),
        'sumWeekends' => $shifts->filter(fn ($shift) => $shift->isWeekend)->sum('points'),
        'sumNights' => $shifts->filter(fn ($shift) => $shift->isNight)->sum('points'),
    ];
    expect(Helpers::capacityHold($shifts))->toEqual($result);
});

it('should return shifts spaces', function () {
    $shifts = Shift::factory()->count(3)->create(['is_weekend' => false, 'task_id' => Task::factory()->create(['is_night' => true, 'in_parallel' => false])->id]);
    $shifts = $shifts->map(fn ($shift) => Helpers::buildShift($shift));
    expect(Helpers::addShiftsSpaces($shifts))->toHaveCount(6);
});

it('should return soldiers shifts', function () {
    $soldier = Soldier::factory()->create();
    $shifts = Shift::factory()->count(3)->create([
        'soldier_id' => $soldier->id,
        'task_id' => Task::factory()->create([
            'in_parallel' => false,
        ])->id,
        'start_date' => now()->isLastOfMonth() ? now()->subDays(6)->startOfSecond() : now()->addHours(7)->startOfSecond(),
        'end_date' => now()->isLastOfMonth() ? now()->subDays(5)->startOfSecond() : now()->addHours(8)->startOfSecond()]);
    $result = $shifts->map(fn ($shift) => Helpers::buildShift($shift));
    expect(Helpers::getSoldiersShifts($soldier->id, new Range(now()->startOfMonth(), now()->endOfMonth()), false))->toEqual($result);
});

it('should return soldiers constraints', function () {
    $soldier = Soldier::factory()->create();
    Constraint::factory()->count(4)->create([
        'soldier_id' => $soldier->id,
        'constraint_type' => ConstraintType::NOT_TASK->value,
        'start_date' => now()->isLastOfMonth() ? now()->subDays(9) : now()->addHours(5),
        'end_date' => now()->isLastOfMonth() ? now()->subDays(8) : now()->addHours(6),
    ]);
    $range = new Range(now()->startOfMonth(), now()->endOfMonth());
    expect(Helpers::getConstraintBy($soldier->id, $range))->toHaveCount(4);
});

it('should update shifts table', function () {
    $soldier = Soldier::factory()->create();
    $shift = Shift::factory()->create(['soldier_id' => 22]);
    $assignments = collect([new Assignment($shift->id, $soldier->id)]);
    Helpers::updateShiftTable($assignments);
    $this->assertDatabaseHas(Shift::class, [
        'id' => $shift->id,
        'soldier_id' => $soldier->id,
    ]);
});