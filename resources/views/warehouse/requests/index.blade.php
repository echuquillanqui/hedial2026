@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="warehouseRequests()">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-0">Logística - Solicitudes entre sedes</h4>
            <small class="text-muted">Sede activa: {{ session('current_sede_name') }} | Almacén principal: {{ $principalWarehouse?->sede?->name ?? 'No configurado' }}</small>
        </div>
        <div class="d-flex gap-2">
            @can('warehouse.requests.create')
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                <i class="bi bi-plus-circle"></i> Nueva solicitud
            </button>
            @endcan
        </div>
    </div>

    <form method="GET" class="card shadow-sm p-3 mb-3">
        <div class="row g-2">
            <div class="col-md-8">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por código o sede...">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Todos los estados</option>
                    @foreach(array_keys($statusColors) as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</option>
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
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Solicitante</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    <tr>
                        <td class="fw-semibold">{{ $req->request_code }}</td>
                        <td>{{ $req->fromWarehouse->sede->name ?? $req->fromWarehouse->name }}</td>
                        <td>{{ $req->toWarehouse->sede->name ?? $req->toWarehouse->name }}</td>
                        <td>{{ $req->requester->name ?? '-' }}</td>
                        <td><span class="badge bg-{{ $statusColors[$req->status] ?? 'secondary' }}">{{ $statusLabels[$req->status] ?? ucfirst(str_replace('_', ' ', $req->status)) }}</span></td>
                        <td>{{ $req->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                @can('warehouse.requests.print')
                                <a href="{{ route('warehouse.requests.print-request', $req) }}" class="btn btn-outline-secondary" target="_blank" title="Imprimir solicitud"><i class="bi bi-printer"></i></a>
                                @if(in_array($req->status, ['approved','partially_dispatched','dispatched','partially_received','received']))
                                <a href="{{ route('warehouse.requests.print-dispatch', $req) }}" class="btn btn-outline-primary" target="_blank" title="Imprimir despacho"><i class="bi bi-file-earmark-pdf"></i></a>
                                @endif
                                @endcan

                                @can('warehouse.requests.update.status')
                                <button class="btn btn-outline-info" @click="openStatusModal({{ $req->id }}, '{{ $req->status }}')"><i class="bi bi-arrow-repeat"></i></button>
                                @endcan

                                @can('warehouse.requests.dispatch')
                                @if(in_array($req->status, ['approved','partially_dispatched']))
                                <button class="btn btn-outline-warning" @click="openDispatchModal({{ $req->id }})"><i class="bi bi-truck"></i></button>
                                @endif
                                @endcan

                                @can('warehouse.requests.receive')
                                @if(in_array($req->status, ['dispatched','partially_dispatched','partially_received']))
                                <button class="btn btn-outline-success" @click="openReceiveModal({{ $req->id }})"><i class="bi bi-box-arrow-in-down"></i></button>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="7" class="bg-light-subtle">
                            <div class="small text-muted mb-1">Detalle:</div>
                            <div class="row g-2">
                                @foreach($req->items as $item)
                                    <div class="col-md-4">
                                        <div class="border rounded p-2 h-100">
                                            <strong>{{ $item->material->name }}</strong>
                                            <small class="d-block text-muted">{{ $item->material?->category?->name ?? 'Sin categoría' }}</small>
                                            Sol: {{ number_format($item->qty_requested,2) }} | Aprob: {{ number_format($item->qty_approved,2) }} | Env: {{ number_format($item->qty_sent,2) }} | Rec: {{ number_format($item->qty_received,2) }}
                                            <br><span class="badge bg-{{ $item->dispatch_status === 'complete' ? 'success' : ($item->dispatch_status === 'partial' ? 'warning text-dark' : ($item->dispatch_status === 'not_sent' ? 'danger' : 'secondary')) }}">{{ $dispatchStatusLabels[$item->dispatch_status] ?? ucfirst(str_replace('_', ' ', $item->dispatch_status)) }}</span>
                                            <span class="badge bg-{{ $item->receive_status === 'complete' ? 'success' : ($item->receive_status === 'partial' ? 'warning text-dark' : ($item->receive_status === 'not_received' ? 'danger' : 'secondary')) }}">{{ $receiveStatusLabels[$item->receive_status ?? 'pending'] ?? ucfirst(str_replace('_', ' ', $item->receive_status ?? 'pending')) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">Sin solicitudes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $requests->links() }}</div>
    </div>

    @include('warehouse.requests.partials.create-modal')
    @include('warehouse.requests.partials.status-modal')
    @include('warehouse.requests.partials.dispatch-modal')
    @include('warehouse.requests.partials.receive-modal')
</div>
@endsection

@push('scripts')
<script>
function warehouseRequests() {
    return {
        statusRequestId: null,
        statusValue: 'submitted',
        dispatchRequestId: null,
        receiveRequestId: null,
        openStatusModal(id, status) {
            this.statusRequestId = id;
            this.statusValue = status;
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        },
        openDispatchModal(id) {
            this.dispatchRequestId = id;
            const form = document.getElementById('dispatchForm');
            form.action = form.dataset.actionTemplate.replace('__ID__', id);
            new bootstrap.Modal(document.getElementById('dispatchModal')).show();
        },
        openReceiveModal(id) {
            this.receiveRequestId = id;
            const form = document.getElementById('receiveForm');
            form.action = form.dataset.actionTemplate.replace('__ID__', id);
            new bootstrap.Modal(document.getElementById('receiveModal')).show();
        }
    }
}
</script>
@endpush
