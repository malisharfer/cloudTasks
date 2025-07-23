<?php

namespace App\Resources;

use App\Models\Shift;
use App\Resources\ShiftResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $label = 'All';

    protected static ?string $slug = 'all-shifts';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('Shifts');
    }

    public static function getModelLabel(): string
    {
        return __('All soldiers shift');
    }

    public static function getPluralModelLabel(): string
    {
        return __('All soldiers shifts');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShifts::route('/'),
        ];
    }
}
