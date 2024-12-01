<?php

use App\Models\Task;
use App\Resources\TaskResource\Pages\ListTasks;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should filter the table in boolean filter', function () {
    $tasks = Task::factory()->count(5)->create();
    livewire::test(ListTasks::class)
        ->assertCanSeeTableRecords($tasks)
        ->filterTable('is_alert', true)
        ->assertCanSeeTableRecords($tasks->where('is_alert', true))
        ->assertCanNotSeeTableRecords($tasks->where('is_alert', false));
});

it('should filter the table in NumberFilter filter', function () {
    $tasks = Task::factory()->count(5)->create();
    $data = ['range_condition' => 'equal', 'range_equal' => 2.5];
    livewire::test(ListTasks::class)
        ->assertCanSeeTableRecords($tasks)
        ->filterTable('parallel_weight', $data)
        ->assertCanSeeTableRecords($tasks->where('parallel_weight', '=', 2.5))
        ->assertCanNotSeeTableRecords($tasks->where('parallel_weight', '!=', 2.5));
});
