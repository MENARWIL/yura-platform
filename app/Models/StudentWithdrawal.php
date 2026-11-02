<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Baja de un estudiante (transferencia o retiro) con el respaldo de sus datos de ese momento. */
class StudentWithdrawal extends Model
{
    public const TYPES = ['transferencia' => 'Transferencia a otra unidad educativa', 'retiro' => 'Retiro'];

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
}
