@extends('layouts.app')

@section('title', __('messages.grade_history') . ' - ' . $grade->student->nombre)

@section('content')
<div class="animate-fade-in">
    <div class="card card-quechua border-0">
        <div class="card-header card-header-quechua">
            <h3 class="card-title mb-0 font-weight-bold">
                <i class="fas fa-history mr-2"></i> {{ __('messages.grade_history') }}
            </h3>
        </div>

        <div class="card-body bg-white p-4">
            <div class="mb-4 p-3 bg-light rounded">
                <p class="mb-1"><strong>{{ __('messages.student') }}:</strong> {{ $grade->student->nombre }}</p>
                <p class="mb-0"><strong>{{ __('messages.subject') }}:</strong> {{ $grade->subject_display_name }}</p>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('messages.date') }}</th>
                            <th class="text-center">{{ __('messages.previous_grade') }}</th>
                            <th class="text-center">{{ __('messages.new_grade') }}</th>
                            <th>{{ __('messages.changed_by') }}</th>
                            <th>{{ __('messages.reason') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $record)
                        <tr>
                            <td>{{ $record->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="text-center">
                                <span class="badge bg-light border">{{ $record->old_score ?? '-' }}%</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success">{{ $record->new_score }}%</span>
                            </td>
                            <td><strong>{{ $record->user->name ?? __('messages.na') }}</strong></td>
                            <td class="text-muted">{{ $record->reason ?? __('messages.na') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">{{ __('messages.no_history') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($history->hasPages())
            <div class="mt-4">
                {{ $history->links() }}
            </div>
            @endif
        </div>
    </div>

    <a href="{{ route('grades.show', $grade->student->id) }}" class="btn btn-secondary mt-3">
        <i class="fas fa-arrow-left mr-2"></i> {{ __('messages.back') }}
    </a>
</div>
@endsection
