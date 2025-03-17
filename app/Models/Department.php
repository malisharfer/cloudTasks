<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function commander(): BelongsTo
    {
        return $this->belongsTo(Soldier::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Department $department) {
            self::removeCommanderRole($department->commander_id);
            self::unAssignTeams($department);
        });
    }

    protected static function removeCommanderRole($commanderId)
    {
        if ($commanderId) {
            $commander = Soldier::find($commanderId)->user;
            $commander->removeRole('department-commander');
        }
    }

    protected static function unAssignTeams(Department $department)
    {
        collect($department->teams)->map(function (Team $team) {
            $team->department_id = null;
            $team->save();
        });
    }
}
