@extends('layouts.app')

@section('content')
<div class="container-fluid logistics-page">
    @include('warehouse.partials.navigation', ['title' => 'Registro de proveedores', 'subtitle' => 'Mantén identificados los proveedores que abastecen el almacén.'])
    <div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">Proveedores</h4>@can('warehouse.requests.create')<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal"><i class="bi bi-plus-circle me-1"></i> Nuevo proveedor</button>@endcan</div>
    <form class="logistics-panel p-3 mb-4"><div class="input-group"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por razón social o RUC..."><button class="btn btn-outline-primary">Buscar</button></div></form>
    <div class="logistics-panel overflow-hidden"><div class="table-responsive"><table class="table logistics-table mb-0"><thead><tr><th>Razón social</th><th>RUC / identificación</th><th>Contacto</th><th>Ingresos</th><th>Estado</th></tr></thead><tbody>
    @forelse($suppliers as $supplier)<tr><td class="fw-semibold">{{ $supplier->business_name }}</td><td>{{ $supplier->tax_id }}</td><td>{{ $supplier->contact_name ?: '—' }}<small class="d-block text-muted">{{ collect([$supplier->phone, $supplier->email])->filter()->join(' · ') }}</small></td><td>{{ $supplier->entries_count }}</td><td><span class="badge text-bg-{{ $supplier->is_active ? 'success' : 'secondary' }}">{{ $supplier->is_active ? 'Activo' : 'Inactivo' }}</span></td></tr>@empty<tr><td colspan="5" class="text-center py-5 text-muted">No hay proveedores registrados.</td></tr>@endforelse
    </tbody></table></div><div class="p-3 border-top">{{ $suppliers->links() }}</div></div>
</div>
<div class="modal fade" id="supplierModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('warehouse.suppliers.store') }}">@csrf
<div class="modal-header"><h5 class="modal-title">Nuevo proveedor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3">
<div class="col-12"><label class="form-label">Razón social</label><input name="business_name" maxlength="255" class="form-control" required></div><div class="col-12"><label class="form-label">RUC / identificación tributaria</label><input name="tax_id" maxlength="20" class="form-control" required></div><div class="col-12"><label class="form-label">Persona de contacto</label><input name="contact_name" maxlength="255" class="form-control"></div><div class="col-md-6"><label class="form-label">Teléfono</label><input name="phone" maxlength="30" class="form-control"></div><div class="col-md-6"><label class="form-label">Correo</label><input type="email" name="email" class="form-control"></div>
</div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar proveedor</button></div></form></div></div>
@endsection
