<?php

namespace App\Resources;

use App\Models\Constraint;
use App\Resources\ConstraintResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class ConstraintResource extends Resource
{
    protected static ?string $model = Constraint::class;

    protected static ?string $label = 'My Soldiers Constraint';

    protected static ?string $slug = 'my-soldiers-constraint';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): string
    {
        return __('Constraints');
    }

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
        return auth()->user()->getRoleNames()->count() > 1;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->paginated(false)
            ->emptyState(fn () => null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConstraints::route('/'),
        ];
    }
}
