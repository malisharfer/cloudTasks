<?php

use App\Models\Department;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Resources\DepartmentResource\Pages\ListDepartments;
use Database\Seeders\PermissionSeeder;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should unassign department-commander role from the commander', function () {
    $soldier = Soldier::factory()->create();
    $commander = User::factory()->create([
        'userable_id' => $soldier->id,
    ]);
    $department = Department::factory()->create(['commander_id' => $soldier->id]);
    $commander->assignRole('department-commander');
    livewire(ListDepartments::class)
        ->callTableAction(Filament\Tables\Actions\DeleteAction::class, $department);

    expect($commander->getRoleNames()->contains('department-commander'))->toBe(false);
});

it('should unassign teams', function () {
    $department = Department::factory()->create();
    $team = Team::factory()->create(['department_id' => $department->id]);

    livewire(ListDepartments::class)
        ->callTableAction(Filament\Tables\Actions\DeleteAction::class, $department);

    expect($team->refresh()->department_id)->toBeNull();
});
