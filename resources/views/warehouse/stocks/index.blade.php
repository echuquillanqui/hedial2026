@extends('layouts.app')

@section('content')
<div class="container-fluid logistics-page" x-data="stocksView()">
    @include('warehouse.partials.navigation', ['title' => 'Stock por sede', 'subtitle' => 'Existencias y mínimos operativos separados por almacén, con alertas visibles.'])
    @if(!$currentWarehouse->is_principal)
    <div class="alert alert-info py-2">
        El stock de esta sede se actualiza al recepcionar envíos de la sede principal.
    </div>
    @endif

    <div class="row g-3 mb-4">
        @forelse($stockSummary as $summary)
        <div class="col-md-6 col-xl-4"><div class="logistics-panel p-3 h-100"><div class="d-flex justify-content-between align-items-start"><div><small class="text-muted text-uppercase fw-bold">{{ $summary->warehouse?->is_principal ? 'Sede principal' : 'Sede' }}</small><h5 class="mb-2">{{ $summary->warehouse?->sede?->name ?? $summary->warehouse?->name }}</h5></div><span class="badge rounded-pill {{ $summary->alerts_count ? 'text-bg-danger' : 'text-bg-success' }}">{{ $summary->alerts_count }} alertas</span></div><div class="d-flex gap-4"><span><strong>{{ $summary->products_count }}</strong><small class="d-block text-muted">productos</small></span><span><strong class="{{ $summary->negative_count ? 'text-danger' : '' }}">{{ $summary->negative_count }}</strong><small class="d-block text-muted">por reponer</small></span></div></div></div>
        @empty
        <div class="col-12"><div class="alert alert-light border">Todavía no hay inventario registrado para esta sede.</div></div>
        @endforelse
    </div>

    <form method="GET" class="logistics-panel p-3 mb-4">
        <div class="row g-2">
            <div class="col-md-{{ $currentWarehouse->is_principal ? '4' : '5' }}"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar material..."></div>
            <div class="col-md-{{ $currentWarehouse->is_principal ? '4' : '5' }}">
                <select name="category_id" class="form-select">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string)request('category_id') === (string)$category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            @if($currentWarehouse->is_principal)
            <div class="col-md-2">
                <select name="warehouse_id" class="form-select">
                    <option value="">Todas las sedes</option>
                    @foreach($availableWarehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((string)request('warehouse_id') === (string)$warehouse->id)>{{ $warehouse->sede?->name ?? $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Filtrar</button></div>
        </div>
    </form>

    <div class="logistics-panel overflow-hidden">
        <div class="table-responsive">
            <table class="table logistics-table table-hover mb-0">
                <thead class="table-light"><tr><th>Material</th><th>Categoría</th>@if($currentWarehouse->is_principal)<th>Sede</th>@endif<th>Stock actual</th><th>Stock mínimo</th><th></th></tr></thead>
                <tbody>
                    @forelse($stocks as $stock)
                    <tr>
                        <td>{{ $stock->material->name }} <small class="text-muted d-block">{{ $stock->material->code }}</small></td>
                        <td>{{ $stock->material->category?->name ?? 'Sin categoría' }}</td>
                        @if($currentWarehouse->is_principal)
                        <td>{{ $stock->warehouse?->sede?->name ?? '-' }}</td>
                        @endif
                        <td><span class="badge rounded-pill bg-{{ $stock->current_qty <= $stock->min_qty ? 'danger' : 'success' }}">{{ number_format($stock->current_qty,2) }} {{ $stock->material->unit }}</span>@if($stock->current_qty < 0)<small class="d-block text-danger mt-1">Consumo pendiente de reponer</small>@endif</td>
                        <td>{{ number_format($stock->min_qty,2) }} {{ $stock->material->unit }}</td>
                        <td class="text-end">
                            @can('warehouse.requests.dispatch')
                            @if($currentWarehouse->is_principal)
                            <button class="btn btn-sm btn-outline-primary" @click="openStockModal({{ $stock->id }}, '{{ $stock->current_qty }}', '{{ $stock->min_qty }}')">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            @endif
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ $currentWarehouse->is_principal ? 6 : 5 }}" class="text-center py-4 text-muted">No hay stocks para mostrar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $stocks->links() }}</div>
    </div>

    @include('warehouse.requests.partials.stock-modal')
</div>
@endsection

@push('scripts')
<script>
function stocksView() {
    return {
        stockId: null,
        stockCurrent: 0,
        stockMin: 0,
        openStockModal(id, current, min) {
            this.stockId = id;
            this.stockCurrent = current;
            this.stockMin = min;
            new bootstrap.Modal(document.getElementById('stockModal')).show();
        }
    }
}
</script>
@endpush
