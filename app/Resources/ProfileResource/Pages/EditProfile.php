<?php

namespace App\Resources\ProfileResource\Pages;

use App\Resources\ProfileResource;
use Filament\Resources\Pages\EditRecord;

class EditProfile extends EditRecord
{
    protected static string $resource = ProfileResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getTitle(): string
    {
        return __('My profile');
    }
}
