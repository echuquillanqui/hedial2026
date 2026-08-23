<div class="logistics-hero mb-4">
    <div>
        <span class="logistics-eyebrow"><i class="bi bi-box-seam me-1"></i> Centro de control</span>
        <h2 class="mb-1">{{ $title }}</h2>
        <p class="mb-0">{{ $subtitle }}</p>
    </div>
    <div class="logistics-location">
        <i class="bi bi-geo-alt-fill"></i>
        <div><small>Sede activa</small><strong>{{ session('current_sede_name') }}</strong></div>
    </div>
</div>

<nav class="logistics-tabs mb-4" aria-label="Secciones de logística">
    <a href="{{ route('warehouse.dashboard') }}" class="{{ request()->routeIs('warehouse.dashboard') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i> Resumen</a>
    <a href="{{ route('warehouse.stocks.index') }}" class="{{ request()->routeIs('warehouse.stocks.*') ? 'active' : '' }}"><i class="bi bi-boxes"></i> Stock por sede</a>
    <a href="{{ route('warehouse.movements.index') }}" class="{{ request()->routeIs('warehouse.movements.*') ? 'active' : '' }}"><i class="bi bi-arrow-left-right"></i> Movimientos</a>
    @if(($currentWarehouse ?? null)?->is_principal)
    <a href="{{ route('warehouse.entries.index') }}" class="{{ request()->routeIs('warehouse.entries.*') ? 'active' : '' }}"><i class="bi bi-box-arrow-in-down"></i> Ingresos</a>
    <a href="{{ route('warehouse.suppliers.index') }}" class="{{ request()->routeIs('warehouse.suppliers.*') ? 'active' : '' }}"><i class="bi bi-truck"></i> Proveedores</a>
    @endif
    <a href="{{ route('warehouse.requests.index') }}" class="{{ request()->routeIs('warehouse.requests.index') ? 'active' : '' }}"><i class="bi bi-send"></i> Solicitudes</a>
    <a href="{{ route('warehouse.materials.index') }}" class="{{ request()->routeIs('warehouse.materials.*') ? 'active' : '' }}"><i class="bi bi-sliders"></i> Configuración</a>
    @can('warehouse.configuration.manage')
    <a href="{{ route('warehouse.configuration.edit') }}" class="{{ request()->routeIs('warehouse.configuration.*') ? 'active' : '' }}"><i class="bi bi-building-gear"></i> Almacén principal</a>
    @endcan
</nav>
