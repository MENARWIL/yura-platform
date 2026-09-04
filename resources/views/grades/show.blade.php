@extends('layouts.app')

@section('title', __('messages.grades_for') . ' ' . $student->nombre)

@section('content')
<div class="animate-fade-in">
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card card-quechua border-0">
                <div class="card-header card-header-quechua">
                    <h3 class="card-title mb-0 font-weight-bold">
                        <i class="fas fa-user-graduate mr-2"></i> {{ $student->nombre }}
                    </h3>
                </div>
                <div class="card-body bg-white p-4">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <p class="text-muted small text-uppercase font-weight-bold mb-1">{{ __('messages.level') }}</p>
                            <p class="h5 font-weight-bold">{{ strtoupper($student->level ?? $student->nivel) }}</p>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted small text-uppercase font-weight-bold mb-1">{{ __('messages.age') }}</p>
                            <p class="h5 font-weight-bold">{{ $student->age ?? $student->edad }} {{ __('messages.years') }}</p>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted small text-uppercase font-weight-bold mb-1">{{ __('messages.responsible') }}</p>
                            <p class="h5 font-weight-bold">{{ $student->parent?->name ?? $student->padre?->name ?? __('messages.na') }}</p>
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted small text-uppercase font-weight-bold mb-1">{{ __('messages.teacher') }}</p>
                            <p class="h5 font-weight-bold">{{ $student->teacher?->name ?? $student->profesor?->name ?? __('messages.unassigned') }}</p>
                        </div>
                    </div>

                    <hr>

                    <h5 class="font-weight-bold mb-3 mt-4">
                        <i class="fas fa-star mr-2"></i> {{ __('messages.current_grades') }}
                    </h5>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.subject') }}</th>
                                    <th class="text-center">{{ __('messages.grade') }}</th>
                                    <th>{{ __('messages.type') }}</th>
                                    <th>{{ __('messages.observations') }}</th>
                                    <th class="text-right">{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($grades as $grade)
                                <tr>
                                    <td class="font-weight-bold">{{ $grade->subject_display_name }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $grade->score >= 70 ? 'bg-success' : ($grade->score >= 51 ? 'bg-warning' : 'bg-danger') }} rounded-pill px-3">
                                            {{ number_format($grade->score, 2) }}%
                                        </span>
                                    </td>
                                    <td><small class="text-muted">{{ $grade->type ?? __('messages.na') }}</small></td>
                                    <td><small class="text-muted">{{ Str::limit($grade->observations ?? __('messages.na'), 30) }}</small></td>
                                    <td class="text-right">
                                        @if(!auth()->user()->isAcademic())
                                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editGradeModal{{ $grade->id }}" title="{{ __('messages.edit_grade') }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif
                                        <button class="btn btn-sm btn-outline-info" data-toggle="modal" data-target="#historyModal{{ $grade->id }}" title="{{ __('messages.view_history') }}">
                                            <i class="fas fa-history"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">{{ __('messages.no_grades') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-quechua border-0">
                <div class="card-header card-header-quechua">
                    <h3 class="card-title mb-0 font-weight-bold">
                        <i class="fas fa-history mr-2"></i> {{ __('messages.recent_changes') }}
                    </h3>
                </div>
                <div class="card-body bg-white p-3">
                    @forelse($history->take(5) as $change)
                    <div class="border-bottom pb-3 mb-3">
                        <p class="mb-1">
                            <small class="text-muted d-block">{{ $change->created_at->diffForHumans() }}</small>
                            <strong class="d-block">
                                {{ $change->old_score ?? '-' }} → {{ $change->new_score }}
                            </strong>
                            <small class="text-muted">{{ __('messages.by') }} {{ $change->user->name ?? __('messages.na') }}</small>
                        </p>
                        @if($change->reason)
                            <small class="text-muted italic d-block mt-1">{{ $change->reason }}</small>
                        @endif
                    </div>
                    @empty
                    <p class="text-muted text-center py-3">{{ __('messages.no_changes') }}</p>
                    @endforelse

                    @if($history->count() > 5)
                    <a href="{{ route('grades.history', $student->id) }}" class="btn btn-sm btn-link">
                        {{ __('messages.see_all') }}
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <a href="{{ auth()->user()->isAcademic() ? route('students.index') : route('grades.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i> {{ __('messages.back') }}
    </a>
</div>

@if(!auth()->user()->isAcademic())
@forelse($grades as $grade)
<!-- Edit Modal -->
<div class="modal fade" id="editGradeModal{{ $grade->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('grades.update', $grade->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-quechua text-white">
                    <h5 class="modal-title">{{ __('messages.edit_grade') }} - {{ $grade->subject->name ?? '' }}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">{{ __('messages.new_grade') }}</label>
                        <input type="number" name="score" class="form-control" min="0" max="100" step="0.01" value="{{ $grade->score }}" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">{{ __('messages.grade_type') }}</label>
                        <input type="text" name="type" class="form-control" value="{{ $grade->type }}" placeholder="ej: Examen, Actividad">
                    </div>
                    <div class="form-group mb-3">
                        <label class="font-weight-bold">{{ __('messages.reason') }}</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="{{ __('messages.reason_for_change') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-quechua">{{ __('messages.save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal{{ $grade->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-quechua text-white">
                <h5 class="modal-title">{{ __('messages.grade_history') }} - {{ $grade->subject->name ?? '' }}</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @php
                    $gradeHistory = \App\Models\GradeHistory::where('grade_id', $grade->id)->with('user')->latest()->get();
                @endphp
                @forelse($gradeHistory as $record)
                <div class="border-bottom pb-3 mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <small class="text-muted text-uppercase font-weight-bold">{{ __('messages.previous_grade') }}</small>
                            <h6 class="font-weight-bold">{{ $record->old_score ?? '-' }}%</h6>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted text-uppercase font-weight-bold">{{ __('messages.new_grade') }}</small>
                            <h6 class="font-weight-bold text-success">{{ $record->new_score }}%</h6>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted text-uppercase font-weight-bold">{{ __('messages.date') }}</small>
                            <h6 class="font-weight-bold small">{{ $record->created_at->format('d/m/Y H:i') }}</h6>
                        </div>
                    </div>
                    <p class="mb-1 mt-2">
                        <small class="text-muted">{{ __('messages.changed_by') }}: <strong>{{ $record->user->name ?? __('messages.na') }}</strong></small>
                    </p>
                    @if($record->reason)
                    <p class="mb-0">
                        <small class="text-muted italic">{{ $record->reason }}</small>
                    </p>
                    @endif
                </div>
                @empty
                <p class="text-muted text-center">{{ __('messages.no_history') }}</p>
                @endforelse
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.close') }}</button>
            </div>
        </div>
    </div>
</div>
@empty
@endforelse
@endif

@endsection
