<?php

namespace App\Resources\ConstraintResource\Pages;

use App\Resources\ConstraintResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConstraint extends EditRecord
{
    protected static string $resource = ConstraintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
