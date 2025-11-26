<?php

use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Resources\TeamResource\Pages\ListTeams;
use Database\Seeders\PermissionSeeder;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this
        ->seed(PermissionSeeder::class)
        ->asUser('manager');
});

it('should unassign team-commander role from the commander', function () {
    $soldier = Soldier::factory()->create();
    $commander = User::factory()->create([
        'userable_id' => $soldier->id,
    ]);
    $team = Team::factory()->create(['commander_id' => $soldier->id]);
    $commander->assignRole('team-commander');
    livewire(ListTeams::class)
        ->callTableAction(Filament\Tables\Actions\DeleteAction::class, $team);

    expect($commander->getRoleNames()->contains('team-commander'))->toBe(false);
});

it('should unassign members', function () {
    $team = Team::factory()->create();
    $soldier = User::factory()->create([
        'userable_id' => Soldier::factory()->create(['team_id' => $team->id])->id,
    ]);
    livewire(ListTeams::class)
        ->callTableAction(Filament\Tables\Actions\DeleteAction::class, $team);
    expect($soldier->refresh()->team_id)->toBeNull();
});
