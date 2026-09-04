<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFamilyMember extends Model
{
    protected $table = 'student_family_members';

    protected $fillable = [
        'student_id',
        'user_id',
        'relation_type',
        'is_primary',
        'can_view',
        'can_edit',
        'can_receive_reports',
        'active',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function familyMember(): BelongsTo
    {
        return $this->user();
    }

    public function studentRecord(): BelongsTo
    {
        return $this->student();
    }
}
