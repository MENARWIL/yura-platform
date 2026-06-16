<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $table = 'estudiantes';

    protected $appends = ['promedio'];

    protected $fillable = [
        'usuario_id',
        'user_id',
        'profesor_id',
        'nombre',
        'edad',
        'genero',
        'nivel',
        'fecha_registro',
        'estado',
        'estado_aprobacion',
        'puntaje',
        'nota_escritura',
        'nota_examen',
        'asistencia',
        'foto_path',
    ];

    /**
     * Casts for Student attributes.
     */
    protected $casts = [
        'estado_aprobacion' => 'string',
    ];

    /**
     * Calculate a total average progress.
     */
    public function getPromedioAttribute(): float
    {
        $p = (float) ($this->puntaje ?? 0);
        $e = (float) ($this->nota_escritura ?? 0);
        $ex = (float) ($this->nota_examen ?? 0);

        return round(($p + $e + $ex) / 3, 2);
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the teacher assigned to the student.
     */
    public function profesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profesor_id');
    }

    public function familiares(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_family_members', 'student_id', 'user_id')
            ->withPivot(['relation_type', 'is_primary', 'can_view', 'can_edit', 'can_receive_reports', 'active'])
            ->withTimestamps();
    }
}
