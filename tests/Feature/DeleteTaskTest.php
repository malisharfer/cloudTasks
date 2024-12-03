<?php

use App\Models\Shift;
use App\Models\Task;
use App\Resources\TaskResource\Pages\EditTask;
use Database\Seeders\PermissionSeeder;
use Filament\Actions\DeleteAction;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should delete the shifts of the same task type whose time has not yet expired', function () {
    $task = Task::factory()->create();
    Shift::factory()->count(5)->create([
        'task_id' => $task->id,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(2),
    ]);

    livewire(EditTask::class, [
        'record' => $task->getRouteKey(),
    ])
        ->callAction(DeleteAction::class);

    $this->assertDatabaseCount('shifts', 0);
});

it('should not delete the shifts of the same task type whose time have already expired', function () {
    $task = Task::factory()->create();
    Shift::factory()->count(3)->create([
        'task_id' => $task->id,
        'start_date' => now()->addDay(),
        'end_date' => now()->addDays(2),
    ]);

    Shift::factory()->create([
        'task_id' => $task->id,
        'start_date' => '2024-10-02 10:00:00',
        'end_date' => '2024-10-03 10:00:00',
    ]);

    livewire(EditTask::class, [
        'record' => $task->getRouteKey(),
    ])
        ->callAction(DeleteAction::class);

    $this->assertDatabaseCount('shifts', 1);
});
