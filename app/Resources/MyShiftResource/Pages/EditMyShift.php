<?php

namespace App\Resources\MyShiftResource\Pages;

use App\Resources\MyShiftResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMyShift extends EditRecord
{
    protected static string $resource = MyShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label(__('Delete')),
        ];
    }
}
