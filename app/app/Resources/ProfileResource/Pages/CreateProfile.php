<?php

namespace App\Resources\ProfileResource\Pages;

use App\Resources\ProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProfile extends CreateRecord
{
    protected static string $resource = ProfileResource::class;
}
