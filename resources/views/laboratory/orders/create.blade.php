@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="laboratoryBatchForm({
    tests: @js($tests->map(fn ($test) => ['id' => $test->id, 'name' => $test->name, 'frequency' => $test->frequency])->values()),
    initialSchedules: @js(old('schedules', [['sampled_at' => date('Y-m-d'), 'period' => 'M']])),
    initialPatients: @js(collect(old('patient_ids', []))->map(fn ($id) => (string) $id)->values()),
})">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h3 class="mb-1">Generación masiva de laboratorios</h3>
            <p class="text-muted mb-0">Selecciona los pacientes y define el patrón de fechas de toma de muestra.</p>
        </div>
        <a href="{{ route('laboratory.results.index') }}" class="btn btn-outline-secondary">Ver resultados</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger"><strong>No se pudo generar el bloque.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('laboratory.orders.store') }}">
        @csrf
        <div class="row g-4">
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div><strong>1. Pacientes</strong><small class="text-muted d-block">Puede generar el bloque para todos.</small></div>
                        <span class="badge text-bg-success" x-text="`${selectedPatients.length} seleccionados`"></span>
                    </div>
                    <div class="card-body">
                        <input type="search" class="form-control mb-3" x-model="patientQuery" placeholder="Buscar por DNI, H.C. o nombre">
                        <label class="form-check border-bottom pb-2 mb-2 fw-semibold">
                            <input type="checkbox" class="form-check-input" @change="toggleVisiblePatients($el.checked)" :checked="allVisibleSelected">
                            Seleccionar todos los pacientes visibles
                        </label>
                        <div style="max-height: 480px; overflow-y: auto">
                            @foreach($patients as $patient)
                                <label class="form-check py-2 border-bottom patient-row" x-show="matchesPatient(@js($patient->full_name.' '.$patient->dni.' '.$patient->medical_history_number))">
                                    <input class="form-check-input" type="checkbox" name="patient_ids[]" value="{{ $patient->id }}" x-model="selectedPatients" data-patient-search="{{ mb_strtolower($patient->full_name.' '.$patient->dni.' '.$patient->medical_history_number) }}">
                                    <span class="form-check-label"><strong>{{ $patient->full_name }}</strong><small class="text-muted d-block">DNI: {{ $patient->dni ?: '—' }} · H.C.: {{ $patient->medical_history_number ?: '—' }}</small></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div><strong>2. Patrón de toma de muestras</strong><small class="text-muted d-block">Cada fecha genera una orden para cada paciente seleccionado.</small></div>
                        <button type="button" class="btn btn-sm btn-outline-success" @click="addSchedule()"><i class="bi bi-plus-lg me-1"></i>Agregar fecha</button>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 align-items-center mb-2" x-show="schedules.length">
                            <div class="col-5 small fw-semibold text-muted">FECHA DE MUESTRA</div><div class="col-5 small fw-semibold text-muted">GRUPO DE EXÁMENES</div>
                        </div>
                        <template x-for="(schedule, index) in schedules" :key="schedule.key">
                            <div class="row g-2 align-items-center mb-3">
                                <div class="col-5"><input type="date" class="form-control" :name="`schedules[${index}][sampled_at]`" x-model="schedule.sampled_at" required></div>
                                <div class="col-5"><select class="form-select" :name="`schedules[${index}][period]`" x-model="schedule.period" required><option value="M">Mensual</option><option value="B">Bimestral (incluye mensual)</option><option value="T">Trimestral (acumulado)</option><option value="S">Semestral (todos)</option></select></div>
                                <div class="col-2 text-end"><button type="button" class="btn btn-outline-danger" @click="removeSchedule(index)" :disabled="schedules.length === 1" title="Quitar fecha"><i class="bi bi-trash"></i></button></div>
                                <div class="col-12"><small class="text-success" x-text="`${testsFor(schedule.period).length} exámenes seleccionados automáticamente`"></small></div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3"><strong>3. Exámenes incluidos por defecto</strong><small class="text-muted d-block">La frecuencia elegida es acumulativa. Sodio, potasio y cloro se registran separados para completar resultados, pero se agrupan como Perfil de electrolitos en la FUA.</small></div>
                    <div class="card-body">
                        <template x-for="(schedule, index) in schedules" :key="`summary-${schedule.key}`">
                            <div class="mb-3"><div class="fw-semibold mb-2"><span x-text="schedule.sampled_at || 'Sin fecha'"></span> · <span x-text="periodName(schedule.period)"></span></div><div class="d-flex flex-wrap gap-1"><template x-for="test in testsFor(schedule.period)" :key="test.id"><span class="badge text-bg-light border fw-normal" x-text="test.name"></span></template></div></div>
                        </template>
                    </div>
                </div>

                <div class="card border-0 shadow-sm"><div class="card-body d-flex justify-content-between align-items-end gap-3 flex-wrap"><div class="flex-grow-1"><label class="form-label fw-semibold">Solicitado por</label><input type="text" name="requested_by" class="form-control" value="{{ old('requested_by', auth()->user()?->name) }}" maxlength="120"></div><button class="btn btn-success px-4" :disabled="!selectedPatients.length || !schedules.length" onclick="return confirm('¿Generar las órdenes de laboratorio del patrón indicado?')"><i class="bi bi-collection me-2"></i>Generar bloque</button></div></div>
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
        addSchedule() { this.schedules.push({ sampled_at: '', period: 'M', key: `${Date.now()}-${Math.random()}` }); },
        removeSchedule(index) { if (this.schedules.length > 1) this.schedules.splice(index, 1); },
        periodName(period) { return { M: 'Mensual', B: 'Bimestral', T: 'Trimestral', S: 'Semestral' }[period]; },
        testsFor(period) {
            const frequencies = { M: ['M'], B: ['M', 'B'], T: ['M', 'B', 'T'], S: ['M', 'B', 'T', 'S'] }[period] || [];
            return this.tests.filter(test => frequencies.includes(test.frequency));
        },
        normalize(value) { return String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''); },
        matchesPatient(value) { return this.normalize(value).includes(this.normalize(this.patientQuery)); },
        get visiblePatientInputs() { return [...document.querySelectorAll('[data-patient-search]')].filter(input => this.matchesPatient(input.dataset.patientSearch)); },
        get allVisibleSelected() { return this.visiblePatientInputs.length > 0 && this.visiblePatientInputs.every(input => this.selectedPatients.includes(input.value)); },
        toggleVisiblePatients(checked) {
            const ids = this.visiblePatientInputs.map(input => input.value);
            this.selectedPatients = checked ? [...new Set([...this.selectedPatients, ...ids])] : this.selectedPatients.filter(id => !ids.includes(id));
        },
    };
}
</script>
@endsection
