<?php

use App\Enums\ConstraintType;
use App\Enums\TaskKind;
use App\Models\Constraint;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Models\User;
use App\Services\ChangeAssignment;

it('should return the matching soldiers for shift', function () {
    $shiftForChange = Shift::factory()->create([
        'soldier_id' => Soldier::factory()->create()->id,
        'task_id' => Task::factory()->create([
            'type' => 'clean',
            'kind' => TaskKind::REGULAR->value,
        ])->id,
        'is_weekend' => false,
        'start_date' => now()->isLastOfMonth() ? now()->subDays(8) : now()->addHours(5),
        'end_date' => now()->isLastOfMonth() ? now()->subDays(7) : now()->addHours(6),
    ]);
    $soldier1 = Soldier::factory()->create([
        'qualifications' => ['clean', 'jump'],
        'is_reservist' => false,
    ]);
    $soldier2 = Soldier::factory()->create([
        'qualifications' => ['run'],
        'is_reservist' => false,
    ]);
    $soldier3 = Soldier::factory()->create([
        'qualifications' => ['clean', 'run'],
        'is_reservist' => false,
    ]);
    $soldier4 = Soldier::factory()->create([
        'qualifications' => ['jump'],
        'is_reservist' => false,
    ]);
    $soldier5 = Soldier::factory()->create([
        'qualifications' => ['clean'],
        'is_reservist' => false,
    ]);
    $soldier6 = Soldier::factory()->create([
        'qualifications' => ['clean'],
        'is_reservist' => false,
    ]);
    for ($i = 2; $i <= 7; $i++) {
        User::factory()->create(['userable_id' => Soldier::find($i)->id]);
    }
    Shift::factory()->create([
        'soldier_id' => $soldier5->id,
        'task_id' => Task::factory()->create([
            'type' => 'clean',
            'kind' => TaskKind::REGULAR->value,
        ])->id,
        'start_date' => now()->isLastOfMonth() ? now()->subDays(9) : now()->addHours(4),
        'end_date' => now()->isLastOfMonth() ? now()->subDays(6) : now()->addHours(7),
    ]);
    Constraint::factory()->create([
        'soldier_id' => $soldier6->id,
        'constraint_type' => ConstraintType::NOT_TASK->value,
        'start_date' => now()->isLastOfMonth() ? now()->subDays(9) : now()->addHours(4),
        'end_date' => now()->isLastOfMonth() ? now()->subDays(6) : now()->addHours(7),
    ]);

    $result = [
        $soldier1->id => Soldier::find($soldier1->id)->user->displayName,
        $soldier3->id => Soldier::find($soldier3->id)->user->displayName,
    ];
    $changeAssignment = new ChangeAssignment($shiftForChange);

    expect($changeAssignment->getMatchingSoldiers())->toHaveCount(2);
    expect($changeAssignment->getMatchingSoldiers())->toEqual($result);
});

it('should return the matching shifts for exchanging', function () {
    $shiftForExchange = Shift::factory()->create([
        'soldier_id' => User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['clean', 'jump', 'sing'],
                'is_reservist' => false,
            ])->id,
        ])->userable_id,
        'task_id' => Task::factory()->create([
            'type' => 'clean',
            'kind' => TaskKind::REGULAR->value,
        ])->id,
        'is_weekend' => false,
        'start_date' => now()->addHours(1),
        'end_date' => now()->addHours(2),
    ]);
    $shift1 = Shift::factory()->create([
        'soldier_id' => User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['clean', 'jump'],
                'is_reservist' => false,
            ])->id,
        ])->userable_id,
        'task_id' => Task::factory()->create([
            'type' => 'jump',
            'kind' => TaskKind::REGULAR->value,
        ])->id,
        'is_weekend' => false,
        'start_date' => now()->addHours(3),
        'end_date' => now()->addHours(4),
    ]);
    $shift2 = Shift::factory()->create([
        'soldier_id' => User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['run'],
                'is_reservist' => false,
            ])->id,
        ])->userable_id,
        'task_id' => Task::factory()->create([
            'type' => 'run',
            'kind' => TaskKind::REGULAR->value,
        ])->id,
        'is_weekend' => false,
        'start_date' => now()->addHours(3),
        'end_date' => now()->addHours(4),
    ]);
    $shift3 = Shift::factory()->create([
        'soldier_id' => User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['clean', 'run'],
                'is_reservist' => false,
            ])->id,
        ])->userable_id,
        'task_id' => Task::factory()->create([
            'type' => 'run',
            'kind' => TaskKind::REGULAR->value,
        ])->id,
        'is_weekend' => false,
        'start_date' => now()->addHours(1),
        'end_date' => now()->addHours(2),
    ]);
    $shift4 = Shift::factory()->create([
        'soldier_id' => User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['jump', 'clean'],
                'is_reservist' => false,
            ])->id,
        ])->userable_id,
        'task_id' => Task::factory()->create([
            'type' => 'jump',
            'kind' => TaskKind::REGULAR->value,
        ])->id,
        'is_weekend' => false,
        'start_date' => now()->addHours(3),
        'end_date' => now()->addHours(4),
    ]);
    $shift5 = Shift::factory()->create([
        'soldier_id' => User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['clean'],
                'is_reservist' => false,
            ])->id,
        ])->userable_id,
        'task_id' => Task::factory()->create([
            'type' => 'clean',
            'kind' => TaskKind::REGULAR->value,
        ])->id,
        'is_weekend' => false,
        'start_date' => now()->addHours(1),
        'end_date' => now()->addHours(2),
    ]);
    $shift6 = Shift::factory()->create([
        'soldier_id' => User::factory()->create([
            'userable_id' => Soldier::factory()->create([
                'qualifications' => ['clean'],
                'is_reservist' => false,
            ])->id,
        ])->userable_id,
        'task_id' => Task::factory()->create([
            'type' => 'clean',
            'kind' => TaskKind::REGULAR->value,
        ])->id,
        'is_weekend' => false,
        'start_date' => now()->addHours(3),
        'end_date' => now()->addHours(4),
    ]);
    Constraint::factory()->create([
        'soldier_id' => $shift6->soldier_id,
        'constraint_type' => ConstraintType::NOT_TASK->value,
        'start_date' => now()->addHours(1),
        'end_date' => now()->addHours(2),
    ]);
    $expectedShifts = collect([$shift1->id, $shift4->id]);
    $changeAssignment = new ChangeAssignment($shiftForExchange);
    $result = $changeAssignment->getMatchingShifts();
    expect($result['shifts']->count())->toBe(2);
    $shiftsIds = collect([]);
    $result['shifts']->each(function ($shift) use (&$shiftsIds) {
        $shiftsIds->push($shift['shift']->id);
    });
    expect($expectedShifts)->toEqual($shiftsIds);
});

it('should exchange shifts', function () {
    $soldier1 = Soldier::factory()->create();
    $soldier2 = Soldier::factory()->create();
    $shift1 = Shift::factory()->create(['soldier_id' => $soldier1->id]);
    $shift2 = Shift::factory()->create(['soldier_id' => $soldier2->id]);
    $changeAssignment = new ChangeAssignment($shift1);
    $changeAssignment->exchange($shift2);
    $this->assertDatabaseHas('shifts', [
        'id' => $shift1->id,
        'soldier_id' => $soldier2->id,
    ]);
    $this->assertDatabaseHas('shifts', [
        'id' => $shift2->id,
        'soldier_id' => $soldier1->id,
    ]);
});
