@extends('layouts.app')

@section('content')
<div class="container-fluid logistics-page">
    @include('warehouse.partials.navigation', [
        'title' => 'Trazabilidad de movimientos',
        'subtitle' => 'Audita entradas, salidas, ajustes y consumos automáticos desde un solo lugar.'
    ])

    <form method="GET" class="logistics-panel p-3 mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4"><label class="form-label">Buscar producto</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Código o nombre"></div>
            <div class="col-lg-3"><label class="form-label">Tipo</label><select class="form-select" name="type"><option value="">Todos los movimientos</option><option value="in" @selected(request('type') === 'in')>Entradas</option><option value="out" @selected(request('type') === 'out')>Salidas</option><option value="adjustment" @selected(request('type') === 'adjustment')>Ajustes</option></select></div>
            @if($currentWarehouse->is_principal)
            <div class="col-lg-3"><label class="form-label">Sede</label><select class="form-select" name="warehouse_id">@foreach($availableWarehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected($warehouseId === $warehouse->id)>{{ $warehouse->sede?->name ?? $warehouse->name }}</option>@endforeach</select></div>
            @endif
            <div class="col-lg-2 d-grid"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Aplicar</button></div>
        </div>
    </form>

    <div class="logistics-panel overflow-hidden">
        <div class="table-responsive">
            <table class="table logistics-table align-middle mb-0">
                <thead><tr><th>Fecha y hora</th><th>Producto</th><th>Movimiento</th><th>Cantidad</th><th>Origen / referencia</th></tr></thead>
                <tbody>
                @forelse($movements as $movement)
                    @php
                        $automatic = $movement->reference_type === \App\Models\Order::class;
                        $typeClass = $movement->movement_type === 'in' ? 'success' : ($movement->movement_type === 'out' ? 'danger' : 'warning');
                        $typeLabel = $movement->movement_type === 'in' ? 'Entrada' : ($movement->movement_type === 'out' ? 'Salida' : 'Ajuste');
                    @endphp
                    <tr>
                        <td><strong>{{ $movement->created_at->format('d/m/Y') }}</strong><small class="d-block text-muted">{{ $movement->created_at->format('H:i') }}</small></td>
                        <td><strong>{{ $movement->material?->name }}</strong><small class="d-block text-muted">{{ $movement->material?->code }} · {{ $movement->warehouse?->sede?->name }}</small></td>
                        <td><span class="badge rounded-pill text-bg-{{ $typeClass }}">{{ $typeLabel }}</span>@if($automatic)<span class="badge rounded-pill bg-primary-subtle text-primary ms-1"><i class="bi bi-lightning-charge"></i> Automático</span>@endif</td>
                        <td class="fw-bold {{ $movement->movement_type === 'out' ? 'text-danger' : 'text-success' }}">{{ $movement->movement_type === 'out' ? '−' : '+' }}{{ number_format(abs((float)$movement->qty), 2) }} {{ $movement->material?->unit }}</td>
                        <td><span>{{ $movement->notes ?: 'Movimiento de inventario' }}</span>@if($movement->reference_id)<small class="d-block text-muted">Referencia #{{ $movement->reference_id }}</small>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-5"><i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i><strong>No hay movimientos con estos filtros</strong></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $movements->links() }}</div>
    </div>
</div>
@endsection
