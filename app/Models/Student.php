<?php

namespace App\Models;

use App\Models\Group;
use App\Models\Interaction;
use App\Models\Result;
use App\Models\SchoolClass;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Grade;
use App\Models\Attendance as StudentAttendance;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'course',
        'course_id',
        'parallel_id',
        'parent_user_id',
        'teacher_user_id',
        'student_user_id',
        'age',
        'gender',
        'level',
        'registration_date',
        'status',
        'score',
        'writing_score',
        'exam_score',
        'attendance',
        'foto_path',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'score' => 'integer',
        'writing_score' => 'integer',
        'exam_score' => 'integer',
        'attendance' => 'integer',
        'parent_user_id' => 'integer',
        'teacher_user_id' => 'integer',
        'student_user_id' => 'integer',
        'course_id' => 'integer',
        'parallel_id' => 'integer',
    ];

    public function tutors(): BelongsToMany
    {
        return $this->belongsToMany(Tutor::class, 'student_tutor', 'student_id', 'tutor_id')
            ->withPivot('relationship')
            ->withTimestamps();
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_student', 'student_id', 'class_id')
            ->withTimestamps();
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_student', 'student_id', 'group_id')
            ->withTimestamps();
    }

    public function parallel(): BelongsTo
    {
        return $this->belongsTo(Parallel::class, 'parallel_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(User::class, $this->getParentUserForeignKey());
    }

    public function parent(): BelongsTo
    {
        return $this->padre();
    }

    public function profesor(): BelongsTo
    {
        return $this->belongsTo(User::class, $this->getTeacherUserForeignKey());
    }

    public function teacher(): BelongsTo
    {
        return $this->profesor();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(User::class, $this->getStudentUserForeignKey());
    }

    public function familiares(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_family_members', 'student_id', 'user_id')
            ->withPivot([
                'relation_type',
                'is_primary',
                'can_view',
                'can_edit',
                'can_receive_reports',
                'active',
            ])
            ->withTimestamps();
    }

    public function familyMembers(): BelongsToMany
    {
        return $this->familiares();
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'student_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'student_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'student_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class, 'student_id');
    }

    public function getNombreAttribute($value)
    {
        return $this->attributes['name'] ?? $value;
    }

    public function setNombreAttribute($value): void
    {
        $this->attributes['name'] = $value;
    }

    public function getNameAttribute($value)
    {
        return $this->attributes['name'] ?? $value;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = $value;
    }

    public function getFullNameAttribute($value)
    {
        return $this->getNombreAttribute($value);
    }

    public function setFullNameAttribute($value): void
    {
        $this->setNombreAttribute($value);
    }

    public function getParentUserIdAttribute($value)
    {
        return $this->attributes['parent_user_id'] ?? $this->attributes['usuario_id'] ?? $value;
    }

    public function setParentUserIdAttribute($value): void
    {
        $this->writeCompatAttribute('parent_user_id', 'usuario_id', $value);
    }

    public function getUsuarioIdAttribute($value)
    {
        return $this->getParentUserIdAttribute($value);
    }

    public function setUsuarioIdAttribute($value): void
    {
        $this->setParentUserIdAttribute($value);
    }

    public function getTeacherUserIdAttribute($value)
    {
        return $this->attributes['teacher_user_id'] ?? $this->attributes['profesor_id'] ?? $value;
    }

    public function setTeacherUserIdAttribute($value): void
    {
        $this->writeCompatAttribute('teacher_user_id', 'profesor_id', $value);
    }

    public function getProfesorIdAttribute($value)
    {
        return $this->getTeacherUserIdAttribute($value);
    }

    public function setProfesorIdAttribute($value): void
    {
        $this->setTeacherUserIdAttribute($value);
    }

    public function getStudentUserIdAttribute($value)
    {
        return $this->attributes['student_user_id'] ?? $this->attributes['user_id'] ?? $value;
    }

    public function setStudentUserIdAttribute($value): void
    {
        $this->writeCompatAttribute('student_user_id', 'user_id', $value);
    }

    public function getUserIdAttribute($value)
    {
        return $this->getStudentUserIdAttribute($value);
    }

    public function setUserIdAttribute($value): void
    {
        $this->setStudentUserIdAttribute($value);
    }

    public function getRegistrationDateAttribute($value)
    {
        return $this->attributes['registration_date'] ?? $this->attributes['fecha_registro'] ?? $value;
    }

    public function setRegistrationDateAttribute($value): void
    {
        $this->writeCompatAttribute('registration_date', 'fecha_registro', $value);
    }

    public function getFechaRegistroAttribute($value)
    {
        return $this->getRegistrationDateAttribute($value);
    }

    public function setFechaRegistroAttribute($value): void
    {
        $this->setRegistrationDateAttribute($value);
    }

    public function getWritingScoreAttribute($value)
    {
        return $this->attributes['writing_score'] ?? $this->attributes['nota_escritura'] ?? $value;
    }

    public function setWritingScoreAttribute($value): void
    {
        $this->writeCompatAttribute('writing_score', 'nota_escritura', $value);
    }

    public function getNotaEscrituraAttribute($value)
    {
        return $this->getWritingScoreAttribute($value);
    }

    public function setNotaEscrituraAttribute($value): void
    {
        $this->setWritingScoreAttribute($value);
    }

    public function getExamScoreAttribute($value)
    {
        return $this->attributes['exam_score'] ?? $this->attributes['nota_examen'] ?? $value;
    }

    public function setExamScoreAttribute($value): void
    {
        $this->writeCompatAttribute('exam_score', 'nota_examen', $value);
    }

    public function getNotaExamenAttribute($value)
    {
        return $this->getExamScoreAttribute($value);
    }

    public function setNotaExamenAttribute($value): void
    {
        $this->setExamScoreAttribute($value);
    }

    public function getAgeAttribute($value)
    {
        return $this->attributes['age'] ?? $this->attributes['edad'] ?? $value;
    }

    public function setAgeAttribute($value): void
    {
        $this->writeCompatAttribute('age', 'edad', $value);
    }

    public function getEdadAttribute($value)
    {
        return $this->getAgeAttribute($value);
    }

    public function setEdadAttribute($value): void
    {
        $this->setAgeAttribute($value);
    }

    public function getGenderAttribute($value)
    {
        return $this->attributes['gender'] ?? $this->attributes['genero'] ?? $value;
    }

    public function setGenderAttribute($value): void
    {
        $this->writeCompatAttribute('gender', 'genero', $value);
    }

    public function getGeneroAttribute($value)
    {
        return $this->getGenderAttribute($value);
    }

    public function setGeneroAttribute($value): void
    {
        $this->setGenderAttribute($value);
    }

    public function getLevelAttribute($value)
    {
        return $this->attributes['level'] ?? $this->attributes['nivel'] ?? $value;
    }

    public function setLevelAttribute($value): void
    {
        $this->writeCompatAttribute('level', 'nivel', $value);
    }

    public function getNivelAttribute($value)
    {
        return $this->getLevelAttribute($value);
    }

    public function setNivelAttribute($value): void
    {
        $this->setLevelAttribute($value);
    }

    public function getStatusAttribute($value)
    {
        return $this->attributes['status'] ?? $this->attributes['estado'] ?? $value;
    }

    public function setStatusAttribute($value): void
    {
        $this->writeCompatAttribute('status', 'estado', $value);
    }

    public function getEstadoAttribute($value)
    {
        return $this->getStatusAttribute($value);
    }

    public function setEstadoAttribute($value): void
    {
        $this->setStatusAttribute($value);
    }

    public function getScoreAttribute($value)
    {
        return $this->attributes['score'] ?? $this->attributes['puntaje'] ?? $value;
    }

    public function setScoreAttribute($value): void
    {
        $this->writeCompatAttribute('score', 'puntaje', $value);
    }

    public function getPuntajeAttribute($value)
    {
        return $this->getScoreAttribute($value);
    }

    public function setPuntajeAttribute($value): void
    {
        $this->setScoreAttribute($value);
    }

    public function getAttendanceAttribute($value)
    {
        return $this->attributes['attendance'] ?? $this->attributes['asistencia'] ?? $value;
    }

    public function setAttendanceAttribute($value): void
    {
        $this->writeCompatAttribute('attendance', 'asistencia', $value);
    }

    public function getAsistenciaAttribute($value)
    {
        return $this->getAttendanceAttribute($value);
    }

    public function setAsistenciaAttribute($value): void
    {
        $this->setAttendanceAttribute($value);
    }

    private function writeCompatAttribute(string $englishColumn, string $legacyColumn, $value): void
    {
        $englishExists = $this->hasColumn($englishColumn);
        $legacyExists = $this->hasColumn($legacyColumn);

        if ($englishExists || ! $legacyExists) {
            $this->attributes[$englishColumn] = $value;

            return;
        }

        if ($legacyExists) {
            $this->attributes[$legacyColumn] = $value;
        }
    }

    private function hasColumn(string $column): bool
    {
        try {
            return Schema::hasColumn($this->getTable(), $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getParentUserForeignKey(): string
    {
        return $this->hasColumn('parent_user_id') ? 'parent_user_id' : 'usuario_id';
    }

    public function getTeacherUserForeignKey(): string
    {
        return $this->hasColumn('teacher_user_id') ? 'teacher_user_id' : 'profesor_id';
    }

    public function getStudentUserForeignKey(): string
    {
        return $this->hasColumn('student_user_id') ? 'student_user_id' : 'user_id';
    }

    public function getPromedioAttribute(): int
    {
        $values = array_filter([
            $this->puntaje,
            $this->nota_escritura,
            $this->nota_examen,
        ], function ($value) {
            return $value !== null;
        });

        if (empty($values)) {
            return 0;
        }

        return (int) round(array_sum($values) / count($values));
    }
}
