<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RobotActivity extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'teacher_user_id',
        'parallel_id',
        'subject_id',
        'quarter',
        'activity_number',
        'status',
        'payload',
        'result_payload',
        'result_hash',
        'started_at',
        'completed_at',
        'attempt_number',
        'current_attempt_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'result_payload' => 'array',
        'quarter' => 'integer',
        'activity_number' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'attempt_number' => 'integer',
    ];

    public function parallel(): BelongsTo
    {
        return $this->belongsTo(Parallel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(RobotActivityAttempt::class)->orderBy('attempt_number');
    }
}
