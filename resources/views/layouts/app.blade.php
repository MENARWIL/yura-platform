<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YURA Platform | Dashboard</title>

    <!-- Google Font: Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Custom style -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    @yield('css')
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-light bg-white border-bottom-0 shadow-sm" style="backdrop-filter: blur(10px); background: rgba(255,255,255,0.8) !important;">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link text-dark">
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
                    <button type="submit" class="btn btn-quechua btn-sm ml-2" style="background: #dc3545; color: #fff; border: none; border-radius: 4px; padding: 4px 10px;">
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
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-dark font-weight-bold" style="width: 35px; height: 35px; background: linear-gradient(45deg, #3c8dbc, #00c0ef) !important;">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                </div>
                <div class="info">
                    <a href="{{ route('profile.edit') }}" class="d-block text-white small">
                        {{ Auth::user()->name }}<br>
                        <span class="badge badge-warning" style="font-size: 10px; background-color: #f39c12; color: #fff;">{{ strtoupper(Auth::user()->role ?? Auth::user()->rol ?? 'PROFESOR') }}</span>
                    </a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-header text-uppercase small opacity-50 text-white mb-2">{{ __('messages.main_menu') ?? 'MENÚ' }}</li>
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>{{ __('messages.dashboard') ?? 'Dashboard' }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('students.index') }}" class="nav-link {{ request()->is('students*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-graduation-cap"></i>
                            <p>{{ __('messages.students') ?? 'Estudiantes' }}</p>
                        </a>
                    </li>

                    @php
                        $userRole = strtolower(Auth::user()->role ?? Auth::user()->rol ?? '');
                    @endphp

                    <!-- Menu de Gestion Academica de profesores y administrativos -->
                    @if(in_array($userRole, ['admin', 'academic', 'teacher', 'profesor']))
                    <li class="nav-item">
                        <a href="{{ route('family-members.index') }}" class="nav-link {{ request()->is('family-members*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Gestión Académica</p>
                        </a>
                    </li>
                    @if(Auth::user()->role !== "academic")
                        <li class="nav-item">
                            <a href="{{ route('grades.index') }}" class="nav-link {{ request()->is('grades*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-book"></i>
                                <p>Cuaderno Pedagógico</p>
                            </a>
                        </li>
                    @endif
                    @endif

                    @if(in_array($userRole, ['tutor', 'padre', 'madre']))
                    <li class="nav-item">
                        <a href="{{ route('tutor.dashboard') }}" class="nav-link {{ request()->is('tutor*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-check"></i>
                            <p>Portal Tutor</p>
                        </a>
                    </li>
                    @endif
                    
                    @if(in_array($userRole, ['admin', 'academic']))
                    <li class="nav-header text-uppercase small opacity-50 text-white mt-3 mb-2">{{ __('messages.administration') ?? 'ADMINISTRACIÓN' }}</li>
                    <li class="nav-item">
                        <a href="{{ route('parallels.index') }}" class="nav-link {{ request()->is('parallels*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-layer-group"></i>
                            <p>Paralelos</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('parallels.create') }}" class="nav-link {{ request()->is('parallels/create') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-plus-circle"></i>
                            <p>Crear paralelo</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('courses.index') }}" class="nav-link {{ request()->is('courses*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-book-open"></i>
                            <p>Cursos</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('courses.create') }}" class="nav-link {{ request()->is('courses/create') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-plus-circle"></i>
                            <p>Crear curso</p>
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

<!-- Scripts Requeridos -->
