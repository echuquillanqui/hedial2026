@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-0">Logística - Solicitudes por área</h4>
            <small class="text-muted">Revise cada pedido por área sin sobrecargar la vista general de solicitudes.</small>
        </div>
        <a href="{{ route('warehouse.requests.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver a solicitudes
        </a>
    </div>

    <form method="GET" class="card shadow-sm p-3 mb-3">
        <div class="row g-2">
            <div class="col-md-5">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por código, área o solicitante...">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Todos los estados</option>
                    @foreach(array_keys($statusColors) as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>
                            {{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="operational_area_id" class="form-select">
                    <option value="">Todas las áreas</option>
                    @foreach($operationalAreaFilterOptions as $areaFilter)
                        <option value="{{ $areaFilter->id }}" @selected((string) request('operational_area_id') === (string) $areaFilter->id)>
                            {{ $areaFilter->name }} ({{ $areaFilter->sede?->name ?? 'Sin sede' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button class="btn btn-outline-primary">Filtrar</button>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Área</th>
                        <th>Sede origen</th>
                        <th>Solicitante</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th class="text-end">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        @php
                            $payload = [
                                'request_code' => $req->request_code,
                                'area' => $req->operationalArea?->name ?? 'Sin área',
                                'requester' => $req->requester->name ?? '-',
                                'status' => $statusLabels[$req->status] ?? ucfirst(str_replace('_', ' ', $req->status)),
                                'observations' => $req->observations,
                                'items' => $req->items->map(fn ($item) => [
                                    'material' => $item->material->name,
                                    'category' => $item->material?->category?->name ?? 'Sin categoría',
                                    'qty_requested' => number_format($item->qty_requested, 2),
                                    'qty_approved' => number_format($item->qty_approved, 2),
                                    'qty_sent' => number_format($item->qty_sent, 2),
                                    'qty_received' => number_format($item->qty_received, 2),
                                    'dispatch_status' => $dispatchStatusLabels[$item->dispatch_status] ?? ucfirst(str_replace('_', ' ', $item->dispatch_status)),
                                    'receive_status' => $receiveStatusLabels[$item->receive_status ?? 'pending'] ?? ucfirst(str_replace('_', ' ', $item->receive_status ?? 'pending')),
                                ])->values()->all(),
                            ];
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $req->request_code }}</td>
                            <td>{{ $req->operationalArea?->name ?? '-' }}</td>
                            <td>{{ $req->fromWarehouse->sede->name ?? $req->fromWarehouse->name }}</td>
                            <td>{{ $req->requester->name ?? '-' }}</td>
                            <td><span class="badge bg-{{ $statusColors[$req->status] ?? 'secondary' }}">{{ $statusLabels[$req->status] ?? ucfirst(str_replace('_', ' ', $req->status)) }}</span></td>
                            <td>{{ $req->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm js-open-request-detail"
                                    data-request='@json($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>
                                    <i class="bi bi-eye"></i> Ver pedido
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">Sin solicitudes para mostrar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $requests->links() }}</div>
    </div>
</div>

<div class="modal fade" id="requestByAreaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3 small">
                    <div class="col-md-6"><strong>Código:</strong> <span id="modalRequestCode">-</span></div>
                    <div class="col-md-6"><strong>Área:</strong> <span id="modalRequestArea">-</span></div>
                    <div class="col-md-6"><strong>Solicitante:</strong> <span id="modalRequestRequester">-</span></div>
                    <div class="col-md-6"><strong>Estado:</strong> <span id="modalRequestStatus">-</span></div>
                </div>

                <div class="border rounded p-2 mb-3 bg-light-subtle small">
                    <strong>Observaciones:</strong>
                    <div id="modalRequestObservations" class="text-muted">Sin observaciones.</div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Solicitado</th>
                                <th>Aprobado</th>
                                <th>Enviado</th>
                                <th>Recibido</th>
                                <th>Estados</th>
                            </tr>
                        </thead>
                        <tbody id="modalRequestItems"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('requestByAreaModal');
    if (!modalEl) {
        return;
    }

    const modal = new bootstrap.Modal(modalEl);
    const codeEl = document.getElementById('modalRequestCode');
    const areaEl = document.getElementById('modalRequestArea');
    const requesterEl = document.getElementById('modalRequestRequester');
    const statusEl = document.getElementById('modalRequestStatus');
    const observationsEl = document.getElementById('modalRequestObservations');
    const itemsEl = document.getElementById('modalRequestItems');

    document.querySelectorAll('.js-open-request-detail').forEach((button) => {
        button.addEventListener('click', () => {
            const rawPayload = button.dataset.request || '{}';
            let data = {};

            try {
                data = JSON.parse(rawPayload);
            } catch (error) {
                console.error('No se pudo leer el detalle de la solicitud.', error);
            }

            codeEl.textContent = data.request_code || '-';
            areaEl.textContent = data.area || '-';
            requesterEl.textContent = data.requester || '-';
            statusEl.textContent = data.status || '-';
            observationsEl.textContent = data.observations || 'Sin observaciones.';

            const rows = (data.items || []).map((item) => {
                return `
                    <tr>
                        <td>
                            <strong>${item.material}</strong>
                            <div class="text-muted">${item.category}</div>
                        </td>
                        <td>${item.qty_requested}</td>
                        <td>${item.qty_approved}</td>
                        <td>${item.qty_sent}</td>
                        <td>${item.qty_received}</td>
                        <td>
                            <span class="badge text-bg-secondary mb-1">Despacho: ${item.dispatch_status}</span>
                            <span class="badge text-bg-secondary">Recepción: ${item.receive_status}</span>
                        </td>
                    </tr>
                `;
            }).join('');

            itemsEl.innerHTML = rows || '<tr><td colspan="6" class="text-center text-muted">Esta solicitud no tiene ítems.</td></tr>';
            modal.show();
        });
    });
})();
</script>
@endpush
