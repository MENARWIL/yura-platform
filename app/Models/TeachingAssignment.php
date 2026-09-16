<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeachingAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_user_id',
        'subject_id',
        'parallel_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function parallel(): BelongsTo
    {
        return $this->belongsTo(Parallel::class);
    }
}