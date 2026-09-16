<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    use HasFactory;

    protected $table = 'grades';

    protected $fillable = [
        'student_id',
        'subject_id',
        'score',
        'type',
        'observations',
        'status',
        'activity_category',
        'is_robot_activity',
        'robot_activity_id',
        'quarter',
        'activity_number',
    ];

    protected $casts = [
        'score' => 'float',
        'is_robot_activity' => 'boolean',
        'quarter' => 'integer',
        'activity_number' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function getSubjectDisplayNameAttribute(): string
    {
        if ($this->type === 'activity') {
            return [
                1 => 'Escritura de Quechua',
                2 => 'Redacción de un resumen en Quechua',
                3 => 'Lectura de libros en Quechua',
            ][$this->activity_number] ?? 'Actividad de Quechua';
        }

        return [
            'exam' => 'Comprensión lectora en Quechua',
            'robot' => 'Decir colores en Quechua',
        ][$this->type] ?? $this->subject?->name ?? 'Contenido de Quechua';
    }

    public function history(): HasMany
    {
        return $this->hasMany(GradeHistory::class, 'grade_id');
    }
}
