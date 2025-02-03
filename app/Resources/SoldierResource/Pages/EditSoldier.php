<?php

namespace App\Resources\SoldierResource\Pages;

use App\Models\User;
use App\Resources\SoldierResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
class EditSoldier extends EditRecord
{
    protected static string $resource = SoldierResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['shifts_assignment'] = User::where('userable_id', $this->record->id)?->first()
            ? in_array('shifts-assignment', User::where('userable_id', $this->record->id)
                ->first()
                ->getRoleNames()
                ->toArray())
            : session()->get('shifts_assignment');


        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!$data['is_reservist']) {
            $data['reserve_dates'] = null;
            $data['next_reserve_dates'] = null;
        }

        return $data;
    }
    protected function beforeSave(): void
    {
        $userName = User::where('last_name', $this->data['user']['last_name'])
            ->where('first_name', $this->data['user']['first_name'])
            ->pluck('last_name', 'first_name');

        if (
            ($userName = $userName->get($this->data['user']['first_name']) == $this->data['user']['last_name']) &&
            $this->record->user->displayName !== $this->data['user']['first_name'] . ' ' . $this->data['user']['last_name']
        ) {
            Notification::make()
                ->warning()
                ->title(__('This name already exists in the system!'))
                ->body(__('Add identifier', [
                    'example' => ($this->data['user']['first_name'] . ' ' . $this->data['user']['last_name'] . '2'),
                ]))
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $user = $this->record->user;
        if ($user->getRoleNames()->isEmpty()) {
            $this->data['shifts_assignment'] == 1 ? $user->assignRole('soldier', 'shifts-assignment') : $user->assignRole('soldier');
        } else {
            $roles = $user->getRoleNames()->toArray();
            if ($this->data['shifts_assignment'] == 1 && !in_array('shifts-assignment', $roles)) {
                $user->assignRole('shifts-assignment');
            }
            if ($this->data['shifts_assignment'] == 0 && in_array('shifts-assignment', $roles)) {
                $user->removeRole('shifts-assignment');
            }
        }
    }
}
