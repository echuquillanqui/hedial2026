@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1">Órdenes de {{ \App\Support\ClinicalService::label($type) }}</h2>
            <p class="text-muted mb-0">Primera atención dentro de 30 días y seguimiento cada 3 meses.</p>
        </div>
        @if(auth()->user()->can(\App\Support\ClinicalService::permissionPrefix($type).'.create') || auth()->user()->can('orders.create'))
            <a class="btn btn-primary" href="{{ route('orders.multisectorial.create', ['type' => $type]) }}">
                <i class="bi bi-plus-circle me-1"></i> Nueva orden
            </a>
        @endif
    </div>

    <form method="GET" class="card card-body shadow-sm border-0 mb-4">
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-4"><label class="form-label">Paciente</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="DNI, historia clínica o nombre"></div>
            <div class="col-md-3"><label class="form-label">Profesional</label><select class="form-select" name="professional_id"><option value="">Todos</option>@foreach($professionals as $professional)<option value="{{ $professional->id }}" @selected((string) request('professional_id') === (string) $professional->id)>{{ $professional->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Estado</label><select class="form-select" name="status"><option value="">Todos</option>@foreach(['PENDIENTE'=>'Pendiente','PROXIMA'=>'Próxima','REALIZADA'=>'Realizada','VENCIDA'=>'Vencida'] as $value=>$label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrar</button></div>
        </div>
    </form>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card shadow-sm border-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Orden</th><th>Paciente</th><th>Fecha programada</th><th>Fecha límite</th><th>Período</th><th>Profesional</th><th>Estado</th></tr></thead>
        <tbody>
        @forelse($orders as $order)
            @php($badge = ['REALIZADA'=>'success','VENCIDA'=>'danger','PRÓXIMA'=>'warning','PENDIENTE'=>'secondary'][$order->schedule_status] ?? 'secondary')
            <tr><td>{{ $order->codigo_unico }}</td><td><strong>{{ $order->patient->full_name }}</strong><br><small class="text-muted">{{ $order->patient->dni }}</small></td><td>{{ $order->fecha_orden?->format('d/m/Y') }}</td><td>{{ $order->due_date?->format('d/m/Y') }}</td><td>{{ $order->period_key }}</td><td>{{ $order->assignedProfessional?->name ?: 'Sin asignar' }}</td><td><span class="badge bg-{{ $badge }}">{{ $order->schedule_status }}</span></td></tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-5">No hay órdenes para los filtros seleccionados.</td></tr>
        @endforelse
        </tbody>
    </table></div></div>
    <div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection
