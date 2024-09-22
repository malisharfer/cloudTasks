<?php

namespace App\Resources\SoldierResource\Pages;

use App\Resources\SoldierResource;
use Filament\Resources\Pages\EditRecord;

class EditSoldier extends EditRecord
{
    protected static string $resource = SoldierResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! $data['is_reservist']) {
            $data['reserve_dates'] = null;
            $data['next_reserve_dates'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
