@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')
<div class="animate-fade-in">
    <div class="card card-quechua border-0">
        <div class="card-header card-header-quechua d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0 font-weight-bold">
                <i class="fas fa-users-cog mr-2"></i> Usuarios del Sistema
            </h3>
            <div class="card-tools ml-auto">
                <a href="{{ route('users.create') }}" class="btn btn-quechua px-4 shadow-sm">
                    <i class="fas fa-plus-circle mr-2"></i> Nuevo Usuario
                </a>
            </div>
        </div>
        <div class="card-body p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="px-4">Perfil</th>
                            <th>Email / Teléfono</th>
                            <th class="text-center">Rol</th>
                            <th class="text-center">Estado</th>
                            <th class="text-right px-4">Operaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td class="px-4">
                                <div class="d-flex align-items-center">
                                    @if($user->foto_path)
                                        <img src="{{ asset('storage/' . $user->foto_path) }}" alt="Foto de {{ $user->name }}" class="rounded-circle mr-3 shadow-sm" style="width:40px;height:40px;object-fit:cover;">
                                    @else
                                        <div class="rounded-circle bg-dark d-flex align-items-center justify-content-center mr-3 shadow-sm" style="width:40px;height:40px;background:linear-gradient(135deg,var(--bg-dark),var(--secondary-main)) !important;">
                                            <span class="text-white small font-weight-bold">{{ substr($user->name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                    <div>
                                        <span class="font-weight-bold d-block text-dark">{{ $user->name }}</span>
                                        <small class="text-muted">ID: #{{ $user->id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="d-block text-dark"><i class="far fa-envelope mr-1 opacity-50"></i> {{ $user->email }}</span>
                                    <span class="text-muted"><i class="fas fa-phone-alt mr-1 opacity-50"></i> {{ $user->telefono ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-light border px-3 py-2 text-uppercase rounded-pill" style="font-size: 0.7rem; letter-spacing: 1px;">
                                    {{ $user->rol }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill px-3 py-1 {{ $user->estado == 'activo' ? 'bg-success' : 'bg-danger' }}" style="font-size: 0.75rem;">
                                    {{ strtoupper($user->estado) }}
                                </span>
                            </td>
                            <td class="text-right px-4">
                                <div class="btn-group shadow-sm rounded-pill bg-light p-1">
                                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-white btn-sm border-0 rounded-circle mr-1" title="Editar">
                                        <i class="fas fa-edit text-primary"></i>
                                    </a>

                                    <form action="{{ route('users.status', $user->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-white btn-sm border-0 rounded-circle mr-1" title="{{ $user->estado == 'activo' ? 'Suspender' : 'Activar' }}">
                                            <i class="fas {{ $user->estado == 'activo' ? 'fa-user-slash text-warning' : 'fa-user-check text-success' }}"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-white btn-sm border-0 rounded-circle" title="Eliminar">
                                            <i class="fas fa-trash-alt text-danger"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($deletedUsers->isNotEmpty())
                <div class="mt-4 border-top pt-4">
                    <h5 class="font-weight-bold mb-3"><i class="fas fa-trash-alt mr-2"></i> Usuarios eliminados</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deletedUsers as $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($user->foto_path)
                                                    <img src="{{ asset('storage/' . $user->foto_path) }}" alt="Foto de {{ $user->name }}" class="rounded-circle mr-3 shadow-sm" style="width:32px;height:32px;object-fit:cover;">
                                                @else
                                                    <div class="rounded-circle bg-dark d-flex align-items-center justify-content-center mr-3 shadow-sm" style="width:32px;height:32px;background:linear-gradient(135deg,var(--bg-dark),var(--secondary-main)) !important;">
                                                        <span class="text-white small font-weight-bold">{{ substr($user->name, 0, 1) }}</span>
                                                    </div>
                                                @endif
                                                <span class="font-weight-bold">{{ $user->name }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td class="text-right">
                                            <form action="{{ route('users.restore', $user) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-success mr-2">
                                                    <i class="fas fa-undo mr-1"></i> Restaurar
                                                </button>
                                            </form>
                                            <form action="{{ route('users.forceDestroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este usuario definitivamente?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-skull-crossbones mr-1"></i> Eliminar definitivamente
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
