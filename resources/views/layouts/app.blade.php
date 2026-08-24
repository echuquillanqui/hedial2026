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
        .logistics-page { max-width: 1500px; margin: 0 auto; }
        .logistics-hero { display:flex; justify-content:space-between; align-items:center; gap:1.5rem; padding:2rem; color:#fff; border-radius:24px; background:linear-gradient(125deg,#142b67 0%,#075e9f 58%,#0797a6 100%); box-shadow:0 18px 40px rgba(20,43,103,.18); }
        .logistics-hero h2 { font-weight:700; }
        .logistics-hero p { color:rgba(255,255,255,.78); }
        .logistics-eyebrow { display:inline-block; margin-bottom:.65rem; padding:.35rem .7rem; border-radius:999px; background:rgba(255,255,255,.14); font-size:.75rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
        .logistics-location { display:flex; align-items:center; gap:.8rem; min-width:220px; padding:.85rem 1rem; border:1px solid rgba(255,255,255,.2); border-radius:16px; background:rgba(255,255,255,.1); }
        .logistics-location i { font-size:1.4rem; }.logistics-location small,.logistics-location strong { display:block; }
        .logistics-tabs { display:flex; gap:.4rem; padding:.45rem; overflow-x:auto; border:1px solid #e2e8f0; border-radius:16px; background:#fff; box-shadow:0 8px 24px rgba(15,23,42,.05); }
        .logistics-tabs a { white-space:nowrap; padding:.7rem 1rem; border-radius:11px; color:#64748b; text-decoration:none; font-weight:700; }
        .logistics-tabs a:hover { color:#075e9f; background:#f1f5f9; }.logistics-tabs a.active { color:#fff; background:#075e9f; box-shadow:0 5px 12px rgba(7,94,159,.22); }
        .logistics-panel { border:1px solid #e2e8f0; border-radius:18px; background:#fff; box-shadow:0 10px 30px rgba(15,23,42,.06); }
        .logistics-table thead th { padding:1rem; color:#64748b; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:.75rem; letter-spacing:.04em; text-transform:uppercase; }
        .logistics-table tbody td { padding:1rem; border-color:#eef2f7; }
        @media(max-width:767px){.main-content{padding:1rem}.logistics-hero{align-items:flex-start;flex-direction:column;padding:1.4rem}.logistics-location{width:100%}}
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
                        $canManageFuaConfiguration = auth()->user()->can('fua.configuration.manage');
                        $canSeeGestion = $canManageUsers || $canManagePatients || $canManageSedes || $canManageFuaConfiguration;

                        $canViewReferrals = auth()->user()->can('referrals.view');

                        $canViewOrders = auth()->user()->can('orders.view');
                        $canViewMedicals = auth()->user()->can('medicals.view');
                        $canViewNurses = auth()->user()->can('nurses.view');
                        $canViewExtraMaterials = auth()->user()->can('materials.view');
                        $canViewNephrology = auth()->user()->can('nephrology.view');
                        $canViewFuas = auth()->user()->can('fua.view');
                        $canSeeClinicalArea = $canViewOrders || $canViewMedicals || $canViewNurses || $canViewExtraMaterials || $canViewNephrology || $canViewFuas;

                        $canViewWarehouse = auth()->user()->can('warehouse.requests.view');
                        $canViewCatalog = auth()->user()->can('laboratory.catalog.manage');
                        $canViewLaboratoryResults = auth()->user()->can('laboratory.results.view');
                        $canCreateLaboratoryOrders = auth()->user()->can('laboratory.orders.create');
                        $canSeeLaboratory = $canViewCatalog || $canViewLaboratoryResults || $canCreateLaboratoryOrders;
                        $canViewAudit = auth()->user()->can('audit.view');
                        $canViewInitialHistory = auth()->user()->can('initial_history.view');
                        $canViewConsents = auth()->user()->can('consents.view');
                        $multisectorialMenus = collect([
                            ['type' => \App\Support\ClinicalService::NUTRITION, 'label' => 'Nutrición', 'permission' => 'nutrition.view', 'fua_permission' => 'nutrition.fua.view'],
                            ['type' => \App\Support\ClinicalService::PSYCHOLOGY, 'label' => 'Psicología', 'permission' => 'psychology.view', 'fua_permission' => 'psychology.fua.view'],
                            ['type' => \App\Support\ClinicalService::SOCIAL_WORK, 'label' => 'Trabajo Social', 'permission' => 'social_work.view', 'fua_permission' => 'social_work.fua.view'],
                        ])->filter(fn ($item) => auth()->user()->can($item['permission']) || $canViewOrders);
                        $canSeeClinicalArea = $canSeeClinicalArea || $multisectorialMenus->isNotEmpty() || $canViewInitialHistory || $canViewConsents;
                    @endphp
                    <ul class="navbar-nav me-auto">
    @if($canSeeGestion)
    <li class="nav-item dropdown" x-data="{ open: false }" @click.away="open = false">
        <button class="nav-link dropdown-toggle px-3 border-0 bg-transparent {{ request()->routeIs('users.*', 'patients.*', 'sedes.*', 'operational-areas.*', 'fuas.configuration.*') ? 'active fw-bold' : '' }}"
           type="button" @click="open = !open" :aria-expanded="open.toString()">
            <i class="bi bi-people-fill me-1"></i> Gestión
        </button>
        <ul class="dropdown-menu shadow border-0" :class="{ 'show': open }" x-transition x-cloak>
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
            @if($canManageFuaConfiguration)
            <li>
                <a class="dropdown-item {{ request()->routeIs('fuas.configuration.*') ? 'active' : '' }}" href="{{ route('fuas.configuration.edit') }}">
                    <i class="bi bi-file-earmark-medical me-2"></i> Configuración FUA
                </a>
            </li>
            @endif
        </ul>
    </li>
    @endif

    @if($canSeeLaboratory)
    <li class="nav-item dropdown" x-data="{ open: false }" @click.away="open = false">
        <button class="nav-link dropdown-toggle px-3 border-0 bg-transparent {{ request()->routeIs('catalog.*', 'laboratory.*') ? 'active fw-bold' : '' }}"
           type="button" @click="open = !open" :aria-expanded="open.toString()">
            <i class="bi bi-journal-medical me-1"></i> Laboratorio
        </button>
        <ul class="dropdown-menu shadow border-0" :class="{ 'show': open }" x-transition x-cloak>
            @if($canViewCatalog)
            <li>
                <a class="dropdown-item {{ request()->routeIs('catalog.*') ? 'active' : '' }}" href="{{ route('catalog.index') }}">
                    <i class="bi bi-journal-text me-2"></i> Catálogo
                </a>
            </li>
            @endif
            @if($canCreateLaboratoryOrders)
            <li>
                <a class="dropdown-item {{ request()->routeIs('laboratory.orders.*') ? 'active' : '' }}" href="{{ route('laboratory.orders.create') }}">
                    <i class="bi bi-clipboard2-plus me-2"></i> Nueva orden de laboratorio
                </a>
            </li>
            @endif
            @if($canViewLaboratoryResults)
            <li>
                <a class="dropdown-item {{ request()->routeIs('laboratory.results.*') ? 'active' : '' }}" href="{{ route('laboratory.results.index') }}">
                    <i class="bi bi-clipboard2-pulse me-2"></i> Resultados
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

    @if($canViewFuas)
    <li class="nav-item dropdown" x-data="{ open: false }" @click.away="open = false">
        <button class="nav-link dropdown-toggle px-3 border-0 bg-transparent {{ request()->routeIs('fuas.hemodialysis.*', 'fuas.nephrology.*') ? 'active fw-bold' : '' }}"
                type="button" @click="open = !open" :aria-expanded="open.toString()">
            <i class="bi bi-printer me-1"></i> Impresiones
        </button>
        <ul class="dropdown-menu shadow border-0" :class="{ 'show': open }" x-transition x-cloak>
            <li><a class="dropdown-item {{ request()->routeIs('fuas.hemodialysis.*') ? 'active' : '' }}" href="{{ route('fuas.hemodialysis.index') }}">
                <i class="bi bi-file-earmark-medical me-2"></i> FUA de hemodiálisis
            </a></li>
            <li><a class="dropdown-item {{ request()->routeIs('fuas.nephrology.*') ? 'active' : '' }}" href="{{ route('fuas.nephrology.index') }}">
                <i class="bi bi-journal-medical me-2"></i> FUA de consultas
            </a></li>
        </ul>
    </li>
    @endif

    @if($canSeeClinicalArea)
    <li class="nav-item dropdown" x-data="{ open: false }" @click.away="open = false">
        <button class="nav-link dropdown-toggle px-3 border-0 bg-transparent {{ request()->routeIs('orders.*', 'medicals.*', 'consultations.*', 'nurses.*', 'extra-materials.*', 'fuas.index', 'fuas.preview', 'fuas.pdf') ? 'active fw-bold' : '' }}"
           type="button" @click="open = !open" :aria-expanded="open.toString()">
            <i class="bi bi-clipboard2-pulse-fill me-1"></i> Área Clínica
        </button>
        <ul class="dropdown-menu shadow border-0" :class="{ 'show': open }" x-transition x-cloak>
            @if($canViewOrders)
            <li>
                <a class="dropdown-item {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">
                    <i class="bi bi-list-check me-2"></i> Ordenes
                </a>
            </li>
            @endif
            @foreach($multisectorialMenus as $sectorMenu)
            <li>
                <a class="dropdown-item {{ request()->routeIs('orders.multisectorial.*') && request('type') === $sectorMenu['type'] ? 'active' : '' }}" href="{{ route('orders.multisectorial.index', ['type' => $sectorMenu['type']]) }}">
                    <i class="bi bi-person-lines-fill me-2"></i> Órdenes {{ $sectorMenu['label'] }}
                </a>
            </li>
            @if($sectorMenu['type'] === \App\Support\ClinicalService::NUTRITION && auth()->user()->can('nutrition.view'))
            <li><a class="dropdown-item {{ request()->routeIs('nutrition.*') ? 'active' : '' }}" href="{{ route('nutrition.index') }}"><i class="bi bi-heart-pulse me-2"></i> Atenciones Nutrición</a></li>
            @endif
            @if($sectorMenu['type'] === \App\Support\ClinicalService::NUTRITION && auth()->user()->can('nutrition.mis.view'))
            <li><a class="dropdown-item {{ request()->routeIs('mis.*') ? 'active' : '' }}" href="{{ route('mis.index') }}"><i class="bi bi-clipboard2-data me-2"></i> Evaluaciones MIS</a></li>
            @endif
            @if(auth()->user()->can($sectorMenu['fua_permission']) || $canViewFuas)
            <li><a class="dropdown-item" href="{{ route('fuas.multisectorial.index', ['type' => $sectorMenu['type'], 'all_dates' => 1]) }}"><i class="bi bi-file-earmark-medical me-2"></i> FUA {{ $sectorMenu['label'] }}</a></li>
            @endif
            @endforeach
            @if($canViewInitialHistory)<li><a class="dropdown-item {{ request()->routeIs('initial-histories.*') ? 'active' : '' }}" href="{{ route('initial-histories.index') }}"><i class="bi bi-journal-medical me-2"></i> Historia Clínica Inicial</a></li>@endif
            @if($canViewConsents)<li><a class="dropdown-item {{ request()->routeIs('consents.*') ? 'active' : '' }}" href="{{ route('consents.index') }}"><i class="bi bi-pen me-2"></i> Consentimientos</a></li>@endif
            @if($canViewFuas)
            <li>
                <a class="dropdown-item {{ request()->routeIs('fuas.index', 'fuas.preview', 'fuas.pdf') ? 'active' : '' }}" href="{{ route('fuas.index') }}">
                    <i class="bi bi-files me-2"></i> FUA generadas
                </a>
            </li>
            @endif
            @if(($canViewOrders || $canViewFuas) && ($canViewMedicals || $canViewNurses || $canViewNephrology || $canViewExtraMaterials))
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
            @if($canViewNephrology)
            <li>
                <a class="dropdown-item {{ request()->routeIs('consultations.*') ? 'active' : '' }}" href="{{ route('consultations.index') }}">
                    <i class="bi bi-journal-medical me-2"></i> Consultas nefrológicas
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
        <ul class="dropdown-menu shadow border-0" :class="{ 'show': open }" x-transition x-cloak>
            <li><h6 class="dropdown-header">Control operativo</h6></li>
            <li>
                <a class="dropdown-item {{ request()->routeIs('warehouse.dashboard') ? 'active' : '' }}" href="{{ route('warehouse.dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header">Catálogo e inventario</h6></li>
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
            <li>
                <a class="dropdown-item {{ request()->routeIs('warehouse.movements.*') ? 'active' : '' }}" href="{{ route('warehouse.movements.index') }}">
                    <i class="bi bi-arrow-left-right me-2"></i> Movimientos
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header">Abastecimiento</h6></li>
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
    @if($canViewAudit)
    <li class="nav-item dropdown" x-data="{ open: false }" @click.away="open = false">
        <button class="nav-link dropdown-toggle px-3 border-0 bg-transparent {{ request()->routeIs('audit.*') ? 'active fw-bold' : '' }}"
                type="button" @click="open = !open" :aria-expanded="open.toString()">
            <i class="bi bi-shield-check me-1"></i> AUDITORÍA
        </button>
        <ul class="dropdown-menu shadow border-0" :class="{ 'show': open }" x-transition x-cloak>
            <li><a class="dropdown-item {{ request()->routeIs('audit.histories') ? 'active' : '' }}" href="{{ route('audit.histories') }}">
                <i class="bi bi-journal-check me-2"></i> HISTORIAS
            </a></li>
            <li><a class="dropdown-item {{ request()->routeIs('audit.fissal') ? 'active' : '' }}" href="{{ route('audit.fissal') }}">
                <i class="bi bi-table me-2"></i> FISSAL
            </a></li>
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
                            <div class="dropdown-menu dropdown-menu-end border-0 shadow" :class="{ 'show': open }" x-transition x-cloak>
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
