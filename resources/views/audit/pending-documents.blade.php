@extends('layouts.app')

@section('content')
<div class="container-fluid py-4 audit-pending">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div><span class="text-uppercase text-danger fw-bold small">Auditoría clínica</span><h2 class="fw-bold mb-1">Documentos pendientes</h2><p class="text-muted mb-0">Control mensual de consultas nefrológicas, consentimientos y laboratorios por paciente.</p></div>
        <span class="badge rounded-pill text-bg-light border px-3 py-2">{{ $patients->total() }} paciente(s) pendientes</span>
    </div>

    <form method="GET" class="card card-body shadow-sm mb-4" data-audit-filters>
        <div class="row g-3 align-items-end">
            <div class="col-xl-3 col-md-6"><label class="form-label">Paciente, DNI o H.C.</label><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar..."></div>
            <div class="col-xl-2 col-md-3"><label for="pendingMonthFilter" class="form-label">Mes de control</label><input id="pendingMonthFilter" type="month" name="month" value="{{ $month }}" class="form-control"></div>
            <div class="col-xl-2 col-md-3"><label class="form-label">Pendiente</label><select name="missing" class="form-select"><option value="">Cualquier documento</option><option value="all" @selected(request('missing') === 'all')>Todos los documentos</option><option value="consent" @selected(request('missing') === 'consent')>Consentimiento</option><option value="consultation" @selected(request('missing') === 'consultation')>Consulta nefrológica</option><option value="laboratory" @selected(request('missing') === 'laboratory')>Laboratorio</option></select></div>
            <div class="col-xl-2 col-md-4"><label class="form-label">Secuencia</label><select name="sequence" class="form-select"><option value="">Todas</option>@foreach(['L-M-V','M-J-S'] as $value)<option @selected(request('sequence') === $value)>{{ $value }}</option>@endforeach</select></div>
            <div class="col-xl-1 col-md-3"><label class="form-label">Turno</label><select name="shift" class="form-select"><option value="">Todos</option>@foreach(range(1,4) as $value)<option value="{{ $value }}" @selected((string)request('shift') === (string)$value)>{{ $value }}</option>@endforeach</select></div>
            <div class="col-xl-1 col-md-3"><label class="form-label">Módulo</label><select name="module" class="form-select"><option value="">Todos</option>@foreach(range(1,4) as $value)<option value="{{ $value }}" @selected((string)request('module') === (string)$value)>{{ $value }}</option>@endforeach</select></div>
            <div class="col-xl-1 col-md-2"><a href="{{ route('audit.pending-documents') }}" class="btn btn-outline-secondary w-100" title="Limpiar filtros"><i class="bi bi-arrow-counterclockwise"></i></a></div>
        </div>
    </form>

    <div class="card shadow-sm overflow-hidden"><div class="table-responsive"><table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Paciente</th><th>Programación</th><th>Documentos que faltan</th><th class="text-end">Acciones</th></tr></thead>
        <tbody>
        @forelse($patients as $patient)
            @php
                $nephrologyOrders = $patient->orders->where('attention_type', \App\Support\ClinicalService::NEPHROLOGY);
                $missingConsent = $patient->hemodialysisConsents->isEmpty();
                $missingConsultation = !$nephrologyOrders->contains(fn ($order) => (bool) $order->nephrologyConsultation);
                $missingLaboratory = $patient->laboratoryOrders->isEmpty();
            @endphp
            <tr>
                <td><strong>{{ $patient->full_name }}</strong><small class="d-block text-muted">DNI {{ $patient->dni ?: '—' }} · H.C. {{ $patient->medical_history_number ?: '—' }}</small></td>
                <td><span class="badge text-bg-light border">{{ $patient->secuencia ?: 'Sin secuencia' }}</span><small class="d-block text-muted mt-1">Turno {{ $patient->turno ?: '—' }} · Módulo {{ $patient->modulo ?: '—' }}</small></td>
                <td class="py-3"><div class="d-flex flex-wrap gap-2">@if($missingConsent)<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle"><i class="bi bi-file-earmark-x me-1"></i>Consentimiento</span>@endif @if($missingConsultation)<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-journal-x me-1"></i>Consulta nefrológica</span>@endif @if($missingLaboratory)<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="bi bi-droplet me-1"></i>Laboratorio</span>@endif</div></td>
                <td class="text-end text-nowrap">@if($missingConsent)<a href="{{ route('consents.create', ['patient_id' => $patient->id]) }}" class="btn btn-sm btn-outline-danger" title="Generar consentimiento">Consentimiento</a>@endif @if($missingConsultation)<a href="{{ route('orders.nephrology.create', ['patient_id' => $patient->id]) }}" class="btn btn-sm btn-outline-warning" title="Generar orden nefrológica">Nefrología</a>@endif @if($missingLaboratory)<a href="{{ route('laboratory.orders.create', ['secuencia' => $patient->secuencia, 'turno' => $patient->turno]) }}" class="btn btn-sm btn-outline-info" title="Generar laboratorio">Laboratorio</a>@endif</td>
            </tr>
        @empty<tr><td colspan="4" class="text-center py-5"><i class="bi bi-check-circle-fill text-success fs-2 d-block mb-2"></i><strong>Documentación al día</strong><span class="text-muted d-block">No hay pacientes con documentos pendientes para estos filtros.</span></td></tr>@endforelse
        </tbody>
    </table></div>@if($patients->hasPages())<div class="card-footer bg-white">{{ $patients->links() }}</div>@endif</div>
</div>
@include('audit._filters-script')
@endsection
