<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Shifts extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Shifts');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('Shifts');
    }
}
