<?php

namespace App\Resources;

use App\Filament\Clusters\Constraints;
use App\Models\Constraint;
use App\Resources\MyConstraintResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class MyConstraintResource extends Resource
{
    protected static ?string $cluster = Constraints::class;

    protected static ?string $model = Constraint::class;

    protected static ?string $label = 'My Constraint';

    protected static ?string $slug = 'my-constraint';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static function getModelLabel(): string
    {
        return __('My Constraint');
    }

    public static function getPluralModelLabel(): string
    {
        return __('My Constraints');
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
            'index' => Pages\ListMyConstraints::route('/'),
        ];
    }
}
