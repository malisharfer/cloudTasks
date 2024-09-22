<?php

use App\Filament\Widgets\CalendarWidget;
use App\Models\Constraint;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\PermissionSeeder;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('clicking the save button will save the dragged event data', function () {

    $constraint = Constraint::factory()->create();

    $event = [
        'allDay' => false,
        'title' => $constraint->constraint_type,
        'start' => '2024-09-03 12:00:00',
        'end' => '2024-09-05 12:00:00',
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
        ->callMountedAction(['save' => true]);

    $this->assertDatabaseHas(Constraint::class, [
        'id' => $constraint->id,
        'start_date' => '2024-09-03 12:00:00',
        'end_date' => '2024-09-05 12:00:00',
    ]);
});

it('clicking the cancel button will refresh the calendar', function () {

    $constraint = Constraint::factory()->create();

    $event = [
        'allDay' => false,
        'title' => $constraint->constraint_type,
        'start' => '2024-09-03 12:00:00',
        'end' => '2024-09-05 12:00:00',
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
            'parallel_weight',
            'start_date',
            'end_date',
            'task_color',
        ]),
        'type' => 'my',
    ])
        ->call('fetchEvents', ['start' => '2024-09-01 00:00:00', 'end' => '2024-10-12 00:00:00', 'timezone' => 'Asia\/Jerusalem']);

    expect($calendar->effects['returns'][0])->toHaveCount(count: 5);
});

it('should list events by the commanders soldiers events', function () {
    $user = User::factory()->create();

    $team = Team::factory()->create(['commander_id' => $user->userable_id]);

    $this->asUser('team-commander', $user);

    $user1 = User::factory()->create(['userable_id' => Soldier::factory()->create(['team_id' => $team->id])->id]);
    $user2 = User::factory()->create(['userable_id' => Soldier::factory()->create(['team_id' => $team->id])->id]);

    Shift::factory()->create(['soldier_id' => $user1->userable_id, 'task_id' => Task::factory()->create()->id]);
    Shift::factory()->create(['soldier_id' => $user2->userable_id, 'task_id' => Task::factory()->create()->id]);
    Shift::factory()->create(['soldier_id' => User::factory()->create()->userable_id, 'task_id' => Task::factory()->create()->id]);
    Shift::factory()->count(5)->create(['soldier_id' => $user->userable_id, 'task_id' => Task::factory()->create()->id]);

    $calendar = livewire(CalendarWidget::class, [
        'model' => Shift::class,
        'keys' => collect([
            'id',
            'parallel_weight',
            'start_date',
            'end_date',
            'task_color',
        ]),
        'type' => 'my_soldiers',
    ])
        ->call('fetchEvents', ['start' => '2024-09-01 00:00:00', 'end' => '2024-10-12 00:00:00', 'timezone' => 'Asia\/Jerusalem']);

    expect($calendar->effects['returns'][0])->toHaveCount(count: 2);
});

it('prevent create\edit the soldiers events', function () {

    livewire(CalendarWidget::class, [
        'model' => Constraint::class,
        'type' => 'my_soldiers',
    ]);
    expect(Saade\FilamentFullCalendar\FilamentFullCalendarPlugin::get()->isSelectable())->toBeFalse();
    expect(Saade\FilamentFullCalendar\FilamentFullCalendarPlugin::get()->isEditable())->toBeFalse();
});
