<?php

namespace App\Resources;

use App\Models\Shift;
use App\Resources\ChartResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class ChartResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-s-chart-bar';

    public static function getModelLabel(): string
    {
        return __('Assignment charts');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Assignment charts');
    }

    public static function table(Table $table): Table
    {
        return $table->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCharts::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['manager', 'shifts-assignment', 'department-commander']);
    }
}
