<?php

namespace App\Models;

use App\Casts\Integer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Soldier extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'gender',
        'is_permanent',
        'enlist_date',
        'course',
        'has_exemption',
        'max_shifts',
        'max_nights',
        'max_weekends',
        'max_alerts',
        'max_in_parallel',
        'capacity',
        'is_trainee',
        'is_mabat',
        'qualifications',
        'is_reservist',
        'last_reserve_dates',
        'reserve_dates',
        'next_reserve_dates',
        'constraints_limit',
        'not_thursday_evening',
        'not_sunday_morning',
    ];

    protected $casts = [
        'gender' => 'boolean',
        'is_permanent' => 'boolean',
        'enlist_date' => 'datetime:Y-m-d',
        'has_exemption' => 'boolean',
        'is_trainee' => 'boolean',
        'is_mabat' => 'boolean',
        'not_thursday_evening' => 'boolean',
        'not_sunday_morning' => 'boolean',
        'capacity' => Integer::class,
        'max_weekends' => Integer::class,
        'qualifications' => 'array',
        'is_reservist' => 'boolean',
        'last_reserve_dates' => 'array',
        'reserve_dates' => 'array',
        'next_reserve_dates' => 'array',
        'constraints_limit' => 'array',
    ];

    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }

    public function department(): HasOneThrough
    {
        return $this->hasOneThrough(Department::class, Team::class);
    }

    public function department_commander(): HasOne
    {
        return $this->hasOne(Department::class);
    }

    public function team_commander(): HasOne
    {
        return $this->hasOne(Team::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function constraints(): HasMany
    {
        return $this->hasMany(Constraint::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function updateReserveDays()
    {
        Soldier::where('is_reservist', true)->get()->map(function ($soldier) {
            $soldier['last_reserve_dates'] = array_merge($soldier['last_reserve_dates'], $soldier['reserve_dates']);
            $soldier['reserve_dates'] = $soldier['next_reserve_dates'];
            $soldier['next_reserve_dates'] = [];
            $soldier->save();
        });
    }

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($record) {
            $record->user->delete();
            Shift::where('soldier_id', $record->id)->update(['soldier_id' => null]);
            Constraint::where('soldier_id', $record->id)
                ->delete();
            Team::where('commander_id', $record->id)->update(['commander_id' => null]);
            Department::where('commander_id', $record->id)->update(['commander_id' => null]);
        });
    }
}
