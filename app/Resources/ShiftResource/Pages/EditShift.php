<?php

namespace App\Resources\ShiftResource\Pages;

use App\Resources\ShiftResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShift extends EditRecord
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
