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
        'telefono',
        'estado',
        'foto_path',
    ];

    /**
     * Students assigned to this user as a parent.
     */
    public function hijos(): HasMany
    {
        return $this->hasMany(Student::class, 'usuario_id');
    }

    /**
     * Students assigned to this user as a teacher.
     */
    public function alumnos(): HasMany
    {
        return $this->hasMany(Student::class, 'profesor_id');
    }

    /**
     * Student account linked to this user.
     */
    public function estudiantePerfil(): HasOne
    {
        return $this->hasOne(Student::class, 'user_id');
    }

    /**
     * Students linked through the family members relation.
     */
    public function estudiantesComoFamiliar(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_family_members', 'user_id', 'student_id')
            ->withPivot(['relation_type', 'is_primary', 'can_view', 'can_edit', 'can_receive_reports', 'active'])
            ->withTimestamps();
    }

    /**
     * Role check helpers.
     */
    public function isAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function isProfesor(): bool
    {
        return $this->rol === 'profesor';
    }

    public function isPadre(): bool
    {
        return $this->rol === 'padre';
    }

    public function isMadre(): bool
    {
        return $this->rol === 'madre';
    }

    public function isTutor(): bool
    {
        return $this->rol === 'tutor';
    }

    public function isEstudiante(): bool
    {
        return $this->rol === 'estudiante';
    }

    public function isResponsibleRole(): bool
    {
        return in_array($this->rol, ['padre', 'madre', 'tutor'], true);
    }

    public function isFamilyOf(Student $student): bool
    {
        if ($this->id === $student->usuario_id) {
            return true;
        }

        return $this->estudiantesComoFamiliar()->where('students.id', $student->id)->exists();
    }

    public function canViewStudent(Student $student): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isProfesor()) {
            return $this->id === $student->profesor_id;
        }

        if ($this->isEstudiante()) {
            return $this->id === $student->user_id;
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
