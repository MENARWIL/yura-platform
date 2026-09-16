@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Paralelos</h4>
                    <a href="{{ route('parallels.create') }}" class="btn btn-warning text-dark font-weight-bold shadow-sm">
                        <i class="fas fa-plus-circle mr-1"></i> Crear paralelo
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Paralelo</th>
                                <th>Cupo</th>
                                <th>Estudiantes</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($parallels as $parallel)
                                <tr>
                                    <td>{{ $parallel->course->name }}</td>
                                    <td>{{ $parallel->name }}</td>
                                    <td>{{ $parallel->max_students }}</td>
                                    <td>{{ $parallel->students_count }}</td>
                                    <td>
                                        <a href="{{ route('parallels.edit', $parallel) }}" class="btn btn-sm btn-primary">Editar</a>
                                        <form action="{{ route('parallels.destroy', $parallel) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este registro?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
