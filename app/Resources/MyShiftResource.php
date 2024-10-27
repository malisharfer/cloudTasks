<?php

namespace App\Resources;

use App\Models\Shift;
use App\Resources\MyShiftResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class MyShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $label = 'My Shifts';

    protected static ?string $slug = 'my-shifts';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string
    {
        return __('Shifts');
    }

    public static function getModelLabel(): string
    {
        return __('My Shift');
    }

    public static function getPluralModelLabel(): string
    {
        return __('My Shifts');
    }

    public static function table(Table $table): Table
    {
        return $table->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyShift::route('/'),
            'create' => Pages\CreateMyShift::route('/create'),
            'edit' => Pages\EditMyShift::route('/{record}/edit'),
        ];
    }
}
