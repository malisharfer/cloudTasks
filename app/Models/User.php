<?php

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements HasName,FilamentUser
{
    use HasFactory, HasPermissions, HasRoles, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function userable(): MorphTo
    {
        return $this->morphTo();
    }

    public function displayName(): Attribute
    {
        return Attribute::get(fn () => $this->first_name.' '.$this->last_name);
    }

    public function getFilamentName(): string
    {
        return $this->displayName;
    }
    public function viewAny(User $user): bool
    {
        return $user->hasRole('manager');
    }
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
