@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-0">Logística - Dashboard</h4>
            <small class="text-muted">Sede activa: {{ session('current_sede_name') }} | Almacén: {{ $currentWarehouse->name }}</small>
        </div>
        <a href="{{ route('warehouse.alerts.download') }}" class="btn btn-outline-danger">
            <i class="bi bi-download me-1"></i> Descargar productos en alerta
        </a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <div class="text-muted small">Items en stock</div>
                    <div class="display-6 fw-semibold">{{ $totalStocks }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-0 bg-danger-subtle">
                <div class="card-body">
                    <div class="text-muted small">Productos en alerta</div>
                    <div class="display-6 fw-semibold text-danger">{{ $totalAlerts }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100 border-0 bg-warning-subtle">
                <div class="card-body">
                    <div class="text-muted small">Solicitudes pendientes</div>
                    <div class="display-6 fw-semibold text-warning-emphasis">{{ $pendingRequests }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Alertas de stock mínimo</h5>
            <span class="badge text-bg-danger">{{ $totalAlerts }} alerta(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Material</th>
                        <th>Categoría</th>
                        <th>Stock actual</th>
                        <th>Stock mínimo</th>
                        <th>Brecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                        @php
                            $gap = max(0, (float) $alert->min_qty - (float) $alert->current_qty);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $alert->material->name }}</strong>
                                <small class="text-muted d-block">{{ $alert->material->code }}</small>
                            </td>
                            <td>{{ $alert->material->category?->name ?? 'Sin categoría' }}</td>
                            <td><span class="badge text-bg-danger">{{ number_format($alert->current_qty, 2) }} {{ $alert->material->unit }}</span></td>
                            <td>{{ number_format($alert->min_qty, 2) }} {{ $alert->material->unit }}</td>
                            <td>{{ number_format($gap, 2) }} {{ $alert->material->unit }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No hay productos en alerta para esta sede.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
