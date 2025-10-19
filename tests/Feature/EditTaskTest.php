<?php

use App\Enums\RecurringType;
use App\Enums\TaskKind;
use App\Models\Task;
use App\Resources\TaskResource\Pages\EditTask;
use Database\Seeders\PermissionSeeder;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should change the concurrent_tasks to empty array if the kind is not `in_parallel`', function () {
    $task = Task::factory()->create([
        'kind' => TaskKind::INPARALLEL->value,
        'concurrent_tasks' => ['go'],
        'recurring' => ['type' => RecurringType::DAILY->value],
    ]);

    livewire(EditTask::class, [
        'record' => $task->id,
    ])
        ->set('data.kind', TaskKind::NIGHT->value)
        ->set('data.recurring.type', RecurringType::DAILY->value)
        ->call('save')
        ->assertHasNoFormErrors();
    expect($task->refresh()->concurrent_tasks)->toBe([]);
});
