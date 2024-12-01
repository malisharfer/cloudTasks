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
        'gender',
        'is_permanent',
        'enlist_date',
        'course',
        'has_exemption',
        'max_shifts',
        'max_nights',
        'max_weekends',
        'capacity',
        'is_trainee',
        'is_mabat',
        'qualifications',
        'is_reservist',
        'reserve_dates',
        'next_reserve_dates',
    ];

    protected $casts = [
        'gender' => 'boolean',
        'is_permanent' => 'boolean',
        'enlist_date' => 'datetime:Y-m-d',
        'has_exemption' => 'boolean',
        'is_trainee' => 'boolean',
        'is_mabat' => 'boolean',
        'capacity' => Integer::class,
        'max_nights' => Integer::class,
        'max_weekends' => Integer::class,
        'qualifications' => 'array',
        'is_reservist' => 'boolean',
        'reserve_dates' => 'array',
        'next_reserve_dates' => 'array',
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

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($record) {
            $record->user()->delete();
        });
    }
}