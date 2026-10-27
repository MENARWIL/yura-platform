@extends('layouts.app')

@section('title', __('messages.grades_for') . ' ' . $student->nombre)

@section('content')
<div class="animate-fade-in">
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card card-quechua border-0">
                <div class="card-header card-header-quechua d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0 font-weight-bold">
                        <i class="fas fa-user-graduate mr-2"></i> {{ $student->nombre }}
                    </h3>
                    @if($canManageGuardian)
                        <button type="button" class="btn btn-light btn-sm rounded-pill px-3 ml-auto font-weight-bold text-primary" data-toggle="modal" data-target="#guardianModal">
                            <i class="fas fa-user-edit mr-1"></i> Editar información
                        </button>
                    @endif
                </div>
                <div class="card-body bg-white p-4">
                    @if($student->foto_path)
                        <div class="text-center mb-4">
                            <img src="{{ asset('storage/' . $student->foto_path) }}" alt="{{ __('messages.photo_of', ['name' => $student->nombre]) }}" class="rounded-circle shadow-sm" style="width:110px;height:110px;object-fit:cover;">
                        </div>
                    @endif
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <p class="text-muted small text-uppercase font-weight-bold mb-1">{{ __('messages.level') }}</p>
                            @php
                                $levelValue = $student->level ?? $student->nivel;
                                $ageValue = $student->age ?? $student->edad;
                                $teacherName = $student->teacher?->name ?? $student->profesor?->name;
                            @endphp
                            @if(filled($levelValue))
                                <p class="h5 font-weight-bold">{{ mb_strtoupper($levelValue, 'UTF-8') }}</p>
                            @else
                                <p class="h5 font-weight-bold mb-1 text-muted">Sin registrar</p>
                                @if($canManageGuardian)
                                    <button type="button" class="gm-inline" data-toggle="modal" data-target="#guardianModal" data-gm-tab="info"><i class="fas fa-plus-circle" aria-hidden="true"></i> Completar</button>
                                @endif
                            @endif
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted small text-uppercase font-weight-bold mb-1">{{ __('messages.age') }}</p>
                            @if(filled($ageValue))
                                <p class="h5 font-weight-bold">{{ $ageValue }} {{ __('messages.years') }}</p>
                            @else
                                <p class="h5 font-weight-bold mb-1 text-muted">Sin registrar</p>
                                @if($canManageGuardian)
                                    <button type="button" class="gm-inline" data-toggle="modal" data-target="#guardianModal" data-gm-tab="info"><i class="fas fa-plus-circle" aria-hidden="true"></i> Completar</button>
                                @endif
                            @endif
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted small text-uppercase font-weight-bold mb-1">{{ __('messages.responsible') }}</p>
                            @php
                                $responsibleName = $student->parent?->name ?? $student->padre?->name;
                            @endphp
                            <p class="h5 font-weight-bold mb-1">{{ $responsibleName ?? __('messages.na') }}</p>
                            @if(!$responsibleName && $canManageGuardian)
                                <button type="button" class="gm-inline" data-toggle="modal" data-target="#guardianModal" data-gm-tab="guardian">
                                    <i class="fas fa-plus-circle" aria-hidden="true"></i> Añadir responsable
                                </button>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <p class="text-muted small text-uppercase font-weight-bold mb-1">{{ __('messages.teacher') }}</p>
                            <p class="h5 font-weight-bold">{{ $teacherName ?? __('messages.unassigned') }}</p>
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
                                        @if(auth()->user()->isProfesor())
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

    <a href="{{ (auth()->user()->isAcademic() || auth()->user()->isResponsibleRole()) ? route('students.index') : route('grades.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i> {{ __('messages.back') }}
    </a>
</div>

@forelse($grades as $grade)
@if(auth()->user()->isProfesor())
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
@endif

<!-- History Modal (visible para cualquier rol con acceso a esta ficha: la consulta es de solo lectura) -->
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

