<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'HEMODIAL') }}</title>

    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700" rel="stylesheet">

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <style>
        :root {
            --medical-blue: #1a2a6c;
            --bg-soft: #f0f2f5; /* Gris suave anti-fatiga */
        }
        body { 
            background-color: var(--bg-soft); 
            font-family: 'Nunito', sans-serif;
            color: #334155;
        }
        .navbar { background: var(--medical-blue) !important; }
        .card { border: none; border-radius: 12px; transition: all 0.3s; }
        .shadow-sm { box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1) !important; }
        [x-cloak] { display: none !important; }
        
        /* Contenedor que ocupa todo el ancho */
        .main-content {
            padding: 2rem;
            min-height: calc(100vh - 65px);
        }
    </style>
</head>
<body>
    <div id="app">
        @auth
        <nav class="navbar navbar-expand-md navbar-dark shadow-sm">
            <div class="container-fluid px-4">
                <a class="navbar-brand fw-bold" href="{{ url('/home') }}">
                    <i class="bi bi-heart-pulse-fill me-2"></i> HEMODIAL
                </a>
                <div class="collapse navbar-collapse" id="navMain">
                    <ul class="navbar-nav me-auto">

                        <li class="nav-item">
                            <a class="nav-link px-3 {{ request()->routeIs('users.*') ? 'active fw-bold' : '' }}" href="{{ route('users.index') }}">
                                <i class="bi bi-person-badge me-2"></i> Usuarios
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link px-3 {{ request()->routeIs('patients.*') ? 'active fw-bold' : '' }}" href="{{ route('patients.index') }}"><i class="bi bi-people me-1"></i> Pacientes</a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link px-3 {{ request()->routeIs('referrals.*') ? 'active fw-bold' : '' }}" href="{{ route('referrals.index') }}"><i class="bi bi-file-earmark-plus me-1"></i>Referencias</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 {{ request()->routeIs('orders.*') ? 'active fw-bold' : '' }}" href="{{ route('orders.index') }}"><i class="bi bi-list me-1"></i> Ordenes</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link px-3 {{ request()->routeIs('medicals.*') ? 'active fw-bold' : '' }}" href="{{ route('medicals.index') }}">
                                <i class="bi bi-person-vcard me-1"></i>Médicina
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link px-3 {{ request()->routeIs('nurses.*') ? 'active fw-bold' : '' }}" href="{{ route('nurses.index') }}">
                                <i class="bi bi-clipboard-pulse me-1"></i>Enfermería
                            </a>
                        </li>
                        
                    </ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <div class="text-end me-2 d-none d-sm-block">
                                    <div class="small fw-bold lh-1">{{ Auth::user()->name }}</div>
                                    <small class="opacity-75" style="font-size: 0.7rem;">{{ Auth::user()->profession }}</small>
                                </div>
                                <i class="bi bi-person-circle fs-4"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end border-0 shadow">
                                <a class="dropdown-item text-danger" href="{{ route('logout') }}" 
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    Cerrar Sesión
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        @endauth

        <main class="{{ Auth::check() ? 'main-content' : '' }}">
            @yield('content')
        </main>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @stack('scripts')
</body>
</html>