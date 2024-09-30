<?php

namespace App\Resources;

use App\Filament\Clusters\Constraints;
use App\Models\Constraint;
use App\Resources\ConstraintResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class ConstraintResource extends Resource
{
    protected static ?string $cluster = Constraints::class;

    protected static ?string $model = Constraint::class;

    protected static ?string $label = 'My Soldiers Constraint';

    protected static ?string $slug = 'my-soldiers-constraint';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static function getModelLabel(): string
    {
        return __('My Soldiers Constraint');
    }

    public static function getPluralModelLabel(): string
    {
        return __('My Soldiers Constraints');
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
            'index' => Pages\ListConstraints::route('/'),
        ];
    }
}
