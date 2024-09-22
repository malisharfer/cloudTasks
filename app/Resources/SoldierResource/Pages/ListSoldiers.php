<?php

namespace App\Resources\SoldierResource\Pages;

use App\Resources\SoldierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSoldiers extends ListRecords
{
    protected static string $resource = SoldierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
