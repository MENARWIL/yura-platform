<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parallel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'course_id',
        'max_students',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function studentCount(): int
    {
        return $this->students()->count();
    }

    public function isFull(): bool
    {
        return $this->studentCount() >= $this->max_students;
    }
}
