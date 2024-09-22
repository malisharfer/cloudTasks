<?php

namespace App\Resources;

use App\Filament\Clusters\Shifts;
use App\Models\Shift;
use App\Resources\MyShiftResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class MyShiftResource extends Resource
{
    protected static ?string $cluster = Shifts::class;

    protected static ?string $model = Shift::class;

    protected static ?string $label = 'My Shifts';

    protected static ?string $slug = 'my-shifts';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

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
