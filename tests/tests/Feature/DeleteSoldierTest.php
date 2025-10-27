<?php

use App\Models\Department;
use App\Models\Shift;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Resources\SoldierResource\Pages\ListSoldiers;
use Database\Seeders\PermissionSeeder;
use Filament\Tables\Actions\DeleteAction;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should delete the user', function () {
    $soldier = Soldier::factory()->create();
    $user = User::factory()->create([
        'userable_id' => $soldier->id,
    ]);
    livewire(ListSoldiers::class)
        ->callTableAction(DeleteAction::class, $soldier);

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

it('should unassign the soldiers shift', function () {
    $soldier = Soldier::factory()->create();
    User::factory()->create([
        'userable_id' => $soldier->id,
    ]);
    $shift = Shift::factory()->create(['soldier_id' => $soldier->id]);
    livewire(ListSoldiers::class)
        ->callTableAction(DeleteAction::class, $soldier);

    $this->assertDatabaseHas('shifts', [
        'id' => $shift->id,
        'soldier_id' => null,
    ]);
});

it('should unassign the department commander', function () {
    $soldier = Soldier::factory()->create();
    User::factory()->create([
        'userable_id' => $soldier->id,
    ]);
    $department = Department::factory()->create(['commander_id' => $soldier->id]);
    livewire(ListSoldiers::class)
        ->callTableAction(DeleteAction::class, $soldier);

    $this->assertDatabaseHas('departments', [
        'id' => $department->id,
        'commander_id' => null,
    ]);
});

it('should unassign the team commander', function () {
    $soldier = Soldier::factory()->create();
    User::factory()->create([
        'userable_id' => $soldier->id,
    ]);
    $team = Team::factory()->create(['commander_id' => $soldier->id]);
    livewire(ListSoldiers::class)
        ->callTableAction(DeleteAction::class, $soldier);

    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'commander_id' => null,
    ]);
});
