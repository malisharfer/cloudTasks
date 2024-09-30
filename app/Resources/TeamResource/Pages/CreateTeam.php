<?php

namespace App\Resources\TeamResource\Pages;

use App\Models\Department;
use App\Models\Soldier;
use App\Models\Team;
use App\Models\User;
use App\Resources\TeamResource;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    public ?Collection $teams = null;

    public ?Collection $departments = null;

    protected function beforeCreate(): void
    {
        $this->teams = Team::where('commander_id', $this->data['commander_id'])->get();
        $this->departments = Department::where('commander_id', $this->data['commander_id'])->get();
        if ($this->teams->isNotEmpty() || $this->departments->isNotEmpty()) {
            Notification::make()
                ->title(__('Save team'))
                ->persistent()
                ->body(__('The commander you selected is already registered as a commander. His selection will leave his soldiers without a commander. Are you sure?'))
                ->actions([
                    Action::make(__('View ').($this->teams->isNotEmpty() ? __('Team') : __('Department')))
                        ->button()
                        ->url(
                            fn () => $this->teams->isNotEmpty() ?
                            route('filament.app.resources.teams.index', ['commander_id' => $this->data['commander_id']]) :
                            route('filament.app.resources.departments.index', ['commander_id' => $this->data['commander_id']])

                        ),
                    Action::make(__('Confirm'))
                        ->button()
                        ->emit('confirmCreate'),
                    Action::make(__('Cancel'))
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
                Team::where('commander_id', $this->data['commander_id'])
                    ->update(['commander_id' => null]);
            }
            if ($this->departments->isNotEmpty()) {
                $user = User::where('userable_id', $this->data['commander_id'])->first();
                $user->removeRole('department-commander');

                Department::where('commander_id', $this->data['commander_id'])
                    ->update(['commander_id' => null]);
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
