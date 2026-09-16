<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'subject_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'subject_id');
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }
}