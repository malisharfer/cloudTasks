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
        'kind',
        'concurrent_tasks',
        'department_name',
        'recurring',
    ];

    protected $casts = [
        'parallel_weight' => Integer::class,
        'concurrent_tasks' => 'array',
        'recurring' => 'array',
        'duration' => Integer::class,
    ];

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    protected static function booted(): void
    {
        static::deleting(fn (Task $task) => Shift::where('task_id', $task->id)
            ->where('start_date', '>', now())
            ->delete()
        );
    }
}
