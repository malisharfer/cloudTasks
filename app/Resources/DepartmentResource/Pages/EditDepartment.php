<?php

namespace App\Resources\DepartmentResource\Pages;

use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Resources\DepartmentResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Throwable;

class EditDepartment extends EditRecord
{
    protected static string $resource = DepartmentResource::class;

    protected function beforeSave(): void
    {
        $teams = Team::where('commander_id', $this->data['commander_id'])->get();
        $departments = Department::where('commander_id', $this->data['commander_id'])->get();
        if ($teams->isNotEmpty() || $departments->isNotEmpty()) {
            DepartmentResource::checkCommander($teams, $departments, $this->data);
            $this->halt();
        }
    }

    public function confirmCreate($teams, $departments): void
    {
        DepartmentResource::confirm($teams, $departments, $this->data['commander_id']);
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

    protected function afterSave()
    {
        $this->assignRoles();
    }

    protected function assignRoles()
    {
        $user = User::where('userable_id', $this->record->commander_id)->first();
        $user?->assignRole('department-commander');
    }
}
