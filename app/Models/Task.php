<?php

namespace App\Models;

use App\Casts\Integer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

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
        'recurrence',
    ];

    protected $casts = [
        'is_alert' => 'boolean',
        'is_weekend' => 'boolean',
        'is_night' => 'boolean',
        'parallel_weight' => Integer::class,
        'recurrence' => 'array',
        'duration' => Integer::class,
    ];

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }
}
