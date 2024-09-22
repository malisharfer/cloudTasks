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
}
