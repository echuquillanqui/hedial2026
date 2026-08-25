@extends('layouts.app')

@section('content')
<style>
    .bulk-title, .data-title { color: #198754; }
    .data-title { display: block; font-size: .68rem; font-weight: 800; margin-bottom: .25rem; text-transform: uppercase; }
    .bulk-header { background: linear-gradient(135deg, #198754, #157347); color: #fff; }
    .sticky-config { position: sticky; top: 1rem; }
</style>
<div class="container-fluid px-4 py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold text-uppercase bulk-title mb-1"><i class="bi bi-collection me-2"></i>Generación de órdenes · {{ \App\Support\ClinicalService::label($type) }}</h4>
            <p class="text-muted mb-0">Filtre pacientes y genere una o varias órdenes con una sola configuración.</p>
        </div>
        <a class="btn btn-outline-secondary fw-bold" href="{{ route('orders.multisectorial.index', ['type' => $type]) }}"><i class="bi bi-arrow-left me-1"></i> Volver</a>
    </div>

    @if($errors->any())<div class="alert alert-danger"><strong>No fue posible generar las órdenes.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light">
            <form method="GET" action="{{ route('orders.multisectorial.create') }}" class="row g-2 align-items-end">
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="col-lg-4"><label class="data-title">Paciente</label><input name="search" class="form-control border-success" value="{{ request('search') }}" placeholder="DNI, H.C., nombres o apellidos"></div>
                <div class="col-lg-2"><label class="data-title">Secuencia programada</label><select name="secuencia" class="form-select border-success"><option value="">Todas</option>@foreach(['L-M-V','M-J-S'] as $value)<option value="{{ $value }}" @selected(request('secuencia')===$value)>{{ $value }}</option>@endforeach</select></div>
                <div class="col-lg-2"><label class="data-title">Turno</label><select name="turno" class="form-select border-success"><option value="">Todos</option>@foreach(range(1,4) as $value)<option value="{{ $value }}" @selected((string)request('turno')===(string)$value)>{{ $value }}.° turno</option>@endforeach</select></div>
                <div class="col-lg-2"><label class="data-title">Módulo</label><select name="modulo" class="form-select border-success"><option value="">Todos</option>@foreach(range(1,4) as $value)<option value="{{ $value }}" @selected((string)request('modulo')===(string)$value)>Módulo {{ $value }}</option>@endforeach</select></div>
                <div class="col-lg-1"><button class="btn btn-success w-100 fw-bold"><i class="bi bi-funnel"></i> Filtrar</button></div>
                <div class="col-lg-1"><a class="btn btn-outline-secondary w-100 fw-bold" href="{{ route('orders.multisectorial.create',['type'=>$type]) }}"><i class="bi bi-x-circle"></i> Limpiar</a></div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('orders.multisectorial.store-bulk') }}" x-data="{
        selected: @js(collect(old('patient_ids', []))->map(fn($id)=>(string)$id)->values()),
        query: '',
        normalize(value) { return value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); },
        matches(value) { return this.normalize(value).includes(this.normalize(this.query)); },
        get visibleIds() { return [...document.querySelectorAll('[data-patient-search]')].filter(el => this.matches(el.dataset.patientSearch)).map(el => el.value); },
        toggleVisible(checked) { this.selected = checked ? [...new Set([...this.selected, ...this.visibleIds])] : this.selected.filter(id => !this.visibleIds.includes(id)); }
    }">
        @csrf<input type="hidden" name="type" value="{{ $type }}">
        <div class="row g-3">
            <div class="col-lg-3"><div class="sticky-config">
                <div class="card shadow-sm border-0">
                    <div class="card-header bulk-header py-3 fw-bold text-uppercase"><i class="bi bi-sliders me-1"></i> Datos de generación</div>
                    <div class="card-body">
                        <div class="mb-3"><label class="data-title">Profesional responsable</label><select name="assigned_professional_id" class="form-select border-success" required><option value="">Seleccione</option>@foreach($professionals as $professional)<option value="{{ $professional->id }}" @selected((string)old('assigned_professional_id')===(string)$professional->id)>{{ $professional->name }}</option>@endforeach</select></div>
                        <div class="mb-3"><label class="data-title">Fecha programada</label><input type="date" name="fecha_orden" class="form-control border-success" value="{{ old('fecha_orden',today()->toDateString()) }}" required></div>
                        <div class="alert alert-success py-2" x-show="selected.length"><strong x-text="selected.length"></strong> paciente(s) seleccionado(s).</div>
                        <button class="btn btn-success btn-lg w-100 fw-bold shadow-sm" :disabled="selected.length === 0" onclick="return confirm('¿Generar las órdenes para todos los pacientes seleccionados?')"><i class="bi bi-gear-fill me-1"></i> Generar en bloque</button>
                        <small class="text-muted d-block mt-3">La periodicidad y fecha límite se calculan automáticamente para cada paciente.</small>
                    </div>
                </div>
            </div></div>
            <div class="col-lg-9">
                <div class="card shadow-sm border-0">
                    <div class="card-header bulk-header d-flex justify-content-between align-items-center py-3"><strong>Pacientes disponibles ({{ $patients->count() }})</strong><label class="form-check mb-0"><input class="form-check-input" type="checkbox" @change="toggleVisible($el.checked)" :checked="visibleIds.length && visibleIds.every(id => selected.includes(id))"> <span class="form-check-label fw-bold">Seleccionar visibles</span></label></div>
                    <div class="card-body border-bottom"><label class="data-title">Buscar dentro de los resultados</label><input type="search" x-model="query" class="form-control border-success" placeholder="Escriba DNI, H.C. o nombre"></div>
                    <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="text-center">Sel.</th><th>Paciente</th><th class="text-center">DNI / H.C.</th><th class="text-center">Secuencia</th><th class="text-center">Turno</th><th class="text-center">Módulo</th></tr></thead><tbody>
                    @forelse($patients as $patient)
                        @php($search = mb_strtolower($patient->full_name.' '.$patient->dni.' '.$patient->medical_history_number))
                        <tr x-show="matches(@js($search))"><td class="text-center"><input type="checkbox" class="form-check-input border-success" name="patient_ids[]" value="{{ $patient->id }}" x-model="selected" data-patient-search="{{ $search }}"></td><td><strong class="text-uppercase small">{{ $patient->full_name }}</strong></td><td class="text-center small">{{ $patient->dni ?: '—' }}<br><span class="text-muted">{{ $patient->medical_history_number ?: '—' }}</span></td><td class="text-center"><span class="badge bg-light text-dark border">{{ $patient->secuencia ?: '—' }}</span></td><td class="text-center">{{ $patient->turno ?: '—' }}</td><td class="text-center">{{ $patient->modulo ?: '—' }}</td></tr>
                    @empty<tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-people fs-2 d-block mb-2"></i>No hay pacientes para los filtros seleccionados.</td></tr>@endforelse
                    </tbody></table></div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
