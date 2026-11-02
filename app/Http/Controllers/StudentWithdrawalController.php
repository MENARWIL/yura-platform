<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentWithdrawal;
use App\Services\StudentReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Baja de estudiantes (solo administrador): transferencia a otra unidad educativa o retiro.
 * El estudiante no se borra: pasa a «inactivo», deja de aparecer en listas, notas y asistencia,
 * y se guarda una copia de sus datos de ese momento. Se puede reincorporar.
 */
class StudentWithdrawalController extends Controller
{
    public function store(Request $request, Student $student): RedirectResponse
    {
        if ($student->isWithdrawn()) {
            return back()->with('error', 'El estudiante ya está dado de baja.');
        }

        $input = $request->validateWithBag('withdrawal', [
            'motive' => ['required', Rule::in(array_keys(StudentWithdrawal::MOTIVES))],
            'destination' => ['nullable', 'required_if:motive,transferencia', 'string', 'max:255'],
            'detail' => ['nullable', 'required_if:motive,otro', 'string', 'max:1000'],
            'withdrawn_on' => ['required', 'date', 'before_or_equal:today'],
        ], [
            'motive.required' => 'Elige el motivo de la baja.',
            'destination.required_if' => 'Indica la unidad educativa a la que se transfiere.',
            'detail.required_if' => 'Escribe el motivo de la baja.',
            'withdrawn_on.before_or_equal' => 'La fecha de baja no puede ser futura.',
        ]);

        // El motivo elegido (y el detalle, si se escribió) queda como texto legible en la baja.
        $detail = trim((string) ($input['detail'] ?? ''));
        $data = [
            'type' => $input['motive'] === 'transferencia' ? 'transferencia' : 'retiro',
            'destination' => $input['motive'] === 'transferencia' ? $input['destination'] : null,
            'reason' => $input['motive'] === 'otro'
                ? $detail
                : StudentWithdrawal::MOTIVES[$input['motive']] . ($detail !== '' ? ". {$detail}" : ''),
            'withdrawn_on' => $input['withdrawn_on'],
        ];

        DB::transaction(function () use ($student, $data, $request) {
            StudentWithdrawal::create($data + [
                'student_id' => $student->id,
                'user_id' => Auth::id(),
                'snapshot' => $this->snapshot($student),
            ]);
            $student->update(['status' => Student::STATUS_WITHDRAWN]);

            AuditLog::log('student_withdrawn', 'student', $student->id, [
                'type' => $data['type'],
                'withdrawn_on' => $data['withdrawn_on'],
            ], $request);
        });

        $what = $data['type'] === 'transferencia' ? 'transferido' : 'retirado';

        return redirect()->route('grades.show', $student)
            ->with('success', "{$student->name} quedó {$what}. Ya no aparece en las listas; su información se conserva.");
    }

    public function destroy(Request $request, Student $student): RedirectResponse
    {
        $withdrawal = $student->currentWithdrawal();
        if (! $student->isWithdrawn()) {
            return back()->with('error', 'El estudiante está activo.');
        }

        DB::transaction(function () use ($student, $withdrawal, $request) {
            $withdrawal?->update(['reverted_at' => now(), 'reverted_by' => Auth::id()]);
            $student->update(['status' => 'activo']);
            AuditLog::log('student_reinstated', 'student', $student->id, ['withdrawal_id' => $withdrawal?->id], $request);
        });

        return redirect()->route('grades.show', $student)->with('success', "{$student->name} fue reincorporado.");
    }

    /** Copia de lo que tenía el estudiante al darse de baja: curso, responsables, notas y asistencia. */
    private function snapshot(Student $student): array
    {
        $student->load(['course', 'parallel', 'grades.subject', 'attendances', 'familiares']);

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
