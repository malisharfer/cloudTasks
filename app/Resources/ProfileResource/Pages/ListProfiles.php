<?php

namespace App\Resources\ProfileResource\Pages;

use App\Resources\ProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProfiles extends ListRecords
{
    protected static string $resource = ProfileResource::class;

    protected static ?string $title = 'My profile';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('My profile');
    }

    public function getTitle(): string
    {
        return __('My profile');
    }
}
