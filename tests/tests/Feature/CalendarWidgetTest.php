<?php

use App\Enums\ConstraintType;
use App\Filament\Widgets\CalendarWidget;
use App\Models\Constraint;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('clicking the save button will save the dragged event data', function () {
    $soldier = Soldier::factory()->create();

    $user = User::factory()->create([
        'userable_id' => $soldier->id,
        'userable_type' => Soldier::class,
    ]);

    $user->load('userable');
    auth()->login($user);
    auth()->user()->assignRole('team-commander', 'soldier');
    session()->put('soldiers_ids', [$soldier->id]);
    $constraint = Constraint::factory()->create([
        'soldier_id' => $soldier->id,
        'constraint_type' => ConstraintType::NOT_TASK->value,
        'start_date' => now('Asia/Jerusalem')->addDays(3),
        'end_date' => now('Asia/Jerusalem')->addDays(4),
    ]);

    $event = [
        'allDay' => false,
        'title' => $constraint->constraint_type,
        'start' => now('Asia/Jerusalem')->addDays(1),
        'end' => now('Asia/Jerusalem')->addDays(2),
        'id' => $constraint->id,
        'display' => 'block',
        'backgroundColor' => $constraint->constraint_color,
        'borderColor' => $constraint->constraint_color,
        'textColor' => 'black',
    ];
    $oldEvent = [
        'allDay' => false,
        'title' => $constraint->constraint_type,
        'start' => $constraint->start_date,
        'end' => $constraint->end_date,
        'id' => $constraint->id,
        'display' => 'block',
        'backgroundColor' => $constraint->constraint_color,
        'borderColor' => $constraint->constraint_color,
        'textColor' => 'black',
    ];
    $delta = [
        'years' => 0,
        'months' => 0,
        'days' => 7,
        'milliseconds' => 0,
    ];
    $_SERVER['HTTP_REFERER'] = 'my-soldiers-constraint';
    livewire(CalendarWidget::class, [
        'model' => Constraint::class,
        'type' => 'my',
    ])
        ->call('onEventDrop', $event, $oldEvent, [], $delta, null, null)
        ->callMountedAction(['save' => true]);

    $this->assertDatabaseHas(Constraint::class, [
        'id' => $constraint->id,
        'start_date' => $event['start'],
        'end_date' => $event['end'],
    ]);
});

it('clicking the cancel button will refresh the calendar', function () {
    $soldier = Soldier::factory()->create();

    $user = User::factory()->create([
        'userable_id' => $soldier->id,
        'userable_type' => Soldier::class,
    ]);

    auth()->login($user);
    auth()->user()->assignRole('team-commander', 'soldier');

    session()->put('soldiers_ids', [$soldier->id]);

    $constraint = Constraint::factory()->create([
        'soldier_id' => $soldier->id,
        'constraint_type' => ConstraintType::NOT_TASK->value,
        'start_date' => now('Asia/Jerusalem')->addDays(3),
        'end_date' => now('Asia/Jerusalem')->addDays(4),
    ]);

    $event = [
        'allDay' => false,
        'title' => $constraint->constraint_type,
        'start' => now('Asia/Jerusalem')->addDays(1),
        'end' => now('Asia/Jerusalem')->addDays(2),
        'id' => $constraint->id,
        'display' => 'block',
        'backgroundColor' => $constraint->constraint_color,
        'borderColor' => $constraint->constraint_color,
        'textColor' => 'black',
    ];
    $oldEvent = [
        'allDay' => false,
        'title' => $constraint->constraint_type,
        'start' => $constraint->start_date,
        'end' => $constraint->end_date,
        'id' => $constraint->id,
        'display' => 'block',
        'backgroundColor' => $constraint->constraint_color,
        'borderColor' => $constraint->constraint_color,
        'textColor' => 'black',
    ];
    $delta = [
        'years' => 0,
        'months' => 0,
        'days' => 7,
        'milliseconds' => 0,
    ];

    livewire(CalendarWidget::class, [
        'model' => Constraint::class,
        'type' => 'my',
    ])
        ->call('onEventDrop', $event, $oldEvent, [], $delta, null, null)
        ->callMountedAction(['cancel' => true])
        ->assertDispatched('filament-fullcalendar--refresh');
});

