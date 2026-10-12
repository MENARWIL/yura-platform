@extends('layouts.app')

@section('title', 'Crear actividad Yura')

@section('content')
<div class="animate-fade-in">
    <div class="card card-quechua border-0">
        <div class="card-header card-header-quechua d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0 font-weight-bold"><i class="fas fa-robot mr-2"></i> Crear actividad Yura</h3>
            <a href="{{ route('robot-activities.index') }}" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i> Volver</a>
        </div>
        <form action="{{ route('robot-activities.store') }}" method="POST">
            @csrf
            <input type="hidden" name="quarter" value="{{ $selectedQuarter }}">
            <div class="card-body bg-white p-4">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="text-muted small text-uppercase font-weight-bold">Paralelo</label>
                        <select name="parallel_id" id="robotParallelSelect" class="form-control @error('parallel_id') is-invalid @enderror" required>
                            <option value="">Selecciona un paralelo</option>
                            @foreach($parallels as $parallel)
                                <option value="{{ $parallel->id }}" {{ old('parallel_id') == $parallel->id ? 'selected' : '' }}>
                                    {{ $parallel->course->name }} - {{ $parallel->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('parallel_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label class="text-muted small text-uppercase font-weight-bold">Asignatura</label>
                        <select name="subject_id" id="robotSubjectSelect" class="form-control @error('subject_id') is-invalid @enderror" required>
                            <option value="">Selecciona primero un paralelo</option>
                        </select>
                        @error('subject_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="text-muted small text-uppercase font-weight-bold">Contenido del diccionario</label>
                        <select name="category" class="form-control @error('category') is-invalid @enderror" required>
                            <option value="">Selecciona qué practicar</option>
                            @foreach($categories as $label => $table)
                                <option value="{{ $table }}" {{ old('category') === $table ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        <small class="form-text text-muted">Yura usará palabras y traducciones de esta categoría en su diccionario local.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="text-muted small text-uppercase font-weight-bold">Participantes</label>
                    <div id="robotParticipants" class="border rounded p-3" style="max-height: 220px; overflow-y: auto;"></div>
                    @error('participant_ids') <span class="text-danger small">{{ $message }}</span> @enderror
                    <div id="robotGroupSummary" class="form-text text-muted" aria-live="polite"></div>
                    <ul id="robotGroupBreakdown" class="small text-muted pl-4 mb-0" aria-live="polite"></ul>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="text-muted small text-uppercase font-weight-bold">Trimestre y casilla HACER</label>
                        <div class="form-control bg-light">
                            Trimestre {{ $selectedQuarter }} ·
                            <select name="activity_number" class="border-0 bg-transparent" required>
                                <option value="">Selecciona HACER</option>
                                @for($slot = 1; $slot <= 5; $slot++)
                                    <option value="{{ $slot }}" {{ old('activity_number') == $slot ? 'selected' : '' }}>Hacer {{ $slot }}</option>
                                @endfor
                            </select>
                        </div>
                        @error('activity_number') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label class="text-muted small text-uppercase font-weight-bold">Máximo de alumnos por grupo</label>
                        <input type="number" name="group_size" id="robotGroupSize" class="form-control @error('group_size') is-invalid @enderror" min="1" max="20" value="{{ old('group_size', 20) }}" required>
                        @error('group_size') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        <small class="form-text text-muted">Laravel formará automáticamente los grupos necesarios y los distribuirá de forma equilibrada.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="text-muted small text-uppercase font-weight-bold">Alumnos que responderán por grupo</label>
                        <input type="number" name="respondents_per_group" class="form-control @error('respondents_per_group') is-invalid @enderror" min="1" max="20" value="{{ old('respondents_per_group', 2) }}" required>
                        @error('respondents_per_group') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        <small class="form-text text-muted">Laravel sorteará y guardará a los participantes. Si un grupo es más pequeño, responderán todos sus integrantes.</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label class="text-muted small text-uppercase font-weight-bold">Preguntas por alumno</label>
                        <input type="number" name="questions_per_respondent" class="form-control @error('questions_per_respondent') is-invalid @enderror" min="1" max="20" value="{{ old('questions_per_respondent', 1) }}" required>
                        @error('questions_per_respondent') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="alert alert-info">
                    La nota se calcula sobre 100 por grupo: cada respuesta correcta aporta la misma proporción y las respuestas incorrectas o no respondidas valen 0. Todos los integrantes del grupo reciben la misma nota en la casilla HACER seleccionada.
                </div>
            </div>
            <div class="card-footer bg-white text-right">
                <button type="submit" class="btn btn-quechua px-5"><i class="fas fa-paper-plane mr-1"></i> Enviar actividad a Yura</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const participantsByParallel = @js($participantsByParallel);
    const subjectsByParallel = @js($subjectsByParallel);
    const previouslySelected = @js(array_map('intval', old('participant_ids', [])));
    const previouslySelectedSubject = @js((string) old('subject_id', ''));
    const parallelSelect = document.getElementById('robotParallelSelect');
    const subjectSelect = document.getElementById('robotSubjectSelect');
    const container = document.getElementById('robotParticipants');
    const groupSize = document.getElementById('robotGroupSize');
    const groupSummary = document.getElementById('robotGroupSummary');
    const groupBreakdown = document.getElementById('robotGroupBreakdown');

    function renderSubjects() {
        subjectSelect.replaceChildren();
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Selecciona una asignatura';
        subjectSelect.appendChild(placeholder);
        (subjectsByParallel[parallelSelect.value] || []).forEach(function (subject) {
            const option = document.createElement('option');
            option.value = subject.id;
            option.textContent = subject.name;
            option.selected = String(subject.id) === previouslySelectedSubject;
            subjectSelect.appendChild(option);
        });
    }

    function renderParticipants() {
        container.replaceChildren();
        const participants = participantsByParallel[parallelSelect.value] || [];
        if (!participants.length) {
            container.textContent = 'Este paralelo no tiene estudiantes disponibles.';
            updateGroupSummary();
            return;
        }

        participants.forEach(function (participant) {
            const label = document.createElement('label');
            label.className = 'd-block mb-1';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'participant_ids[]';
            checkbox.value = participant.id;
            checkbox.checked = previouslySelected.length === 0 || previouslySelected.includes(Number(participant.id));
            checkbox.className = 'mr-2';
            checkbox.addEventListener('change', updateGroupSummary);
            label.append(checkbox, document.createTextNode(participant.display_name));
            container.appendChild(label);
        });
        updateGroupSummary();
    }

    function updateGroupSummary() {
        const selectedCount = container.querySelectorAll('input[name="participant_ids[]"]:checked').length;
        const maxPerGroup = Number(groupSize.value);
        groupBreakdown.replaceChildren();

        if (!Number.isInteger(maxPerGroup) || maxPerGroup < 1 || maxPerGroup > 20) {
            groupSummary.textContent = selectedCount
                ? 'Indica un máximo válido de 1 a 20 alumnos por grupo.'
                : 'Selecciona al menos un alumno.';
            return;
        }

        if (!selectedCount) {
            groupSummary.textContent = 'Selecciona al menos un alumno.';
            return;
        }

        const groupCount = selectedCount ? Math.ceil(selectedCount / maxPerGroup) : 0;
        const baseGroupSize = Math.floor(selectedCount / groupCount);
        const largerGroupCount = selectedCount % groupCount;
        groupSummary.textContent = `${selectedCount} alumnos seleccionados · ${groupCount} grupo${groupCount === 1 ? '' : 's'}`;

        for (let index = 0; index < groupCount; index += 1) {
            const memberCount = baseGroupSize + (index < largerGroupCount ? 1 : 0);
            const item = document.createElement('li');
            item.textContent = `Grupo ${index + 1}: ${memberCount} alumno${memberCount === 1 ? '' : 's'}`;
            groupBreakdown.appendChild(item);
        }
    }

    parallelSelect.addEventListener('change', renderParticipants);
    parallelSelect.addEventListener('change', renderSubjects);
    groupSize.addEventListener('input', updateGroupSummary);
    renderSubjects();
    renderParticipants();
});
</script>
@endsection
