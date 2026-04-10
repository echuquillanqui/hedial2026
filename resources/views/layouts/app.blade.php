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
        .navbar {
            background: var(--medical-blue) !important;
            position: sticky;
            top: 0;
            z-index: 1030;
        }
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
        <nav class="navbar navbar-expand-md navbar-dark shadow-sm" x-data="{ mobileOpen: false }">
            <div class="container-fluid px-4">
                <a class="navbar-brand fw-bold" href="{{ url('/home') }}">
                    <i class="bi bi-heart-pulse-fill me-2"></i> HEMODIAL
                </a>
                <button class="navbar-toggler" type="button" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen.toString()" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="navbar-collapse" :class="{ 'd-none d-md-flex': !mobileOpen, 'd-flex flex-column flex-md-row': mobileOpen }" id="navMain">
                    @php
                        $canManageUsers = auth()->user()->can('users.view');
                        $canManagePatients = auth()->user()->can('patients.view');
                        $canManageSedes = auth()->user()->can('users.view');
                        $canSeeGestion = $canManageUsers || $canManagePatients || $canManageSedes;

                        $canViewReferrals = auth()->user()->can('referrals.view');

                        $canViewOrders = auth()->user()->can('orders.view');
                        $canViewMedicals = auth()->user()->can('medicals.view');
                        $canViewNurses = auth()->user()->can('nurses.view');
                        $canViewExtraMaterials = auth()->user()->can('orders.view');
                        $canSeeClinicalArea = $canViewOrders || $canViewMedicals || $canViewNurses || $canViewExtraMaterials;

                        $canViewWarehouse = auth()->user()->can('warehouse.requests.view');
                    @endphp
                    <ul class="navbar-nav me-auto">
    @if($canSeeGestion)
    <li class="nav-item dropdown" x-data="{ open: false }" @click.away="open = false">
        <button class="nav-link dropdown-toggle px-3 border-0 bg-transparent {{ request()->routeIs('users.*', 'patients.*', 'sedes.*', 'operational-areas.*') ? 'active fw-bold' : '' }}"
           type="button" @click="open = !open" :aria-expanded="open.toString()">
            <i class="bi bi-people-fill me-1"></i> Gestión
        </button>
        <ul class="dropdown-menu shadow border-0" x-show="open" x-transition x-cloak style="display: none;">
            @if($canManageUsers)
            <li>
                <a class="dropdown-item {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                    <i class="bi bi-person-badge me-2"></i> Usuarios
                </a>
            </li>
            @endif
            @if($canManagePatients)
            <li>
                <a class="dropdown-item {{ request()->routeIs('patients.*') ? 'active' : '' }}" href="{{ route('patients.index') }}">
                    <i class="bi bi-people me-2"></i> Pacientes
                </a>
            </li>
            @endif
            @if($canManageSedes)
            <li>
                <a class="dropdown-item {{ request()->routeIs('sedes.*') ? 'active' : '' }}" href="{{ route('sedes.index') }}">
                    <i class="bi bi-building me-2"></i> Sedes
                </a>
            </li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('operational-areas.*') ? 'active' : '' }}" href="{{ route('operational-areas.index') }}">
                    <i class="bi bi-diagram-3 me-2"></i> Áreas operativas
                </a>
            </li>
            @endif
        </ul>
    </li>
    @endif

    @if($canViewReferrals)
    <li class="nav-item">
        <a class="nav-link px-3 {{ request()->routeIs('referrals.*') ? 'active fw-bold' : '' }}" href="{{ route('referrals.index') }}">
            <i class="bi bi-file-earmark-plus me-1"></i> Referencias
        </a>
    </li>
    @endif

    @if($canSeeClinicalArea)
    <li class="nav-item dropdown" x-data="{ open: false }" @click.away="open = false">
        <button class="nav-link dropdown-toggle px-3 border-0 bg-transparent {{ request()->routeIs('orders.*', 'medicals.*', 'nurses.*', 'extra-materials.*') ? 'active fw-bold' : '' }}"
           type="button" @click="open = !open" :aria-expanded="open.toString()">
            <i class="bi bi-clipboard2-pulse-fill me-1"></i> Área Clínica
        </button>
        <ul class="dropdown-menu shadow border-0" x-show="open" x-transition x-cloak style="display: none;">
            @if($canViewOrders)
            <li>
                <a class="dropdown-item {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">
                    <i class="bi bi-list-check me-2"></i> Ordenes
                </a>
            </li>
            @endif
            @if(($canViewOrders && ($canViewMedicals || $canViewNurses || $canViewExtraMaterials)))
            <li><hr class="dropdown-divider"></li>
            @endif
            @if($canViewMedicals)
            <li>
                <a class="dropdown-item {{ request()->routeIs('medicals.*') ? 'active' : '' }}" href="{{ route('medicals.index') }}">
                    <i class="bi bi-person-vcard me-2"></i> Medicina
                </a>
            </li>
            @endif
            @if($canViewNurses)
            <li>
                <a class="dropdown-item {{ request()->routeIs('nurses.*') ? 'active' : '' }}" href="{{ route('nurses.index') }}">
                    <i class="bi bi-clipboard-pulse me-2"></i> Enfermería
                </a>
            </li>
            @endif
            @if($canViewExtraMaterials)
            <li>
                <a class="dropdown-item {{ request()->routeIs('extra-materials.*') ? 'active' : '' }}" href="{{ route('extra-materials.index') }}">
                    <i class="bi bi-box-seam me-2"></i> Materiales extra
                </a>
            </li>
            @endif
        </ul>
    </li>
    @endif

    @if($canViewWarehouse)
    <li class="nav-item dropdown" x-data="{ open: false }" @click.away="open = false">
        <button class="nav-link dropdown-toggle px-3 border-0 bg-transparent {{ request()->routeIs('warehouse.*') ? 'active fw-bold' : '' }}"
           type="button" @click="open = !open" :aria-expanded="open.toString()">
            <i class="bi bi-truck me-1"></i> LOGÍSTICA
        </button>
        <ul class="dropdown-menu shadow border-0" x-show="open" x-transition x-cloak style="display: none;">
            <li>
                <a class="dropdown-item {{ request()->routeIs('warehouse.dashboard') ? 'active' : '' }}" href="{{ route('warehouse.dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('warehouse.categories.*') ? 'active' : '' }}" href="{{ route('warehouse.categories.index') }}">
                    <i class="bi bi-tags me-2"></i> Categorías
                </a>
            </li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('warehouse.materials.*') ? 'active' : '' }}" href="{{ route('warehouse.materials.index') }}">
                    <i class="bi bi-box-seam me-2"></i> Materiales
                </a>
            </li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('warehouse.stocks.*') ? 'active' : '' }}" href="{{ route('warehouse.stocks.index') }}">
                    <i class="bi bi-bar-chart-line me-2"></i> Stock por sede
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('warehouse.requests.index') ? 'active' : '' }}" href="{{ route('warehouse.requests.index') }}">
                    <i class="bi bi-file-earmark-text me-2"></i> Solicitudes
                </a>
            </li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('warehouse.requests.by-area') ? 'active' : '' }}" href="{{ route('warehouse.requests.by-area') }}">
                    <i class="bi bi-diagram-3 me-2"></i> Solicitudes por área
                </a>
            </li>
        </ul>
    </li>
    @endif
</ul>
                    <ul class="navbar-nav ms-auto align-items-center">
                        <li class="nav-item me-3 d-none d-md-block">
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-geo-alt-fill me-1"></i>{{ session('current_sede_name', 'Sin sede') }}
                            </span>
                        </li>
                        <li class="nav-item me-2">
                            <a class="btn btn-sm btn-outline-light" href="{{ route('sede.select') }}">Cambiar sede</a>
                        </li>
                        <li class="nav-item dropdown" x-data="{ open: false }" @click.away="open = false">
                            <button class="nav-link dropdown-toggle d-flex align-items-center border-0 bg-transparent" type="button" @click="open = !open" :aria-expanded="open.toString()">
                                <div class="text-end me-2 d-none d-sm-block">
                                    <div class="small fw-bold lh-1">{{ Auth::user()->name }}</div>
                                    <small class="opacity-75" style="font-size: 0.7rem;">{{ Auth::user()->profession }}</small>
                                </div>
                                <i class="bi bi-person-circle fs-4"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end border-0 shadow" x-show="open" x-transition x-cloak style="display: none;">
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
