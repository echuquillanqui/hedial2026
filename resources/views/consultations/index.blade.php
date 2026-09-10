@extends('layouts.app')

@section('content')
@php
    $visibleIds = $consultations->pluck('id')->map(fn ($id) => (string) $id)->values();
    $fuaByConsultation = $consultations->mapWithKeys(fn ($item) => [(string) $item->id => $item->order?->fua?->id]);
@endphp
<div class="container-fluid py-4 nephrology-index" x-data="{ selected: [], format: 'consultation', fuaMap: @js($fuaByConsultation), applyFilters() { this.$refs.filters.requestSubmit() } }">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div><span class="overline">GESTIÓN CLÍNICA</span><h2 class="mb-1">Consultas nefrológicas</h2><p class="text-muted mb-0">Gestiona la consulta, receta médica y FUA desde una sola vista.</p></div>
        <a href="{{ route('orders.nephrology.create') }}" class="btn btn-success rounded-pill px-4"><i class="bi bi-plus-lg me-2"></i>Generar orden</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card filter-card mb-4"><div class="card-body p-3 p-lg-4"><form class="row g-3 align-items-end" x-ref="filters">
        <div class="col-xl-4 col-md-6"><label class="filter-label">Paciente</label><div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Nombre, apellido o DNI" @input.debounce.450ms="applyFilters()"></div></div>
        <div class="col-xl-2 col-md-3"><label class="filter-label">Fecha</label><input type="date" name="date" value="{{ request('date') }}" class="form-control" @change="applyFilters()"></div>
        <div class="col-xl-2 col-md-3"><label class="filter-label">Secuencia</label><select name="sequence" class="form-select" @change="applyFilters()"><option value="">Todas</option>@foreach($filterOptions['secuencia'] as $value)<option value="{{ $value }}" @selected((string) request('sequence') === (string) $value)>{{ $value }}</option>@endforeach</select></div>
        <div class="col-xl-1 col-md-3"><label class="filter-label">Turno</label><select name="shift" class="form-select" @change="applyFilters()"><option value="">Todos</option>@foreach($filterOptions['turno'] as $value)<option value="{{ $value }}" @selected((string) request('shift') === (string) $value)>{{ $value }}</option>@endforeach</select></div>
        <div class="col-xl-1 col-md-3"><label class="filter-label">Módulo</label><select name="module" class="form-select" @change="applyFilters()"><option value="">Todos</option>@foreach($filterOptions['modulo'] as $value)<option value="{{ $value }}" @selected((string) request('module') === (string) $value)>{{ $value }}</option>@endforeach</select></div>
        <div class="col-xl-2 col-md-4"><a href="{{ route('consultations.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-counterclockwise me-1"></i>Limpiar</a></div>
    </form></div></div>

    <div class="document-tabs mb-3" role="tablist" aria-label="Formato para imprimir">
        <button type="button" :class="{active: format === 'consultation'}" @click="format='consultation'"><i class="bi bi-journal-medical"></i> Consulta</button>
        <button type="button" :class="{active: format === 'prescription'}" @click="format='prescription'"><i class="bi bi-capsule"></i> Receta</button>
        <button type="button" :class="{active: format === 'fua'}" @click="format='fua'"><i class="bi bi-file-earmark-text"></i> FUA</button>
    </div>

    @can('nephrology.print')
    <form method="POST" :action="format === 'fua' ? @js(route('fuas.nephrology.bulk-pdf')) : @js(route('consultations.bulk-pdf'))" target="_blank">
        @csrf
        <input type="hidden" name="document_type" :value="format">
        <template x-for="id in selected" :key="id"><input type="hidden" :name="format === 'fua' ? 'fuas[]' : 'consultations[]'" :value="format === 'fua' ? fuaMap[id] : id" :disabled="format === 'fua' && !fuaMap[id]"></template>
        <div class="bulkbar mb-3" x-show="selected.length" x-cloak><span><strong x-text="selected.length"></strong> registro(s) seleccionado(s) · <span x-text="format === 'consultation' ? 'Consultas' : (format === 'prescription' ? 'Recetas' : 'FUA')"></span></span><button class="btn btn-light btn-sm fw-semibold" @click="if(format === 'fua' && selected.some(id => !fuaMap[id])) { $event.preventDefault(); alert('Uno o más registros seleccionados no tienen FUA.'); }"><i class="bi bi-printer me-2"></i>Imprimir bloque</button></div>
    </form>
    @endcan

    @can('nephrology.update')
    <form method="POST" action="{{ route('consultations.dates.update') }}" class="bulk-date-form mb-3" x-show="selected.length" x-cloak>
        @csrf @method('PATCH')
        <template x-for="id in selected" :key="`date-${id}`"><input type="hidden" name="consultations[]" :value="id"></template>
        <span><strong x-text="selected.length"></strong> consulta(s): asignar una misma fecha a la orden, consulta y FUA</span>
        <div class="d-flex gap-2"><input type="date" name="consultation_date" class="form-control form-control-sm" required><button class="btn btn-light btn-sm fw-semibold" onclick="return confirm('¿Actualizar la fecha de todos los registros seleccionados?')"><i class="bi bi-calendar-check me-2"></i>Actualizar fechas</button></div>
    </form>
    @endcan

    <div class="card table-card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th class="selector">@canany(['nephrology.print', 'nephrology.update'])<input class="form-check-input" type="checkbox" aria-label="Seleccionar visibles" @change="selected = $event.target.checked ? @js($visibleIds) : []">@endcanany</th><th>Fecha</th><th>Paciente</th><th>Programación</th><th>Médico</th><th class="text-end">Acciones</th></tr></thead>
        <tbody>@forelse($consultations as $item)<tr><td>@canany(['nephrology.print', 'nephrology.update'])<input class="form-check-input" type="checkbox" value="{{ $item->id }}" x-model="selected" aria-label="Seleccionar consulta de {{ $item->patient->full_name }}">@endcanany</td><td>@can('nephrology.update')<form method="POST" action="{{ route('consultations.date.update', $item) }}" class="date-editor">@csrf @method('PATCH')<input type="date" name="consultation_date" value="{{ $item->consultation_date?->format('Y-m-d') }}" class="form-control form-control-sm" aria-label="Fecha de consulta de {{ $item->patient->full_name }}" required><button class="btn btn-sm btn-outline-success" title="Guardar fecha de consulta y FUA"><i class="bi bi-calendar-check"></i></button></form>@else<strong>{{ $item->consultation_date?->format('d/m/Y') }}</strong>@endcan</td><td><div class="patient-name">{{ $item->patient->full_name }}</div><small>DNI {{ $item->patient->dni ?: '—' }} · H.C. {{ $item->patient->medical_history_number ?: '—' }}</small></td><td><span class="schedule-badge">{{ $item->patient->secuencia ?: '—' }}</span><small class="d-block mt-1">Turno {{ $item->patient->turno ?: '—' }} · Módulo {{ $item->patient->modulo ?: '—' }}</small></td><td>{{ $item->doctor?->name ?: 'Sin asignar' }}</td><td class="text-end text-nowrap"><a href="{{ route('consultations.edit', $item) }}" class="btn btn-sm btn-outline-success" title="Rellenar o editar"><i class="bi bi-pencil"></i></a> <a x-show="format === 'consultation'" target="_blank" href="{{ route('consultations.pdf', $item) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-pdf me-1"></i>Consulta</a> <a x-show="format === 'prescription'" target="_blank" href="{{ route('consultations.prescription.pdf', $item) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-pdf me-1"></i>Receta</a> @if($item->order?->fua)<a x-show="format === 'fua'" target="_blank" href="{{ route('fuas.pdf', $item->order->fua) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-file-earmark-pdf me-1"></i>FUA</a>@else<span x-show="format === 'fua'" class="text-muted small">Sin FUA</span>@endif</td></tr>@empty<tr><td colspan="6" class="empty-state"><i class="bi bi-journal-x"></i><strong>Sin consultas</strong><span>No hay registros que coincidan con los filtros.</span></td></tr>@endforelse</tbody>
    </table></div>@if($consultations->hasPages())<div class="card-footer bg-white">{{ $consultations->links() }}</div>@endif</div>
