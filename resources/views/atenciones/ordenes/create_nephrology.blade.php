@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold text-primary text-uppercase mb-1"><i class="bi bi-clipboard2-pulse me-2"></i>Consultas nefrológicas</h4>
            <small class="text-muted">Genera la orden y la FUA de consulta, sin crear hojas de hemodiálisis ni registros de laboratorio.</small>
        </div>
        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary fw-bold"><i class="bi bi-arrow-left me-1"></i> VOLVER</a>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body bg-light">
            <form method="GET" action="{{ route('orders.nephrology.create') }}" class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-bold text-primary text-uppercase">Secuencia</label>
                    <select name="secuencia" class="form-select border-primary">
                        <option value="">Todas las secuencias</option>
                        <option value="L-M-V" @selected(request('secuencia') === 'L-M-V')>L-M-V</option>
                        <option value="M-J-S" @selected(request('secuencia') === 'M-J-S')>M-J-S</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-bold text-primary text-uppercase">Turno</label>
                    <select name="turno" class="form-select border-primary">
                        <option value="">Todos los turnos</option>
                        @foreach(range(1, 4) as $shift)
                            <option value="{{ $shift }}" @selected(request('turno') == $shift)>Turno {{ $shift }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label small fw-bold text-primary text-uppercase">Módulo</label>
                    <select name="modulo" class="form-select border-primary">
                        <option value="">Todos los módulos</option>
                        @foreach(range(1, 4) as $module)
                            <option value="{{ $module }}" @selected(request('modulo') == $module)>Módulo {{ $module }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="form-label small fw-bold text-primary text-uppercase">DNI, H.C. o nombre</label>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control border-primary" placeholder="Buscar paciente">
                </div>
                <div class="col-lg-2 d-grid gap-1">
                    <button class="btn btn-primary fw-bold"><i class="bi bi-funnel me-1"></i> FILTRAR</button>
                    <a href="{{ route('orders.nephrology.create') }}" class="btn btn-sm btn-outline-secondary">Mostrar todos</a>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('orders.nephrology.store') }}" x-data="{
        selected: @js(collect(old('patient_ids', []))->map(fn ($id) => (string) $id)->values()),
        patientQuery: '',
        normalize(value) { return value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); },
        matches(value) { return this.normalize(value).includes(this.normalize(this.patientQuery)); },
        get visibleIds() {
            return [...document.querySelectorAll('[data-nephrology-patient]')]
                .filter(input => this.matches(input.dataset.nephrologyPatient))
                .map(input => input.value);
        },
        toggleVisible(checked) {
            this.selected = checked
                ? [...new Set([...this.selected, ...this.visibleIds])]
                : this.selected.filter(id => !this.visibleIds.includes(id));
        }
    }">
        @csrf
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-bold text-uppercase">Seleccionar pacientes ({{ $patients->count() }})</span>
                <div class="d-flex align-items-center gap-2">
                    <label for="fecha_orden" class="small fw-bold mb-0">FECHA:</label>
                    <input id="fecha_orden" type="date" name="fecha_orden" value="{{ old('fecha_orden', date('Y-m-d')) }}" class="form-control form-control-sm" required>
                    <button type="submit" class="btn btn-light btn-sm text-primary fw-bold" onclick="return confirm('¿Generar las consultas nefrológicas seleccionadas?')">
                        <i class="bi bi-file-earmark-plus me-1"></i> GENERAR
                    </button>
                </div>
            </div>
            @if($errors->any())
                <div class="alert alert-danger rounded-0 mb-0">{{ $errors->first() }}</div>
            @endif
            <div class="card-body border-bottom py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-9">
                        <label for="nephrologyPatientQuery" class="form-label small fw-bold text-primary text-uppercase">Buscar dentro de los resultados</label>
                        <input id="nephrologyPatientQuery" type="search" x-model="patientQuery" class="form-control border-primary" placeholder="DNI, H.C. o nombre del paciente">
                    </div>
                    <div class="col-md-3">
                        <div class="form-check border rounded p-2 ps-5 bg-light">
                            <input id="selectVisibleNephrology" type="checkbox" class="form-check-input" @change="toggleVisible($el.checked)" :checked="visibleIds.length > 0 && visibleIds.every(id => selected.includes(id))">
                            <label for="selectVisibleNephrology" class="form-check-label fw-bold">Seleccionar visibles</label>
                        </div>
                    </div>
                </div>
                <small class="text-muted">La selección masiva aplica solo a los pacientes visibles; las selecciones anteriores se conservan al cambiar la búsqueda.</small>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th class="text-center">SEL.</th><th>PACIENTE</th><th>DNI</th><th>H.C.</th><th>TURNO</th></tr></thead>
                    <tbody>
                        @forelse($patients as $patient)
                            <tr x-show="matches(@js($patient->full_name.' '.$patient->dni.' '.$patient->medical_history_number))">
                                <td class="text-center"><input type="checkbox" name="patient_ids[]" value="{{ $patient->id }}" x-model="selected" data-nephrology-patient="{{ mb_strtolower($patient->full_name.' '.$patient->dni.' '.$patient->medical_history_number) }}" class="form-check-input border-primary"></td>
                                <td class="fw-bold text-uppercase small">{{ $patient->surname }} {{ $patient->last_name }}, {{ $patient->first_name }} {{ $patient->other_names }}</td>
                                <td>{{ $patient->dni ?? '-' }}</td>
                                <td>{{ $patient->medical_history_number ?? '-' }}</td>
                                <td>{{ $patient->turno ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">No se encontraron pacientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white text-muted small">Puede generar órdenes para todos los pacientes filtrados o únicamente para los que seleccione.</div>
        </div>
    </form>
</div>
@endsection
