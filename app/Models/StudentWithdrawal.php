<?php

namespace App\Models;

use App\Services\StudentReport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Baja de un estudiante (transferencia o retiro) con el respaldo de sus datos de ese momento. */
class StudentWithdrawal extends Model
{
    public const TYPES = ['transferencia' => 'Transferencia a otra unidad educativa', 'retiro' => 'Retiro', 'egreso' => 'Egresó (terminó el colegio)'];

    /** Palabra corta para listas y avisos. */
    public const SHORT = ['transferencia' => 'Transferido', 'retiro' => 'Retirado', 'egreso' => 'Egresado'];

    /** Motivos que se eligen en la ventana de baja; «transferencia» pide la unidad educativa de destino. */
    public const MOTIVES = [
        'transferencia' => 'Transferencia a otra unidad educativa',
        'abandono' => 'Abandono escolar',
        'otro' => 'Otro motivo',
    ];

    protected $fillable = ['student_id', 'type', 'destination', 'reason', 'withdrawn_on', 'user_id', 'snapshot', 'reverted_at', 'reverted_by'];

    protected $casts = [
        'withdrawn_on' => 'date',
        'snapshot' => 'array',
        'reverted_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function shortLabel(): string
    {
        return self::SHORT[$this->type] ?? 'Dado de baja';
    }

    /**
     * Copia de lo que tenía el estudiante al darse de baja: curso, responsables, notas y asistencia.
     * Con $yearId, las notas y la asistencia son las de esa gestión (al egresar: la que se acaba de cerrar).
     */
    public static function snapshotOf(Student $student, ?int $yearId = null): array
    {
        $ofYear = fn ($q) => $yearId
            ? $q->withoutGlobalScope('academic_year')->where($q->getModel()->getTable() . '.academic_year_id', $yearId)
            : $q;
        $student->load(['course', 'parallel', 'grades' => $ofYear, 'grades.subject', 'attendances' => $ofYear, 'familiares']);

        return [
            'student' => StudentReport::identity($student) + [
                'last_name' => $student->last_name,
                'second_last_name' => $student->second_last_name,
                'first_name' => $student->first_name,
                'age' => $student->age,
                'gender' => $student->gender,
                'level' => $student->level,
            ],
            'guardians' => $student->familiares->map(fn ($u) => [
                'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone,
                'relation' => $u->pivot->relation_type, 'primary' => (bool) $u->pivot->is_primary,
            ])->values()->all(),
            'report' => StudentReport::for($student),
            'grades' => $student->grades->map(fn ($g) => [
                'subject' => $g->subject?->name, 'type' => $g->type, 'number' => $g->activity_number,
                'quarter' => $g->quarter, 'score' => $g->score,
            ])->values()->all(),
            'attendance' => $student->attendances->map(fn ($a) => [
                'date' => $a->date?->toDateString(), 'status' => $a->status, 'subject_id' => $a->subject_id,
            ])->values()->all(),
            'saved_at' => now()->toIso8601String(),
        ];
    }
}
