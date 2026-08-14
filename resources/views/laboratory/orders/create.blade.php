@extends('layouts.app')

@section('content')
<style>
    .laboratory-batch .section-header { background: #198754; color: #fff; font-size: .82rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; }
    .laboratory-batch .data-title { color: #198754; display: block; font-size: .68rem; font-weight: 700; margin-bottom: .3rem; text-transform: uppercase; }
    .laboratory-batch .sticky-summary { position: sticky; top: 1rem; }
    .laboratory-batch .patient-list { max-height: 510px; overflow-y: auto; }
    .laboratory-batch .patient-row { cursor: pointer; }
    .laboratory-batch .patient-row:hover { background: #f1f8f4; }
    .laboratory-batch .period-selector .btn { font-size: .62rem; padding: .35rem .25rem; }
</style>

<div class="container-fluid px-4 py-3 laboratory-batch" x-data="laboratoryBatchForm({
    tests: @js($tests->map(fn ($test) => ['id' => $test->id, 'name' => $test->name, 'frequency' => $test->frequency])->values()),
    initialSchedules: @js(old('schedules', [['sampled_at' => date('Y-m-d'), 'period' => 'M']])),
    initialPatients: @js(collect(old('patient_ids', []))->map(fn ($id) => (string) $id)->values()),
})">
    <div class="card border-0 shadow-sm mb-3 bg-light">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h5 class="m-0 text-success fw-bold text-uppercase"><i class="bi bi-clipboard2-pulse me-1"></i> Generación masiva de laboratorios</h5>
                    <small class="text-muted">Seleccione el bloque programado y configure las fechas de toma.</small>
                </div>
                <a href="{{ route('laboratory.results.index') }}" class="btn btn-sm btn-outline-secondary fw-bold"><i class="bi bi-arrow-left me-1"></i> Ver resultados</a>
            </div>

            <form action="{{ route('laboratory.orders.create') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="data-title">Secuencia programada</label>
                    <select name="secuencia" class="form-select border-success shadow-sm">
                        <option value="L-M-V" {{ $sequence === 'L-M-V' ? 'selected' : '' }}>L-M-V (lunes, miércoles y viernes)</option>
                        <option value="M-J-S" {{ $sequence === 'M-J-S' ? 'selected' : '' }}>M-J-S (martes, jueves y sábado)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="data-title">Turno</label>
                    <select name="turno" class="form-select border-success shadow-sm">
                        <option value="" {{ $shift === null ? 'selected' : '' }}>Todos los turnos</option>
                        @foreach(['1' => '1.er turno', '2' => '2.do turno', '3' => '3.er turno', '4' => '4.to turno'] as $value => $label)
                            <option value="{{ $value }}" {{ $shift === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-success w-100 fw-bold shadow-sm"><i class="bi bi-person-check-fill me-1"></i> Filtrar</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('laboratory.orders.create') }}" class="btn btn-outline-secondary w-100 fw-bold">Hoy</a>
                </div>
            </form>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger"><strong>No se pudo generar el bloque.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('laboratory.orders.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header section-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                        <span>Pacientes · {{ $sequence ?: 'sin secuencia para hoy' }}{{ $shift ? ' · turno '.$shift : ' · todos los turnos' }}</span>
                        <label class="form-check m-0">
                            <input type="checkbox" class="form-check-input" @change="toggleVisiblePatients($el.checked)" :checked="allVisibleSelected">
                            <span class="form-check-label">Seleccionar visibles</span>
                        </label>
                    </div>
                    <div class="card-body border-bottom py-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="search" class="form-control" x-model="patientQuery" placeholder="Buscar en el bloque por DNI, H.C. o nombre">
                        </div>
                        <small class="text-muted d-block mt-2">{{ $patients->count() }} paciente(s) encontrados en la programación seleccionada.</small>
                    </div>
                    <div class="table-responsive patient-list">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top"><tr><th class="text-center">Sel.</th><th>Apellidos y nombres</th><th class="text-center">H.C.</th><th class="text-center">Turno</th><th class="text-center">Módulo</th></tr></thead>
                            <tbody>
                            @forelse($patients as $patient)
                                <tr class="patient-row" x-show="matchesPatient(@js($patient->full_name.' '.$patient->dni.' '.$patient->medical_history_number))">
                                    <td class="text-center"><input class="form-check-input border-success" type="checkbox" name="patient_ids[]" value="{{ $patient->id }}" x-model="selectedPatients" data-patient-search="{{ mb_strtolower($patient->full_name.' '.$patient->dni.' '.$patient->medical_history_number) }}"></td>
                                    <td><div class="fw-bold text-uppercase small">{{ $patient->full_name }}</div><small class="text-muted">DNI: {{ $patient->dni ?: '—' }}</small></td>
                                    <td class="text-center small">{{ $patient->medical_history_number ?: '—' }}</td>
                                    <td class="text-center"><span class="badge bg-light text-success border border-success">T{{ $patient->turno }}</span></td>
                                    <td class="text-center small">{{ $patient->modulo ? 'Módulo '.$patient->modulo : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-people fs-1 d-block mb-2"></i>No hay pacientes en esta secuencia y turno.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="sticky-summary">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header section-header py-3">Datos de generación</div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-semibold">Pacientes seleccionados</span>
                                <span class="badge text-bg-success fs-6" x-text="selectedPatients.length"></span>
                            </div>
                            <label class="data-title">Solicitado por</label>
                            <input type="text" name="requested_by" class="form-control border-success mb-3" value="{{ old('requested_by', auth()->user()?->name) }}" maxlength="120">

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="data-title mb-0">Fechas y grupos de exámenes</label>
                                <button type="button" class="btn btn-sm btn-outline-success" @click="addSchedule()"><i class="bi bi-plus-lg"></i> Agregar</button>
                            </div>
                            <template x-for="(schedule, index) in schedules" :key="schedule.key">
                                <div class="row g-2 align-items-center border rounded p-2 mb-2 bg-light">
                                    <div class="col-6"><input type="date" class="form-control form-control-sm border-success" :name="`schedules[${index}][sampled_at]`" x-model="schedule.sampled_at" required></div>
                                    <div class="col-5">
                                        <div class="btn-group w-100 period-selector" role="group" aria-label="Frecuencia de exámenes">
                                            <input type="hidden" :name="`schedules[${index}][period]`" x-model="schedule.period">
                                            <button type="button" class="btn btn-outline-success fw-bold" :class="periodIncludes(schedule.period, 'M') && 'active'" :aria-pressed="periodIncludes(schedule.period, 'M')" @click="schedule.period = 'M'" title="Mensual">Mensual</button>
                                            <button type="button" class="btn btn-outline-success fw-bold" :class="periodIncludes(schedule.period, 'B') && 'active'" :aria-pressed="periodIncludes(schedule.period, 'B')" @click="schedule.period = 'B'" title="Bimestral: incluye mensual y bimestral">Bimestral</button>
                                            <button type="button" class="btn btn-outline-success fw-bold" :class="periodIncludes(schedule.period, 'T') && 'active'" :aria-pressed="periodIncludes(schedule.period, 'T')" @click="schedule.period = 'T'" title="Trimestral: incluye mensual, bimestral y trimestral">Trimestral</button>
                                            <button type="button" class="btn btn-outline-success fw-bold" :class="periodIncludes(schedule.period, 'S') && 'active'" :aria-pressed="periodIncludes(schedule.period, 'S')" @click="schedule.period = 'S'" title="Semestral: incluye todos los grupos">Semestral</button>
                                        </div>
                                    </div>
                                    <div class="col-1 text-end"><button type="button" class="btn btn-sm p-0 text-danger" @click="removeSchedule(index)" :disabled="schedules.length === 1" title="Quitar"><i class="bi bi-trash"></i></button></div>
                                    <div class="col-12"><small class="text-success" x-text="`${testsFor(schedule.period).length} exámenes incluidos`"></small></div>
                                </div>
                            </template>

                            <div class="alert alert-success py-2 mt-3 mb-3" x-show="selectedPatients.length"><small><strong x-text="selectedPatients.length * schedules.length"></strong> órdenes serán generadas con el patrón indicado.</small></div>
                            <button class="btn btn-success btn-lg w-100 fw-bold shadow" :disabled="!selectedPatients.length || !schedules.length" onclick="return confirm('¿Generar las órdenes de laboratorio del patrón indicado?')"><i class="bi bi-gear-fill me-2"></i>Generar ahora</button>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm"><div class="card-body"><span class="data-title">Exámenes incluidos</span><template x-for="schedule in schedules" :key="`summary-${schedule.key}`"><div class="small mb-2"><strong x-text="periodName(schedule.period)"></strong>: <span x-text="testsFor(schedule.period).map(test => test.name).join(', ')"></span></div></template></div></div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function laboratoryBatchForm({ tests, initialSchedules, initialPatients }) {
    return {
        tests,
        schedules: initialSchedules.map((item, index) => ({ ...item, key: `${Date.now()}-${index}` })),
        selectedPatients: initialPatients,
        patientQuery: '',
        addSchedule() { this.schedules.push({ sampled_at: '{{ date('Y-m-d') }}', period: 'M', key: `${Date.now()}-${Math.random()}` }); },
        removeSchedule(index) { if (this.schedules.length > 1) this.schedules.splice(index, 1); },
        periodName(period) { return { M: 'Mensual', B: 'Bimestral', T: 'Trimestral', S: 'Semestral' }[period]; },
        periodIncludes(period, frequency) { const periods = ['M', 'B', 'T', 'S']; return periods.indexOf(frequency) <= periods.indexOf(period); },
        testsFor(period) { const frequencies = { M: ['M'], B: ['M', 'B'], T: ['M', 'B', 'T'], S: ['M', 'B', 'T', 'S'] }[period] || []; return this.tests.filter(test => frequencies.includes(test.frequency)); },
        normalize(value) { return String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); },
        matchesPatient(value) { return this.normalize(value).includes(this.normalize(this.patientQuery)); },
        get visiblePatientInputs() { return [...document.querySelectorAll('[data-patient-search]')].filter(input => this.matchesPatient(input.dataset.patientSearch)); },
        get allVisibleSelected() { return this.visiblePatientInputs.length > 0 && this.visiblePatientInputs.every(input => this.selectedPatients.includes(input.value)); },
        toggleVisiblePatients(checked) { const ids = this.visiblePatientInputs.map(input => input.value); this.selectedPatients = checked ? [...new Set([...this.selectedPatients, ...ids])] : this.selectedPatients.filter(id => !ids.includes(id)); },
    };
}
</script>
@endsection
