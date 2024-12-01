<?php

namespace App\Models;

use App\Casts\Integer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'start_hour',
        'duration',
        'parallel_weight',
        'type',
        'color',
        'is_alert',
        'is_weekend',
        'is_night',
        'department_name',
        'recurring',
    ];

    protected $casts = [
        'is_alert' => 'boolean',
        'is_weekend' => 'boolean',
        'is_night' => 'boolean',
        'parallel_weight' => Integer::class,
        'recurring' => 'array',
        'duration' => Integer::class,
    ];

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Task $task) {
            $shifts = Shift::where('task_id', $task->id)
                ->where('start_date', '>', now())
                ->get();
            $shifts->map(function (Shift $shift) {
                $shift->delete();
            });
        });
    }
}
