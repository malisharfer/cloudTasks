<?php

namespace App\Enums\Requests;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum Status: string implements HasColor, HasIcon
{
    case New = 'new';
    case Approved = 'approved';
    case Denied = 'denied';
    
    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Approved => 'success',
            self::Denied => 'danger',
        };
    }
    
    public function getIcon(): string
    {
        return match ($this) {
            self::New => 'heroicon-o-plus-circle',
            self::Approved => 'heroicon-o-check-circle',
            self::Denied => 'heroicon-o-x-circle',
        };
    }
}