it('should list events by the user events', function () {
    $user = User::factory()->create();

    $this->asUser('soldier', $user);

    Shift::factory()->create(['soldier_id' => User::factory()->create()->userable_id, 'task_id' => Task::factory()->create()->id]);
    Shift::factory()->count(5)->create(['soldier_id' => $user->userable_id, 'task_id' => Task::factory()->create()->id]);

    $calendar = livewire(CalendarWidget::class, [
        'model' => Shift::class,
        'keys' => collect([
            'id',
            'task_name',
            'start_date',
            'end_date',
            'task_color',
        ]),
        'type' => 'my',
    ])
        ->call('fetchEvents', ['start' => Carbon::yesterday(), 'end' => Carbon::now()->addDays(2), 'timezone' => 'Asia\/Jerusalem']);
    $events = collect($calendar->effects['returns'][0])
        ->filter(fn ($event) => ! is_null($event['id']))
        ->values();

    expect($events)->toHaveCount(5);

});

it('should refresh the fullcalendar', function () {
    livewire(CalendarWidget::class, [
        'model' => Shift::class,
        'type' => 'soldiers',
        'fetchInfo' => ['start' => Carbon::yesterday(), 'end' => Carbon::now()->addDays(12), 'timezone' => 'Asia\/Jerusalem'],
    ])
        ->mountAction('Filters')
        ->callMountedAction(['Filter' => true])
        ->assertDispatched('filament-fullcalendar--refresh');
});

it('should filter the calendar', function () {
    $user = User::factory()->create();
    $task1 = Task::factory()->create(['type' => 'wash']);
    $task2 = Task::factory()->create(['type' => 'clean']);
    Shift::factory()->count(5)->create(['soldier_id' => $user->userable_id, 'task_id' => $task1->id]);
    Shift::factory()->count(5)->create(['soldier_id' => $user->userable_id, 'task_id' => $task2->id]);

    $calendar = livewire(CalendarWidget::class, [
        'model' => Shift::class,
        'keys' => collect([
            'id',
            'task_name',
            'start_date',
            'end_date',
            'task_color',
        ]),
        'type' => 'soldiers',
        'fetchInfo' => ['start' => Carbon::yesterday(), 'end' => Carbon::now()->addDays(5), 'timezone' => 'Asia\/Jerusalem'],
    ])
        ->mountAction('Filters')
        ->setActionData(['soldier_id' => [$user->userable_id], 'type' => [$task2->type]])
        ->callMountedAction(['Filter' => true])
        ->call('fetchEvents', ['start' => Carbon::yesterday(), 'end' => Carbon::now()->addDays(5), 'timezone' => 'Asia\/Jerusalem']);
    $events = collect($calendar->effects['returns'][0])
        ->filter(fn ($event) => ! is_null($event['id']))
        ->values();
    expect($events)->toHaveCount(5);
});

it('should filter the unassigned shifts', function () {
    $task1 = Task::factory()->create(['type' => 'wash']);
    $task2 = Task::factory()->create(['type' => 'clean']);
    Shift::factory()->count(5)->create(['soldier_id' => 1, 'task_id' => $task1->id]);
    Shift::factory()->count(5)->create(['soldier_id' => 1, 'task_id' => $task2->id]);
    Shift::factory()->count(6)->create(['soldier_id' => null, 'task_id' => $task1->id]);
    Shift::factory()->count(2)->create(['soldier_id' => null, 'task_id' => $task2->id]);

    $calendar = livewire(CalendarWidget::class, [
        'model' => Shift::class,
        'keys' => collect([
            'id',
            'task_name',
            'start_date',
            'end_date',
            'task_color',
        ]),
        'type' => 'soldiers',
        'fetchInfo' => ['start' => Carbon::yesterday(), 'end' => Carbon::now()->addDays(5), 'timezone' => 'Asia\/Jerusalem'],
    ])
        ->mountAction('Filters')
        ->setActionData(['reservists' => false, 'unassigned_shifts' => true, 'kind' => null, 'soldier_id' => [], 'type' => [], 'course' => []])
        ->callMountedAction()
        ->call('fetchEvents', ['start' => Carbon::yesterday(), 'end' => Carbon::now()->addDays(5), 'timezone' => 'Asia\/Jerusalem']);
    $events = collect($calendar->effects['returns'][0])
        ->filter(fn ($event) => ! is_null($event['id']))
        ->values();
    expect($events);
});
