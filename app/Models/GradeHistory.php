<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeHistory extends Model
{
    use HasFactory;

    protected $table = 'grade_history';

    protected $fillable = [
        'grade_id',
        'user_id',
        'old_score',
        'new_score',
        'reason',
    ];

    protected $casts = [
        'old_score' => 'float',
        'new_score' => 'float',
    ];

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
