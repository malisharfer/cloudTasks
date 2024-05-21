<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Enums\Requests\ServiceType;
use App\Filament\Resources\RequestResource;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRequest extends CreateRecord
{
    protected static string $resource = RequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $username = UserResource::getUserFromAzure()->name;
        $data['submit_username'] = $username;
        $data['status'] = 'new';
        if (! $data['expiration_date']) {
            $data['expiration_date'] = $data['service_type'] === ServiceType::Regular->value ?
                now()->addYear() : now()->addMonth(6);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
