<?php

namespace App\Resources\TeamResource\Pages;

use App\Models\Department;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Resources\TeamResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Throwable;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected function beforeCreate(): void
    {
        $name = Team::where('name', '=', $this->data['name'])->pluck('name');
        if ($name->contains($this->data['name'])) {
            Notification::make()
                ->warning()
                ->title(__('This name already exists in the system!'))
                ->body(__('Add an identifier to the name so that it is not the same as another name.'))
                ->persistent()
                ->send();
            $this->halt();
        }

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
        $this->attachSoldiers();
        $this->assignRoles();
    }

    protected function attachSoldiers(): void
    {
        $teamId = Team::latest()->pluck('id')->first();

        if ($teamId) {
            Soldier::whereIn('id', $this->data['members'])
                ->update(['team_id' => $teamId]);
        }
    }

    protected function assignRoles()
    {
        Soldier::where('id', $this->record->commander_id)->update(['team_id' => null]);
        $user = User::where('userable_id', $this->record->commander_id)->first();
        $user->assignRole('team-commander');
    }
}