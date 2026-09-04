@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Paralelos</h4>
                    <a href="{{ route('parallels.create') }}" class="btn btn-light">Crear paralelo</a>
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
