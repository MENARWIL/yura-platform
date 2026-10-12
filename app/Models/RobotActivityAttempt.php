<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RobotActivityAttempt extends Model
{
    protected $fillable = [
        'robot_activity_id',
        'attempt_number',
        'attempt_id',
        'status',
        'result_payload',
        'result_hash',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'result_payload' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(RobotActivity::class, 'robot_activity_id');
    }
}
