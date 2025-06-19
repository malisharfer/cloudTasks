<?php

use App\Models\Department;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Resources\SoldierResource;
use App\Resources\TeamResource\Pages\ListTeams;
use Database\Seeders\PermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should return teams by commander_id', function () {
    $team1 = Team::factory()->create([
        'commander_id' => User::factory()->create([
            'userable_id' => Soldier::factory()->create()->id,
        ])->userable_id,
    ]);

    Team::factory()->create([
        'commander_id' => User::factory()->create([
            'userable_id' => Soldier::factory()->create()->id,
        ])->userable_id,
    ]);

    Livewire::withQueryParams(['commander_id' => $team1->commander_id])
        ->test(ListTeams::class)
        ->assertCountTableRecords(1);
});

it('clicking on the View members button will redirect to soldiers routing', function () {
    $team = Team::factory()->create();

    Livewire::test(ListTeams::class)
        ->assertTableActionExists('View members')
        ->callTableAction('View members');
    $this->get(SoldierResource::getUrl('index', ['team_id' => $team->id]))->assertSuccessful();
});

it('clicking on the members button will open form to attach member to the team', function () {

    $team = Team::factory()->create();

    $user1 = User::factory()->create([
        'userable_id' => Soldier::factory()->create()->id,
    ]);

    $user2 = User::factory()->create([
        'userable_id' => Soldier::factory()->create()->id,
    ]);

    Livewire::test(ListTeams::class)
        ->callTableAction('members', $team, ['members' => [$user1->userable_id, $user2->userable_id]]);

    $this->assertDatabaseHas('soldiers', [
        'id' => $user1->userable_id,
        'team_id' => $team->id,
    ]);
    $this->assertDatabaseHas('soldiers', [
        'id' => $user2->userable_id,
        'team_id' => $team->id,
    ]);
});

it('should return teams by department_id', function () {
    $department = Department::factory()->create();
    $team1 = Team::factory()->create([
        'department_id' => $department->id,
    ]);

    Team::factory()->count(4)->create();

    Livewire::withQueryParams(['department_id' => $team1->department_id])
        ->test(ListTeams::class)
        ->assertCountTableRecords(1);
});
