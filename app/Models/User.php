<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'role',
        'telefono',
        'phone',
        'ci',
        'estado',
        'status',
        'foto_path',
        'main_course_id',
        'main_subject_id',
    ];

    /**
     * Students assigned to this user as a parent.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Student::class, $this->getParentUserForeignKey());
    }

    /**
     * Students assigned to this user as a teacher.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, $this->getTeacherUserForeignKey());
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'teacher_user_id');
    }

    /**
     * Student account linked to this user.
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(Student::class, $this->getStudentUserForeignKey());
    }

    /**
     * Students linked through the family members relation.
     */
    public function familyStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_family_members', 'user_id', 'student_id')
            ->withPivot(['relation_type', 'is_primary', 'can_view', 'can_edit', 'can_receive_reports', 'active'])
            ->withTimestamps();
    }

    public function hijos(): HasMany
    {
        return $this->children();
    }

    public function alumnos(): HasMany
    {
        return $this->students();
    }

    public function estudiantePerfil(): HasOne
    {
        return $this->studentProfile();
    }

    public function estudiantesComoFamiliar(): BelongsToMany
    {
        return $this->familyStudents();
    }

    /**
     * Role check helpers.
     */
    public function getRolAttribute(): ?string
    {
        return $this->attributes['role'] ?? $this->attributes['rol'] ?? null;
    }

    public function setRolAttribute(string $value): void
    {
        $this->writeCompatAttribute('role', 'rol', $value);
    }

    public function setRoleAttribute(string $value): void
    {
        $this->writeCompatAttribute('role', 'rol', $value);
    }

    public function getRoleAttribute(): ?string
    {
        return $this->getRolAttribute();
    }

    public function getPhoneAttribute($value)
    {
        return $this->attributes['phone'] ?? $this->attributes['telefono'] ?? $value;
    }

    public function setPhoneAttribute($value): void
    {
        $this->writeCompatAttribute('phone', 'telefono', $value);
    }

    public function getTelefonoAttribute($value)
    {
        return $this->getPhoneAttribute($value);
    }

    public function setTelefonoAttribute($value): void
    {
        $this->setPhoneAttribute($value);
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

    private function getParentUserForeignKey(): string
    {
        return $this->hasParentUserForeignKey('parent_user_id') ? 'parent_user_id' : 'usuario_id';
    }

    private function getTeacherUserForeignKey(): string
    {
        return $this->hasParentUserForeignKey('teacher_user_id') ? 'teacher_user_id' : 'profesor_id';
    }

    private function getStudentUserForeignKey(): string
    {
        return $this->hasParentUserForeignKey('student_user_id') ? 'student_user_id' : 'user_id';
    }

    private function hasParentUserForeignKey(string $column): bool
    {
        try {
            return Schema::hasColumn('students', $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAcademic(): bool
    {
        return $this->role === 'academic';
    }

    public function isProfesor(): bool
    {
        return $this->role === 'profesor';
    }

    public function isPadre(): bool
    {
        return $this->role === 'padre';
    }

    public function isMadre(): bool
    {
        return $this->role === 'madre';
    }

    public function isTutor(): bool
    {
        return $this->role === 'tutor';
    }

    public function isEstudiante(): bool
    {
        return $this->role === 'estudiante';
    }

    public function mainCourse()
    {
        return $this->belongsTo(Course::class, 'main_course_id');
    }

    public function mainSubject()
    {
        return $this->belongsTo(Subject::class, 'main_subject_id');
    }

    public function isResponsibleRole(): bool
    {
        return in_array($this->rol, ['padre', 'madre', 'tutor'], true);
    }

    public function scopeOfRole($query, array|string $roles)
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $table = $query->getModel()->getTable();

        return $query->where(function ($query) use ($roles, $table) {
            $query->whereIn("{$table}.role", $roles);

            if (Schema::hasColumn($table, 'rol')) {
                $query->orWhereIn("{$table}.rol", $roles);
            }
        });
    }

    public function scopeActive($query, string $status = 'activo')
    {
        $table = $query->getModel()->getTable();

        return $query->where(function ($query) use ($table, $status) {
            if (Schema::hasColumn($table, 'status')) {
                $query->where("{$table}.status", $status);
            }

            if (Schema::hasColumn($table, 'estado')) {
                $query->orWhere("{$table}.estado", $status);
            }
        });
    }

    public function isFamilyOf(Student $student): bool
    {
        $parentForeignKey = $student->getParentUserForeignKey();

        if ($this->id === $student->getAttribute($parentForeignKey)) {
            return true;
        }

        return $this->familyStudents()->where('students.id', $student->id)->exists();
    }

    public function canViewStudent(Student $student): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isProfesor()) {
            $teacherForeignKey = $student->getTeacherUserForeignKey();

            return $this->id === $student->getAttribute($teacherForeignKey);
        }

        if ($this->isEstudiante()) {
            $studentUserForeignKey = $student->getStudentUserForeignKey();

            return $this->id === $student->getAttribute($studentUserForeignKey);
        }

        return $this->isFamilyOf($student);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