</div>
<style>
.nephrology-index{--clinical:#087f5b}.overline{font-size:11px;letter-spacing:2px;color:var(--clinical);font-weight:800}.nephrology-index h2{font-weight:800;color:#163d35}.filter-card,.table-card{border:0;border-radius:16px;box-shadow:0 8px 28px #183f3512;overflow:hidden}.filter-label{display:block;color:#52736c;font-size:10px;font-weight:800;letter-spacing:.08em;margin-bottom:5px;text-transform:uppercase}.filter-card .input-group-text{background:#fff;border-right:0}.filter-card .input-group .form-control{border-left:0}.document-tabs{display:flex;gap:6px;border-bottom:1px solid #dce8e4}.document-tabs button{border:0;background:transparent;color:#668078;padding:11px 20px;font-weight:700;border-bottom:3px solid transparent}.document-tabs button.active{color:var(--clinical);border-color:var(--clinical)}.document-tabs i{margin-right:7px}.table thead th{background:#f1f7f5;color:#52736c;font-size:10px;letter-spacing:.04em;text-transform:uppercase;padding:14px 12px;border:0}.table td{padding:15px 12px;border-color:#edf2f0}.patient-name{font-weight:750;color:#183f37}.table small{color:#82958f}.schedule-badge{display:inline-block;background:#e0f4ed;color:var(--clinical);border-radius:8px;padding:4px 8px;font-weight:800;font-size:12px}.bulkbar{background:linear-gradient(90deg,#07684c,#07966b);color:#fff;padding:12px 18px;border-radius:12px;display:flex;justify-content:space-between;align-items:center}.selector{width:42px}.empty-state{text-align:center!important;padding:55px!important;color:#82958f}.empty-state i,.empty-state strong,.empty-state span{display:block}.empty-state i{font-size:2rem}.form-control:focus,.form-select:focus{border-color:#63b99d;box-shadow:0 0 0 .2rem #087f5b1f}
.date-editor{display:flex;align-items:center;gap:5px;min-width:178px}.date-editor .form-control{min-width:135px}
.bulk-date-form{background:#e7f5ef;border:1px solid #b8dfd0;border-radius:12px;color:#185c48;padding:12px 18px;display:flex;justify-content:space-between;align-items:center;gap:15px}.bulk-date-form .form-control{min-width:145px}.bulk-date-form .btn{white-space:nowrap;color:#087f5b}
</style>
@endsection
