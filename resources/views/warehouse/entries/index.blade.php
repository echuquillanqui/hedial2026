@extends('layouts.app')

@section('content')
<div class="container-fluid logistics-page">
    @include('warehouse.partials.navigation', ['title' => 'Ingreso de nuevo material', 'subtitle' => 'Registra cada recepción con su proveedor, lote y fecha de vencimiento.'])
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="mb-0">Historial de ingresos</h4><small class="text-muted">Cada ingreso aumenta automáticamente el stock del almacén principal.</small></div>
        @can('warehouse.requests.create')<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#entryModal"><i class="bi bi-plus-circle me-1"></i> Registrar ingreso</button>@endcan
    </div>
    <form class="logistics-panel p-3 mb-4"><div class="input-group"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar producto, proveedor o lote..."><button class="btn btn-outline-primary">Buscar</button></div></form>
    <div class="logistics-panel overflow-hidden"><div class="table-responsive"><table class="table logistics-table mb-0 align-middle">
        <thead><tr><th>Fecha</th><th>Producto</th><th>Proveedor</th><th>Cantidad</th><th>Vencimiento</th><th>Lote / documento</th></tr></thead><tbody>
        @forelse($entries as $entry)<tr>
            <td>{{ $entry->created_at->format('d/m/Y H:i') }}<small class="d-block text-muted">{{ $entry->receiver?->name ?? 'Sistema' }}</small></td>
            <td><strong>{{ $entry->material?->name }}</strong><small class="d-block text-muted">{{ $entry->material?->code }}</small></td>
            <td>{{ $entry->supplier?->business_name }}<small class="d-block text-muted">{{ $entry->supplier?->tax_id }}</small></td>
            <td class="fw-bold text-success">+{{ number_format((float)$entry->quantity, 2) }} {{ $entry->material?->unit }}</td>
            <td>@if($entry->expiration_date)<span class="badge {{ $entry->expiration_date->isPast() ? 'text-bg-danger' : ($entry->expiration_date->lte(today()->addDays(30)) ? 'text-bg-warning' : 'text-bg-success') }}">{{ $entry->expiration_date->format('d/m/Y') }}</span>@else<span class="badge text-bg-secondary">No vence</span>@endif</td>
            <td>{{ $entry->batch_number ?: 'Sin lote' }}<small class="d-block text-muted">{{ $entry->document_number ?: 'Sin documento' }}</small></td>
        </tr>@empty<tr><td colspan="6" class="text-center py-5 text-muted">Aún no se registraron ingresos.</td></tr>@endforelse
        </tbody></table></div><div class="p-3 border-top">{{ $entries->links() }}</div></div>
</div>

<div class="modal fade" id="entryModal" tabindex="-1" x-data="{ noExpiration: false }"><div class="modal-dialog modal-lg"><form class="modal-content" method="POST" action="{{ route('warehouse.entries.store') }}">@csrf
    <div class="modal-header"><h5 class="modal-title">Registrar ingreso de material</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-md-7"><label class="form-label">Producto</label><select name="warehouse_material_id" class="form-select" required><option value="">Seleccione...</option>@foreach($materials as $material)<option value="{{ $material->id }}">{{ $material->code }} · {{ $material->name }}</option>@endforeach</select></div>
        <div class="col-md-5"><label class="form-label">Proveedor</label><select name="warehouse_supplier_id" class="form-select" required><option value="">Seleccione...</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->business_name }}</option>@endforeach</select><small><a href="{{ route('warehouse.suppliers.index') }}">Registrar proveedor nuevo</a></small></div>
        <div class="col-md-4"><label class="form-label">Cantidad</label><input type="number" name="quantity" min="0.01" step="0.01" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Fecha de vencimiento</label><input type="date" name="expiration_date" min="{{ today()->toDateString() }}" class="form-control" :required="!noExpiration" :disabled="noExpiration"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" id="noExpiration" x-model="noExpiration"><label class="form-check-label" for="noExpiration">Este producto no vence</label></div></div>
        <div class="col-md-4"><label class="form-label">Número de lote</label><input name="batch_number" maxlength="100" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Guía / factura</label><input name="document_number" maxlength="100" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Observaciones</label><input name="notes" maxlength="1000" class="form-control"></div>
    </div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar ingreso</button></div>
</form></div></div>
@endsection
