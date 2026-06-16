<!DOCTYPE html>
<html lang="es">
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
                    <b>Integrante:</b> Wilson Mendieta Arnez
                </span>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge">1</span>
                </a>
            </li>
            <li class="nav-item">
                <form action="{{ route('logout') }}" method="POST" style="display: inline-block;">
                    @csrf
                    <button type="submit" class="btn btn-quechua btn-sm ml-2">
                        <i class="fas fa-sign-out-alt mr-1"></i> Salir
                    </button>
                </form>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar elevation-4">
        <!-- Brand Logo -->
        <a href="{{ route('dashboard') }}" class="brand-link text-center">
            <span class="brand-text font-weight-bold text-white" style="letter-spacing: 2px;">YURA PLATFORM</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
                <div class="image">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold" style="width: 35px; height: 35px; background: linear-gradient(45deg, var(--primary-main), var(--accent-main)) !important; color: #000 !important;">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                </div>
                <div class="info">
                    <a href="{{ route('profile.edit') }}" class="d-block text-white small">
                        {{ Auth::user()->name }}<br>
                        <span class="badge badge-quechua-gold" style="font-size: 10px;">{{ strtoupper(Auth::user()->rol) }}</span>
                    </a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-header text-uppercase small opacity-50 text-white mb-2">Menú Principal</li>
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>Panel de Control</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('students.index') }}" class="nav-link {{ request()->is('students*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-graduation-cap"></i>
                            <p>Estudiantes</p>
                        </a>
                    </li>

                    @if(Auth::user()->isAdmin() || Auth::user()->isProfesor())
                    <li class="nav-item">
                        <a href="{{ route('family-members.index') }}" class="nav-link {{ request()->is('family-members*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Familiares y Tutores</p>
                        </a>
                    </li>
                    @endif
                    
                    @if(Auth::user()->isAdmin())
                    <li class="nav-header text-uppercase small opacity-50 text-white mt-3 mb-2">Administración</li>
                    <li class="nav-item">
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->is('users*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users-cog"></i>
                            <p>Gestionar Usuarios</p>
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
                    <div class="alert alert-success mt-3">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger mt-3">
                        {{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">
            YURA Platform
        </div>
        <strong>&copy; 2026 Wilson Mendieta Arnez.</strong>
    </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
@yield('js')
</body>
</html>