@if($canManageGuardian)
@php
    $gErr = $errors->getBag('guardian');
    $oldMode = old('mode');
    $hasCandidates = $guardianCandidates->isNotEmpty();
    $infoErr = $errors->getBag('info');
    $levelOptions = ['básico' => 'QALLARIY (Básico)', 'intermedio' => 'CHAWPI (Intermedio)', 'avanzado' => 'SUMAQ (Avanzado)'];
    // Pestaña con la que se abre: la que falló; si no hay responsable, esa; si no, los datos del estudiante.
    $startTab = $infoErr->any() ? 'info'
        : ($gErr->any() ? ($oldMode === 'new' || ! $hasCandidates ? 'new' : 'existing')
        : ($guardians->isEmpty() ? ($hasCandidates ? 'existing' : 'new') : 'info'));
    $roleLabels = ['padre' => 'Padre', 'madre' => 'Madre', 'tutor' => 'Tutor'];
    $invalid = fn ($field) => $gErr->has($field) ? ' is-invalid' : '';
@endphp
<style>
    .gm-inline {
        display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px;
        border: 1px dashed var(--yura-action); border-radius: 999px;
        background: var(--yura-action-soft); color: var(--yura-action);
        font-size: .78rem; font-weight: 700; cursor: pointer; transition: background-color .15s ease;
    }
    .gm-inline:hover { background: var(--yura-blue-light); }
    .gm-inline:focus-visible { outline: 3px solid var(--yura-action-ring); outline-offset: 2px; }
    .guardian-modal .modal-content { border: 0; border-radius: 20px; overflow: hidden; box-shadow: 0 24px 60px rgba(8, 44, 108, .35); }
    .guardian-modal .modal-header {
        align-items: center; gap: 14px; padding: 18px 22px; border: 0; border-bottom: 3px solid var(--yura-red);
        background: linear-gradient(135deg, var(--yura-blue-dark) 0%, var(--yura-blue) 100%); color: #fff;
    }
    .guardian-modal .gm-icon { display: grid; place-items: center; flex: none; width: 46px; height: 46px; border-radius: 14px; background: rgba(255,255,255,.14); font-size: 1.2rem; }
    .guardian-modal .modal-title { font-weight: 800; }
    .guardian-modal .gm-sub { margin: 2px 0 0; font-size: .85rem; color: #D6E0F5; }
    .guardian-modal .close { color: #fff; opacity: .85; text-shadow: none; }
    .guardian-modal .modal-body { padding: 20px 22px 22px; background: #fff; }
    .gm-note { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 16px; padding: 10px 14px; border-radius: 12px; font-size: .9rem; }
    .gm-note-warn { background: #FFF7DB; border: 1px solid var(--yura-amber); color: var(--yura-amber-text); }
    .gm-guardians { list-style: none; margin: 0 0 16px; padding: 12px 14px; border: 1px solid var(--yura-border); border-radius: 12px; background: var(--yura-bg); }
    .gm-guardians li { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 3px 0; font-size: .92rem; }
    .gm-badge { padding: 2px 10px; border-radius: 999px; background: var(--yura-blue-light); color: var(--yura-blue-dark); font-size: .72rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
    .gm-tabs { gap: 8px; margin-bottom: 16px; }
    .gm-tabs .nav-link { padding: 9px 18px; border-radius: 999px; background: var(--yura-blue-light); color: var(--yura-blue); font-weight: 700; }
    .gm-tabs .nav-link.active { background: linear-gradient(180deg, var(--yura-action-top), var(--yura-action)); color: #fff; box-shadow: var(--yura-action-shadow); }
    .gm-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px 16px; }
    .gm-span2 { grid-column: span 2; }
    .gm-check { display: flex; align-items: center; gap: 10px; margin: 16px 0 0; font-weight: 600; color: var(--yura-text); cursor: pointer; }
    .gm-check input { width: 18px; height: 18px; accent-color: var(--yura-action); }
    .gm-errors { margin-bottom: 14px; padding: 10px 14px; border: 1px solid var(--yura-red); border-radius: 12px; background: var(--yura-red-light); color: var(--yura-red-dark); font-size: .9rem; }
    .gm-errors ul { margin: 0; padding-left: 18px; }
    .gm-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .guardian-modal .fb-field .is-invalid { border-color: var(--yura-red); background-color: var(--yura-red-light); }
    .guardian-modal .invalid-feedback { display: block; margin-top: 4px; font-size: .78rem; color: var(--yura-red-dark); }
    .guardian-modal .invalid-feedback:empty { display: none; }
    @media (max-width: 767.98px) {
        .gm-grid { grid-template-columns: 1fr; }
        .gm-span2 { grid-column: auto; }
        .gm-actions .fb-btn { flex: 1 1 100%; }
    }
</style>
<div class="modal fade guardian-modal" id="guardianModal" tabindex="-1" role="dialog" aria-labelledby="guardianModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="gm-icon" aria-hidden="true"><i class="fas fa-life-ring"></i></div>
                <div class="flex-grow-1">
                    <h5 class="modal-title" id="guardianModalTitle">Editar información</h5>
                    <p class="gm-sub">{{ $student->nombre }} · completa lo que falte de su ficha.</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                @if($guardians->isEmpty())
                    <div class="gm-note gm-note-warn" role="status">
                        <i class="fas fa-exclamation-triangle mt-1" aria-hidden="true"></i>
                        <span>Este estudiante <strong>no tiene responsable registrado</strong>. Sin él, la familia no puede ver las notas.</span>
                    </div>
                @else
                    <ul class="gm-guardians" aria-label="Responsables actuales">
                        @foreach($guardians as $guardian)
                            <li>
                                <span><i class="fas fa-user mr-2 text-primary" aria-hidden="true"></i>{{ $guardian->name }} <small class="text-muted">· {{ $roleLabels[$guardian->pivot->relation_type] ?? ucfirst($guardian->pivot->relation_type) }}</small></span>
                                @if($guardian->pivot->is_primary)<span class="gm-badge">Principal</span>@endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                <ul class="nav nav-pills gm-tabs" role="tablist">
                    <li class="nav-item"><a class="nav-link {{ $startTab === 'info' ? 'active' : '' }}" id="gm-tab-info" data-toggle="pill" href="#gm-pane-info" role="tab" aria-controls="gm-pane-info" aria-selected="{{ $startTab === 'info' ? 'true' : 'false' }}"><i class="fas fa-id-card mr-1" aria-hidden="true"></i> Datos del estudiante</a></li>
                    @if($hasCandidates)
                        <li class="nav-item"><a class="nav-link {{ $startTab === 'existing' ? 'active' : '' }}" id="gm-tab-existing" data-toggle="pill" href="#gm-pane-existing" role="tab" aria-controls="gm-pane-existing" aria-selected="{{ $startTab === 'existing' ? 'true' : 'false' }}"><i class="fas fa-user-check mr-1" aria-hidden="true"></i> Elegir responsable</a></li>
                    @endif
                    <li class="nav-item"><a class="nav-link {{ $startTab === 'new' ? 'active' : '' }}" id="gm-tab-new" data-toggle="pill" href="#gm-pane-new" role="tab" aria-controls="gm-pane-new" aria-selected="{{ $startTab === 'new' ? 'true' : 'false' }}"><i class="fas fa-user-plus mr-1" aria-hidden="true"></i> Nuevo responsable</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade {{ $startTab === 'info' ? 'show active' : '' }}" id="gm-pane-info" role="tabpanel" aria-labelledby="gm-tab-info">
                        @if($infoErr->any())
                            <div class="gm-errors" role="alert"><ul>@foreach($infoErr->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
                        @endif
                        @php
                            $curLevel = old('level', $levelValue ?? null);
                            $curAge = old('age', $ageValue ?? null);
                            $curGender = old('gender', $student->gender);
                        @endphp
                        <form method="POST" action="{{ route('students.info', $student) }}">
                            @csrf
                            @method('PATCH')
                            <div class="gm-grid">
                                <div class="fb-field {{ filled($curLevel) ? 'is-set' : '' }}">
                                    <label for="gm-level"><i class="fas fa-signal" aria-hidden="true"></i> Nivel</label>
                                    <select id="gm-level" name="level">
                                        <option value="">Sin registrar</option>
                                        @foreach($levelOptions as $value => $label)
                                            <option value="{{ $value }}" {{ mb_strtolower((string) $curLevel, 'UTF-8') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="fb-field {{ filled($curAge) ? 'is-set' : '' }}">
                                    <label for="gm-age"><i class="fas fa-birthday-cake" aria-hidden="true"></i> Edad (años)</label>
                                    <input id="gm-age" type="number" name="age" min="0" max="120" inputmode="numeric" value="{{ $curAge }}" placeholder="Ej. 11">
                                </div>
                                <div class="fb-field {{ filled($curGender) ? 'is-set' : '' }}">
                                    <label for="gm-gender"><i class="fas fa-venus-mars" aria-hidden="true"></i> Género</label>
                                    <select id="gm-gender" name="gender">
                                        <option value="">Sin registrar</option>
                                        @foreach(['Masculino', 'Femenino'] as $option)
                                            <option value="{{ $option }}" {{ $curGender === $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <p class="text-muted small mt-3 mb-0">Dejar un dato en blanco no borra lo que ya está registrado.</p>
                            <div class="gm-actions">
                                <button type="button" class="fb-btn fb-btn-ghost" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="fb-btn fb-btn-primary"><i class="fas fa-save" aria-hidden="true"></i> Guardar datos</button>
                            </div>
                        </form>
                    </div>
                    @if($hasCandidates)
                    <div class="tab-pane fade {{ $startTab === 'existing' ? 'show active' : '' }}" id="gm-pane-existing" role="tabpanel" aria-labelledby="gm-tab-existing">
                        @if($oldMode !== 'new' && $gErr->any())
                            <div class="gm-errors" role="alert"><ul>@foreach($gErr->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
                        @endif
                        <form method="POST" action="{{ route('students.guardian', $student) }}">
                            @csrf
                            <input type="hidden" name="mode" value="existing">
                            <div class="gm-grid">
                                <div class="fb-field gm-span2">
                                    <label for="gm-user"><i class="fas fa-users" aria-hidden="true"></i> Responsable registrado</label>
                                    <select id="gm-user" name="user_id" required class="{{ $oldMode !== 'new' ? trim($invalid('user_id')) : '' }}">
                                        <option value="">Selecciona un padre, madre o tutor…</option>
                                        @foreach($guardianCandidates as $candidate)
                                            <option value="{{ $candidate->id }}" data-role="{{ $candidate->role }}" {{ $oldMode !== 'new' && old('user_id') == $candidate->id ? 'selected' : '' }}>
                                                {{ $candidate->name }}{{ $candidate->ci ? ' — C.I. ' . $candidate->ci : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="fb-field">
                                    <label for="gm-relation"><i class="fas fa-heart" aria-hidden="true"></i> Relación</label>
                                    <select id="gm-relation" name="relation_type" required>
                                        @foreach($roleLabels as $value => $label)
                                            <option value="{{ $value }}" {{ ($oldMode !== 'new' ? old('relation_type', 'tutor') : 'tutor') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <label class="gm-check"><input type="hidden" name="is_primary" value="0"><input type="checkbox" name="is_primary" value="1" checked> Es el responsable principal</label>
                            <div class="gm-actions">
                                <button type="button" class="fb-btn fb-btn-ghost" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="fb-btn fb-btn-primary"><i class="fas fa-save" aria-hidden="true"></i> Guardar responsable</button>
                            </div>
                        </form>
                    </div>
                    @endif

                    <div class="tab-pane fade {{ $startTab === 'new' ? 'show active' : '' }}" id="gm-pane-new" role="tabpanel" aria-labelledby="gm-tab-new">
                        @if($oldMode === 'new' && $gErr->any())
                            <div class="gm-errors" role="alert"><ul>@foreach($gErr->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
                        @endif
                        <form method="POST" action="{{ route('students.guardian', $student) }}">
                            @csrf
                            <input type="hidden" name="mode" value="new">
                            <div class="gm-grid">
                                <div class="fb-field">
                                    <label for="gm-last-name">Apellido paterno</label>
                                    <input id="gm-last-name" type="text" name="last_name" value="{{ $oldMode === 'new' ? old('last_name') : '' }}" class="js-person-field{{ $invalid('last_name') }}" data-rule="name" autocomplete="off" required>
                                    <small class="invalid-feedback"></small>
                                </div>
                                <div class="fb-field">
                                    <label for="gm-second-last-name">Apellido materno</label>
                                    <input id="gm-second-last-name" type="text" name="second_last_name" value="{{ $oldMode === 'new' ? old('second_last_name') : '' }}" class="js-person-field{{ $invalid('second_last_name') }}" data-rule="name" autocomplete="off" required>
                                    <small class="invalid-feedback"></small>
                                </div>
                                <div class="fb-field">
                                    <label for="gm-first-name">Nombre(s)</label>
                                    <input id="gm-first-name" type="text" name="first_name" value="{{ $oldMode === 'new' ? old('first_name') : '' }}" class="js-person-field{{ $invalid('first_name') }}" data-rule="name" autocomplete="off" required>
                                    <small class="invalid-feedback"></small>
                                </div>
                                <div class="fb-field">
                                    <label for="gm-ci">C.I.</label>
                                    <input id="gm-ci" type="text" name="ci" value="{{ $oldMode === 'new' ? old('ci') : '' }}" class="js-person-field{{ $invalid('ci') }}" data-rule="ci" autocomplete="off" required>
                                    <small class="invalid-feedback"></small>
                                </div>
                                <div class="fb-field">
                                    <label for="gm-phone">Teléfono</label>
                                    <input id="gm-phone" type="tel" name="phone" value="{{ $oldMode === 'new' ? old('phone') : '' }}" class="js-person-field{{ $invalid('phone') }}" data-rule="phone" inputmode="tel" autocomplete="off" required>
                                    <small class="invalid-feedback"></small>
                                </div>
                                <div class="fb-field">
                                    <label for="gm-relation-new">Relación</label>
                                    <select id="gm-relation-new" name="relation_type" required>
                                        @foreach($roleLabels as $value => $label)
                                            <option value="{{ $value }}" {{ ($oldMode === 'new' ? old('relation_type', 'tutor') : 'tutor') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="fb-field gm-span2">
                                    <label for="gm-email">Correo electrónico de acceso</label>
                                    <input id="gm-email" type="email" name="email" value="{{ $oldMode === 'new' ? old('email') : '' }}" class="js-person-field{{ $invalid('email') }}" data-rule="email" autocomplete="off" required>
                                    <small class="invalid-feedback"></small>
                                </div>
                                <div></div>
                                <div class="fb-field">
                                    <label for="gm-password">Contraseña de acceso</label>
                                    <input id="gm-password" type="password" name="password" class="js-person-field{{ $invalid('password') }}" data-rule="password" autocomplete="new-password" required>
                                    <small class="invalid-feedback"></small>
                                </div>
                                <div class="fb-field">
                                    <label for="gm-password-confirmation">Confirmar contraseña</label>
                                    <input id="gm-password-confirmation" type="password" name="password_confirmation" class="js-person-field" data-rule="confirm" data-match="gm-password" autocomplete="new-password" required>
                                    <small class="invalid-feedback"></small>
                                </div>
                                <p class="text-muted small mb-0 align-self-end">Mínimo 8 caracteres, con mayúsculas, números y símbolos.</p>
                            </div>
                            <label class="gm-check"><input type="hidden" name="is_primary" value="0"><input type="checkbox" name="is_primary" value="1" checked> Es el responsable principal</label>
                            <div class="gm-actions">
                                <button type="button" class="fb-btn fb-btn-ghost" data-dismiss="modal">Cancelar</button>
                                <button type="submit" class="fb-btn fb-btn-primary"><i class="fas fa-user-plus" aria-hidden="true"></i> Registrar y asignar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script src="{{ asset('js/user-form-validation.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // La relación se propone según el rol del usuario elegido (se puede cambiar).
        var userSelect = document.getElementById('gm-user');
        var relationSelect = document.getElementById('gm-relation');
        if (userSelect && relationSelect) {
            userSelect.addEventListener('change', function () {
                var role = userSelect.selectedOptions[0] ? userSelect.selectedOptions[0].dataset.role : '';
                if (role) relationSelect.value = role;
            });
        }
        // Los atajos de la ficha abren la pestaña que corresponde a cada dato faltante.
        if (window.jQuery) {
            jQuery('#guardianModal').on('show.bs.modal', function (event) {
                var wanted = event.relatedTarget ? event.relatedTarget.getAttribute('data-gm-tab') : null;
                if (!wanted) return;
                if (wanted === 'guardian') wanted = document.getElementById('gm-tab-existing') ? 'existing' : 'new';
                var tab = document.getElementById('gm-tab-' + wanted);
                if (tab) jQuery(tab).tab('show');
            });
        }
        @if($gErr->any() || $infoErr->any())
        // Si el servidor devolvió errores, la ventana se vuelve a abrir con lo que se escribió.
        if (window.jQuery) jQuery('#guardianModal').modal('show');
        @endif
    });
</script>
@endpush
@endif

@endsection
