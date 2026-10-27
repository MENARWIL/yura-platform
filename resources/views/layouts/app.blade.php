<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YURA Platform | Dashboard</title>

    <!-- Google Font: Outfit (requiere internet; si no hay conexión, el navegador usa la fuente de respaldo del sistema) -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome (local, sin depender de internet) -->
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <!-- Theme style (local, sin depender de internet) -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/css/adminlte.min.css') }}">
    <!-- Custom style -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}?v={{ @filemtime(public_path('css/custom.css')) }}">
    @yield('css')
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-dark border-bottom-0 shadow-sm">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link text-white">
                    <i class="fas fa-user-circle mr-1 opacity-50"></i>
                    <b>{{ __('messages.member') ?? 'Miembro' }}:</b> {{ Auth::user()->name }}
                </span>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown mr-2">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-language mr-1"></i> {{ __('messages.language') ?? 'Idioma' }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a class="dropdown-item" href="{{ route('lang.set', ['locale' => 'es']) }}">{{ __('messages.spanish') ?? 'Castellano' }}</a>
                    <a class="dropdown-item" href="{{ route('lang.set', ['locale' => 'qu']) }}">{{ __('messages.quechua') ?? 'Quechua' }}</a>
                </div>
            </li>
            <li class="nav-item">
                <form action="{{ route('logout') }}" method="POST" style="display: inline-block;">
                    @csrf
                    <button type="submit" class="btn btn-quechua btn-yura-logout btn-sm ml-2" style="border-radius: 4px; padding: 4px 10px;">
                        <i class="fas fa-sign-out-alt mr-1"></i> {{ __('messages.logout') ?? 'Salir' }}
                    </button>
                </form>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar elevation-4 bg-dark">
        <!-- Brand Logo -->
        <a href="{{ route('dashboard') }}" class="brand-link text-center border-bottom border-secondary">
            <span class="brand-text font-weight-bold text-white" style="letter-spacing: 2px;">YURA PLATFORM</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center border-bottom border-secondary">
                <div class="image">
                    <div class="yura-avatar rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold" style="width: 35px; height: 35px;">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                </div>
                <div class="info">
                    <a href="{{ route('profile.edit') }}" class="d-block text-white small">
                        {{ Auth::user()->name }}<br>
                        <span class="badge yura-role-badge" style="font-size: 10px;">{{ mb_strtoupper(Auth::user()->role ?? Auth::user()->rol ?? 'PROFESOR', 'UTF-8') }}</span>
                    </a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-header text-uppercase small opacity-50 text-white mb-2">{{ __('messages.main_menu') ?? 'MENÚ' }}</li>
                    @php
                        $userRole = strtolower(Auth::user()->role ?? Auth::user()->rol ?? '');
                    @endphp
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>{{ __('messages.dashboard') ?? 'Dashboard' }}</p>
                        </a>
                    </li>
                    @if($userRole !== 'tutor')
                        <li class="nav-item">
                            <a href="{{ route('students.index') }}" class="nav-link {{ request()->is('students*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-graduation-cap"></i>
                                <p>{{ __('messages.students') ?? 'Estudiantes' }}</p>
                            </a>
                        </li>
                    @endif

                    <!-- Menú docente y administrativo -->
                    @if(in_array($userRole, ['admin', 'academic']))
                    <li class="nav-item">
                        <a href="{{ route('family-members.index') }}" class="nav-link {{ request()->is('family-members*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>{{ __('messages.academic_management') ?? 'Gestión Académica' }}</p>
                        </a>
                    </li>
                    @endif

                    @if(in_array($userRole, ['admin', 'academic', 'teacher', 'profesor']))
                        <li class="nav-item">
                            <a href="{{ route('grades.index') }}" class="nav-link {{ request()->is('grades*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-book"></i>
                                <p>{{ __('messages.pedagogical_notebook') ?? 'Cuaderno Pedagógico' }}</p>
                            </a>
                        </li>
                        @if($userRole === 'profesor')
                            <li class="nav-item">
                                <a href="{{ route('robot-activities.index') }}" class="nav-link {{ request()->is('robot-activities*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-robot"></i>
                                    <p>Actividades Yura</p>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('attendance.index') }}" class="nav-link {{ request()->is('attendance*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calendar-check"></i>
                                <p>Asistencia</p>
                            </a>
                        </li>
                    @endif

                    @if(in_array($userRole, ['admin', 'academic']))
                    <li class="nav-header text-uppercase small opacity-50 text-white mt-3 mb-2">{{ __('messages.administration') ?? 'ADMINISTRACIÓN' }}</li>
                    <li class="nav-item">
                        <a href="{{ route('courses.index') }}" class="nav-link {{ request()->is('courses*') || request()->is('parallels*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book-open"></i>
                            <p>{{ __('messages.courses_parallels') ?? 'Cursos y Paralelos' }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('subjects.index') }}" class="nav-link {{ request()->is('subjects*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book"></i>
                            <p>{{ __('messages.subjects') ?? 'Asignaturas' }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('teachers.index') }}" class="nav-link {{ request()->is('teachers*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chalkboard-teacher"></i>
                            <p>{{ __('messages.teachers') ?? 'Profesores' }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('teaching-assignments.index') }}" class="nav-link {{ request()->is('teaching-assignments*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-tie"></i>
                            <p>{{ __('messages.teaching_assignments') ?? 'Asignaciones docentes' }}</p>
                        </a>
                    </li>
                    @endif
                    
                    @if($userRole === 'admin')
                    <li class="nav-item">
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->is('users*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users-cog"></i>
                            <p>{{ __('messages.manage_users') ?? 'Usuarios' }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.years.index') }}" class="nav-link {{ request()->is('admin/years*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>Gestiones</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.audit.index') }}" class="nav-link {{ request()->is('admin/audit*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shield-alt"></i>
                            <p>{{ __('messages.system_audit') }}</p>
                        </a>
                    </li>
                    @endif
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">@yield('title')</h1>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <div class="content">
            <div class="container-fluid">
                
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <!-- AQUÍ INYECTA EL CONTENIDO DE LAS PÁGINAS HIJAS -->
                @yield('content')

            </div>
        </div>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

</div>
<!-- ./wrapper -->

<!-- jQuery, Bootstrap y AdminLTE (servidos localmente desde public/vendor, sin depender de internet): requeridos para el toggle animado del sidebar (data-widget="pushmenu") -->
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/js/adminlte.min.js') }}"></script>

<!-- Scripts Requeridos -->
@stack('scripts')
