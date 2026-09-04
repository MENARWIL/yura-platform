<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tutor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ci',
        'phone',
    ];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_tutor', 'tutor_id', 'student_id')
            ->withPivot('relationship')
            ->withTimestamps();
    }

    public function studentLinks(): BelongsToMany
    {
        return $this->students();
    }
}
