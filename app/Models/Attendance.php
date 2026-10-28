<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademicYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToAcademicYear;

    use HasFactory;

    protected $table = 'attendance';

    protected $fillable = [
        'academic_year_id',
        'student_id',
        'subject_id',
        'date',
        'status',
    ];

    /** Estados que el profesor puede registrar (se guardan tal cual en la columna status). */
    public const STATUSES = ['Presente', 'Falta', 'Atraso', 'Licencia'];

    protected $casts = [
        'date' => 'date',
    ];

    public static function normalizeStatus(?string $status): string
    {
        return mb_strtolower(trim((string) $status), 'UTF-8');
    }

    /**
     * Porcentaje de asistencia (Presente sobre el total). La licencia es una ausencia
     * justificada: no cuenta como presente ni como falta, así que se excluye del total.
     * Devuelve null cuando no hay registros que contar.
     */
    public static function presentPercent(iterable $attendances, int $precision = 2): ?float
    {
        $counted = 0;
        $present = 0;
        foreach ($attendances as $attendance) {
            $status = self::normalizeStatus($attendance->status);
            if ($status === 'licencia') {
                continue;
            }
            $counted++;
            if ($status === 'presente') {
                $present++;
            }
        }

        return $counted ? round(($present / $counted) * 100, $precision) : null;
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
