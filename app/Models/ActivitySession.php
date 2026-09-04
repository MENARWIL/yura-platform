<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivitySession extends Model
{
    use HasFactory;

    protected $table = 'activity_sessions';

    protected $fillable = [
        'activity_id',
        'class_id',
        'status',
        'duration_limit',
        'start_time',
        'end_time',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class, 'session_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'session_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'session_id');
    }
}
