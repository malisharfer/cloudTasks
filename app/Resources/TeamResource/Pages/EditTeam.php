<?php

namespace App\Resources\TeamResource\Pages;

use App\Models\Department;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Resources\TeamResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Throwable;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function beforeSave(): void
    {
        if ($this->data['commander_id'] !== $this->record->commander_id) {
            if ($this->record->commander_id !== null) {
                $user = User::where('userable_id', $this->record->commander_id)->first();
                $user->removeRole('team-commander');
            }
            $teams = Team::where('commander_id', $this->data['commander_id'])->get();
            $departments = Department::where('commander_id', $this->data['commander_id'])->get();
            if ($teams->isNotEmpty() || $departments->isNotEmpty()) {
                TeamResource::checkCommander($teams, $departments, $this->data);
                $this->halt();
            }
        }
    }

    public function confirmCreate($teams, $departments): void
    {
        TeamResource::confirm($teams, $departments, $this->data['commander_id']);
        try {
            $this->beginDatabaseTransaction();
            $data = $this->form->getState();
            $this->handleRecordUpdate($this->getRecord(), $data);
            $this->callHook('afterSave');
            $this->commitDatabaseTransaction();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();
            throw $exception;
        }
        $this->rememberData();
        $this->getSavedNotification()?->send();
        $redirectUrl = $this->getRedirectUrl();
        $this->redirect($redirectUrl);
    }

    protected $listeners = [
        'confirmCreate' => 'confirmCreate',
    ];

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();

        return $resource::getUrl('index');
    }

    protected function afterSave(): void
    {
        $this->assignRoles();
        $this->unAssignMembers();
        $this->assignMembers();
    }

    protected function assignRoles()
    {
        Soldier::where('id', $this->record->commander_id)->update(['team_id' => null]);
        User::where('userable_id', $this->record->commander_id)->first()->assignRole('team-commander');
    }

    protected function unAssignMembers()
    {
        $members = $this->data['members'] ? collect($this->data['members']) : collect();
        Soldier::where(function ($query) use ($members) {
            $query->where('team_id', $this->record->id)
                ->whereNotIn('id', $members);
        })->update(['team_id' => null]);
    }

    protected function assignMembers()
    {
        $members = $this->data['members'] ? collect($this->data['members']) : collect();
        Soldier::whereIn('id', $members)
            ->update(['team_id' => $this->record->id]);
    }
}
