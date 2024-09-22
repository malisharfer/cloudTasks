<?php

namespace App\Resources\MyShiftResource\Pages;

use App\Resources\MyShiftResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMyShift extends EditRecord
{
    protected static string $resource = MyShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
