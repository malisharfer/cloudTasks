<?php

namespace App\Resources\DepartmentResource\Pages;

use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Resources\DepartmentResource;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class CreateDepartment extends CreateRecord
{
    protected static string $resource = DepartmentResource::class;

    public ?Collection $teams = null;

    public ?Collection $departments = null;

    protected function beforeCreate(): void
    {
        $this->teams = Team::where('commander_id', $this->data['commander_id'])->get();
        $this->departments = Department::where('commander_id', $this->data['commander_id'])->get();
        if ($this->teams->isNotEmpty() || $this->departments->isNotEmpty()) {
            Notification::make()
                ->title('Save department')
                ->persistent()
                ->body('The commander you selected is already registered as a commander. His selection will leave the '.($this->teams->isNotEmpty() ? 'team' : 'department').' without a commander. Are you sure?')
                ->actions([
                    Action::make('view '.($this->teams->isNotEmpty() ? 'team' : 'department'))
                        ->button()
                        ->url(
                            fn () => $this->teams->isNotEmpty() ?
                            route('filament.app.resources.teams.index', ['commander_id' => $this->data['commander_id']]) :
                            route('filament.app.resources.departments.index', ['commander_id' => $this->data['commander_id']])

                        ),
                    Action::make('confirm')
                        ->button()
                        ->emit('confirmCreate'),
                    Action::make('cancel')
                        ->button()
                        ->close(),
                ])
                ->send();
            $this->halt();
        }
    }

    public function confirmCreate(): void
    {
        try {
            if ($this->teams->isNotEmpty()) {
                $this->updateRole();
                $this->unAssignTeamCommander();
            }
            if ($this->departments->isNotEmpty()) {
                $this->unAssignDepartmentCommander();
            }
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

    protected function updateRole(): void
    {
        $user = User::where('userable_id', $this->data['commander_id'])->first();
        $user->assignRole('department-commander');
    }

    protected function unAssignTeamCommander(): void
    {
        Team::where('commander_id', $this->data['commander_id'])
            ->update(['commander_id' => null]);
    }

    protected function unAssignDepartmentCommander(): void
    {
        Department::where('commander_id', $this->data['commander_id'])
            ->update(['commander_id' => null]);
    }

    protected $listeners = [
        'confirmCreate' => 'confirmCreate',
    ];

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();

        return $resource::getUrl('index');
    }

    protected function afterCreate()
    {
        $this->assignRoles();
    }

    protected function assignRoles()
    {
        $user = User::where('userable_id', $this->record->commander_id)->first();
        $user->assignRole('department-commander');
    }
}
