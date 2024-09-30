<?php

namespace App\Resources;

use App\Filament\Clusters\Shifts;
use App\Models\Shift;
use App\Resources\ShiftResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class ShiftResource extends Resource
{
    protected static ?string $cluster = Shifts::class;

    protected static ?string $model = Shift::class;

    protected static ?string $label = 'My Soldiers Shifts';

    protected static ?string $slug = 'my-soldiers-shifts';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function getModelLabel(): string
    {
        return __('My Soldiers Shift');
    }

    public static function getPluralModelLabel(): string
    {
        return __('My Soldiers Shifts');
    }

    public static function canAccess(): bool
    {
        return current(array_diff(collect(auth()->user()->getRoleNames())->toArray(), ['soldier'])) ? true : false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShifts::route('/'),
        ];
    }
}
