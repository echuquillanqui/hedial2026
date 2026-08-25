@extends('layouts.app')
@section('content')
<div class="container-fluid py-4 consent-index" x-data="{ selected: [], applyFilters() { this.$refs.filters.requestSubmit() } }">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div><span class="overline">GESTIÓN CLÍNICA</span><h2 class="mb-1">Consentimientos de hemodiálisis</h2><p class="text-muted mb-0">Consulta, organiza e imprime los consentimientos registrados.</p></div>
        @can('consents.create')<a class="btn btn-success rounded-pill px-4" href="{{ route('consents.create') }}"><i class="bi bi-plus-lg me-2"></i>Registro excepcional</a>@endcan
    </div>

    <div class="card filter-card mb-4"><div class="card-body p-3 p-lg-4">
        <form class="row g-3 align-items-end" x-ref="filters" id="consent-filters">
            <div class="col-xl-4 col-md-6"><label for="consent-search" class="filter-label">Paciente</label><div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input id="consent-search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Nombre, apellido o DNI" @input.debounce.450ms="applyFilters()"></div></div>
            <div class="col-xl-2 col-md-3"><label for="consent-date" class="filter-label">Fecha</label><input id="consent-date" type="date" class="form-control" name="date" value="{{ $date }}" @change="applyFilters()"></div>
            <div class="col-xl-2 col-md-3"><label for="consent-sequence" class="filter-label">Secuencia</label><select id="consent-sequence" name="sequence" class="form-select" @change="applyFilters()"><option value="">Todas</option><option value="L-M-V" @selected(request('sequence') === 'L-M-V')>L-M-V</option><option value="M-J-S" @selected(request('sequence') === 'M-J-S')>M-J-S</option></select></div>
            <div class="col-xl-2 col-md-4"><label for="consent-shift" class="filter-label">Turno</label><select id="consent-shift" name="shift" class="form-select" @change="applyFilters()"><option value="">Todos</option>@foreach(range(1, 4) as $shift)<option value="{{ $shift }}" @selected((string) request('shift') === (string) $shift)>Turno {{ $shift }}</option>@endforeach</select></div>
            <div class="col-xl-2 col-md-4"><a href="{{ route('consents.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Limpiar</a></div>
        </form>
    </div></div>

    <form method="POST" action="{{ route('consents.bulk-pdf') }}" target="_blank">@csrf
        @can('consents.print')<div class="bulkbar mb-3" x-show="selected.length" x-cloak><span><strong x-text="selected.length"></strong> consentimiento(s) seleccionado(s)</span><button class="btn btn-light btn-sm fw-semibold"><i class="bi bi-printer me-2"></i>Imprimir selección</button></div>@endcan
        <div class="card table-card"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th class="selector">@can('consents.print')<input class="form-check-input" type="checkbox" aria-label="Seleccionar todos" @change="selected = $event.target.checked ? @js($consents->pluck('id')->map(fn ($id) => (string) $id)->values()) : []">@endcan</th><th>Paciente</th><th>Programación</th><th>Fecha</th><th>Versión</th><th>Decisión</th><th>Médico responsable</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>@forelse($consents as $consent)<tr>
                <td>@can('consents.print')<input class="form-check-input" name="consent_ids[]" value="{{ $consent->id }}" type="checkbox" x-model="selected" aria-label="Seleccionar consentimiento de {{ $consent->patient->full_name }}">@endcan</td>
                <td><div class="patient-name">{{ $consent->patient->full_name }}</div><small>DNI {{ $consent->patient->dni ?: '—' }} · H.C. {{ $consent->patient->medical_history_number ?: '—' }}</small></td>
                <td><span class="schedule-badge">{{ $consent->patient->secuencia ?: '—' }}</span><small class="d-block mt-1">Turno {{ $consent->patient->turno ?: '—' }}</small></td>
                <td><strong>{{ $consent->consented_at->format('d/m/Y') }}</strong><small class="d-block">{{ $consent->consented_at->format('H:i') }}</small></td><td>{{ $consent->version }}</td>
                <td><span class="status {{ $consent->accepted ? 'accepted' : 'declined' }}"><i class="bi {{ $consent->accepted ? 'bi-check-circle' : 'bi-x-circle' }} me-1"></i>{{ $consent->accepted ? 'Acepta' : 'No acepta' }}</span></td>
                <td>{{ $consent->physician?->name ?: 'Sin asignar' }}</td><td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-success" href="{{ route('consents.show', $consent) }}" title="Ver detalle"><i class="bi bi-eye"></i></a> @can('consents.print')<a class="btn btn-sm btn-outline-dark" target="_blank" href="{{ route('consents.pdf', $consent) }}" title="Imprimir PDF"><i class="bi bi-file-earmark-pdf"></i></a>@endcan</td>
            </tr>@empty<tr><td colspan="8" class="empty-state"><i class="bi bi-file-earmark-text"></i><strong>Sin consentimientos</strong><span>No hay registros que coincidan con los filtros seleccionados.</span></td></tr>@endforelse</tbody>
        </table></div></div>
    </form><div class="mt-3">{{ $consents->links() }}</div>
</div>
<style>
.consent-index{--clinical:#087f5b}.consent-index .overline{font-size:11px;letter-spacing:2px;color:var(--clinical);font-weight:800}.consent-index h2{font-weight:800;color:#163d35}.filter-card,.table-card{border:0;border-radius:16px;box-shadow:0 8px 28px #183f3512;overflow:hidden}.filter-label{display:block;color:#52736c;font-size:10px;font-weight:800;letter-spacing:.08em;margin-bottom:5px;text-transform:uppercase}.filter-card .input-group-text{background:#fff;border-right:0;color:#6b817b}.filter-card .input-group .form-control{border-left:0}.table thead th{background:#f1f7f5;color:#52736c;font-size:10px;letter-spacing:.04em;text-transform:uppercase;padding:14px 12px;border:0;white-space:nowrap}.table td{padding:15px 12px;border-color:#edf2f0}.patient-name{font-weight:750;color:#183f37}.table small{color:#82958f}.schedule-badge{display:inline-block;background:#e0f4ed;color:var(--clinical);border-radius:8px;padding:4px 8px;font-weight:800;font-size:12px}.status{padding:6px 10px;border-radius:20px;font-size:11px;font-weight:750;white-space:nowrap}.status.accepted{background:#dff5e9;color:#18794e}.status.declined{background:#fde8e8;color:#b42318}.bulkbar{background:linear-gradient(90deg,#07684c,#07966b);color:#fff;padding:12px 18px;border-radius:12px;display:flex;justify-content:space-between;align-items:center}.selector{width:42px}.empty-state{text-align:center!important;padding:55px!important;color:#82958f}.empty-state i{display:block;font-size:2.2rem;margin-bottom:8px}.empty-state strong,.empty-state span{display:block}.empty-state strong{color:#385a51;font-size:1rem}.form-control:focus,.form-select:focus{border-color:#63b99d;box-shadow:0 0 0 .2rem #087f5b1f}
</style>
@endsection
