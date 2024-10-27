<?php

namespace App\Resources\TeamResource\Pages;

use App\Models\Department;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Resources\TeamResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Throwable;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected function beforeCreate(): void
    {
        $teams = Team::where('commander_id', $this->data['commander_id'])->get();
        $departments = Department::where('commander_id', $this->data['commander_id'])->get();
        if ($teams->isNotEmpty() || $departments->isNotEmpty()) {
            TeamResource::checkCommander($teams, $departments, $this->data);
            $this->halt();
        }
    }

    public function confirmCreate($teams, $departments): void
    {
        TeamResource::confirm($teams, $departments, $this->data['commander_id']);
        try {
            $this->beginDatabaseTransaction();
            $data = $this->form->getState();
            $this->record = $this->handleRecordCreation($data);
            $this->form->model($this->getRecord())->saveRelationships();
            $this->callHook('afterCreate');
            $this->commitDatabaseTransaction();
            $this->rememberData();
            $this->getCreatedNotification()?->send();
            $redirectUrl = $this->getRedirectUrl();
            $this->redirect($redirectUrl);
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }
    }

    protected $listeners = [
        'confirmCreate' => 'confirmCreate',
    ];

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();

        return $resource::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $this->attachCommander();
        $this->attachSoldiers();
        $this->assignRoles();
    }

    protected function attachCommander(): void
    {
        Soldier::where('id', $this->data['commander_id'])
            ->update(['team_id' => Team::latest()->pluck('id')->first()]);
    }

    protected function attachSoldiers(): void
    {
        collect($this->data['members'])->map(fn ($soldier_id) => Soldier::where('id', $soldier_id)
            ->update(['team_id' => Team::latest()->pluck('id')->first()]));
    }

    protected function assignRoles()
    {
        $user = User::where('userable_id', $this->record->commander_id)->first();
        $user->assignRole('team-commander');
    }
}
