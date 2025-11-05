<?php

namespace App\Models;

use App\Traits\TrimsAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory, TrimsAttributes;

    protected $fillable = [
        'name',
    ];

    public function commander(): BelongsTo
    {
        return $this->belongsTo(Soldier::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Soldier::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Team $team) {
            self::removeCommanderRole($team->commander_id);
            self::unAssignMembers($team);
        });
    }

    protected static function removeCommanderRole($commanderId)
    {
        if ($commanderId) {
            $commander = Soldier::find($commanderId)->user;
            $commander->removeRole('team-commander');
        }
    }

    protected static function unAssignMembers(Team $team)
    {
        $memberIds = $team->members->pluck('id');
        Soldier::whereIn('id', $memberIds)->update(['team_id' => null]);
    }
}
