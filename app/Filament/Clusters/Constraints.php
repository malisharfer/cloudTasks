<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Constraints extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Constraints');
    }

    public static function getClusterBreadcrumb(): string
    {
        return __('Constraints');
    }
}
