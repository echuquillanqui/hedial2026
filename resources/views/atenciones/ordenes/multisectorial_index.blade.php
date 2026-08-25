@extends('layouts.app')

@section('content')
<style>
    .multisectorial-title { color: #198754; }
    .filter-label { color: #198754; font-size: .7rem; font-weight: 800; text-transform: uppercase; }
    .order-toolbar { background: linear-gradient(135deg, #198754, #157347); }
</style>
<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="mb-1 fw-bold text-uppercase multisectorial-title"><i class="bi bi-file-earmark-medical me-2"></i>Órdenes de {{ \App\Support\ClinicalService::label($type) }}</h2>
            <p class="text-muted mb-0">Primera atención dentro de 30 días y seguimiento cada 3 meses.</p>
        </div>
        @if(auth()->user()->can(\App\Support\ClinicalService::permissionPrefix($type).'.create') || auth()->user()->can('orders.create'))
            <a class="btn btn-success shadow-sm fw-bold" href="{{ route('orders.multisectorial.create', ['type' => $type]) }}">
                <i class="bi bi-collection me-1"></i> Generar órdenes
            </a>
        @endif
    </div>

    <form method="GET" class="card card-body shadow-sm border-0 mb-4">
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="row g-2 align-items-end">
            <div class="col-lg-3"><label class="filter-label">Paciente / documento</label><input class="form-control form-control-sm border-success" name="search" value="{{ request('search') }}" placeholder="DNI, H.C. o nombre"></div>
            <div class="col-lg-2 col-md-4"><label class="filter-label">Fecha</label><input type="date" class="form-control form-control-sm border-success" name="date" value="{{ request('date') }}"></div>
            <div class="col-lg-1 col-md-4"><label class="filter-label">Turno</label><select class="form-select form-select-sm border-success" name="turno"><option value="">Todos</option>@foreach(range(1,4) as $turno)<option value="{{ $turno }}" @selected((string)request('turno')===(string)$turno)>T{{ $turno }}</option>@endforeach</select></div>
            <div class="col-lg-1 col-md-4"><label class="filter-label">Módulo</label><select class="form-select form-select-sm border-success" name="modulo"><option value="">Todos</option>@foreach(range(1,4) as $modulo)<option value="{{ $modulo }}" @selected((string)request('modulo')===(string)$modulo)>M{{ $modulo }}</option>@endforeach</select></div>
            <div class="col-lg-2 col-md-4"><label class="filter-label">Profesional</label><select class="form-select form-select-sm border-success" name="professional_id"><option value="">Todos</option>@foreach($professionals as $professional)<option value="{{ $professional->id }}" @selected((string) request('professional_id') === (string) $professional->id)>{{ $professional->name }}</option>@endforeach</select></div>
            <div class="col-lg-1 col-md-4"><label class="filter-label">Estado</label><select class="form-select form-select-sm border-success" name="status"><option value="">Todos</option>@foreach(['PENDIENTE'=>'Pendiente','PROXIMA'=>'Próxima','REALIZADA'=>'Realizada','VENCIDA'=>'Vencida'] as $value=>$label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-lg-1 col-md-2"><button class="btn btn-sm btn-success w-100 fw-bold"><i class="bi bi-funnel"></i> Filtrar</button></div>
            <div class="col-lg-1 col-md-2"><a class="btn btn-sm btn-outline-secondary w-100 fw-bold" href="{{ route('orders.multisectorial.index',['type'=>$type]) }}"><i class="bi bi-x-circle"></i> Limpiar</a></div>
        </div>
    </form>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @php($canGenerateFua = auth()->user()->can(\App\Support\ClinicalService::permissionPrefix($type).'.fua.generate') || auth()->user()->can('fua.generate'))
    <form method="POST" action="{{ route('fuas.multisectorial.generate-bulk') }}">@csrf
    <div class="card shadow-sm border-0">
        @if($canGenerateFua)<div class="card-header order-toolbar d-flex justify-content-between"><a class="btn btn-light btn-sm fw-bold" href="{{ route('fuas.multisectorial.index', ['type' => $type, 'all_dates' => 1]) }}"><i class="bi bi-printer me-1"></i> Bandeja FUA</a><button class="btn btn-warning btn-sm fw-bold">Generar FUA seleccionadas</button></div>@endif
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr>@if($canGenerateFua)<th></th>@endif<th>Orden</th><th>Paciente</th><th>Fecha programada</th><th>Fecha límite</th><th>Período</th><th>Profesional</th><th>Estado</th>@if($canGenerateFua)<th>FUA</th>@endif</tr></thead>
        <tbody>
        @forelse($orders as $order)
            @php($badge = ['REALIZADA'=>'success','VENCIDA'=>'danger','PRÓXIMA'=>'warning','PENDIENTE'=>'secondary'][$order->schedule_status] ?? 'secondary')
            <tr>@if($canGenerateFua)<td>@if(!$order->fua)<input class="form-check-input" type="checkbox" name="orders[]" value="{{ $order->id }}">@endif</td>@endif<td>{{ $order->codigo_unico }}</td><td><strong>{{ $order->patient->full_name }}</strong><br><small class="text-muted">{{ $order->patient->dni }}</small></td><td>{{ $order->fecha_orden?->format('d/m/Y') }}</td><td>{{ $order->due_date?->format('d/m/Y') }}</td><td>{{ $order->period_key }}</td><td>{{ $order->assignedProfessional?->name ?: 'Sin asignar' }}</td><td><span class="badge bg-{{ $badge }}">{{ $order->schedule_status }}</span></td>@if($canGenerateFua)<td>@if($order->fua)<a class="btn btn-sm btn-outline-danger" href="{{ route('fuas.pdf', $order->fua) }}">{{ $order->fua->number }}</a>@else<button class="btn btn-sm btn-outline-primary" formaction="{{ route('fuas.orders.generate', $order) }}">Generar</button>@endif</td>@endif</tr>
        @empty
            <tr><td colspan="9" class="text-center text-muted py-5">No hay órdenes para los filtros seleccionados.</td></tr>
        @endforelse
        </tbody>
    </table></div></div></form>
    <div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection
