@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="stocksView()">
    <div class="mb-3">
        <h4 class="mb-0">Logística - Stock de sede</h4>
        <small class="text-muted">Sede activa: {{ session('current_sede_name') }} | Almacén: {{ $currentWarehouse->name }}</small>
    </div>
    @if(!$currentWarehouse->is_principal)
    <div class="alert alert-info py-2">
        El stock de esta sede se actualiza al recepcionar envíos de la sede principal.
    </div>
    @endif

    <form method="GET" class="card shadow-sm p-3 mb-3">
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

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Material</th><th>Categoría</th>@if($currentWarehouse->is_principal)<th>Sede</th>@endif<th>Stock actual</th><th>Stock mínimo</th><th></th></tr></thead>
                <tbody>
                    @forelse($stocks as $stock)
                    <tr>
                        <td>{{ $stock->material->name }} <small class="text-muted d-block">{{ $stock->material->code }}</small></td>
                        <td>{{ $stock->material->category?->name ?? 'Sin categoría' }}</td>
                        @if($currentWarehouse->is_principal)
                        <td>{{ $stock->warehouse?->sede?->name ?? '-' }}</td>
                        @endif
                        <td><span class="badge bg-{{ $stock->current_qty <= $stock->min_qty ? 'danger' : 'secondary' }}">{{ number_format($stock->current_qty,2) }} {{ $stock->material->unit }}</span></td>
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
