<?php

namespace App\Resources\MyConstraintResource\Pages;

use App\Resources\MyConstraintResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMyConstraint extends EditRecord
{
    protected static string $resource = MyConstraintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label(__('Delete')),
        ];
    }
}
