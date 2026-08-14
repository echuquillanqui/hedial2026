@extends('layouts.app')

@section('content')
<div class="container-fluid lab-page" x-data="fissalOrder(@js($tests), @js(old('test_ids', [])))">
    <div class="lab-hero mb-4">
        <div><span class="eyebrow">LABORATORIO FISSAL</span><h2>Generación de órdenes</h2><p>Programa los controles periódicos y elige uno o varios pacientes.</p></div>
        <i class="bi bi-droplet-half"></i>
    </div>
    <form method="POST" action="{{ route('laboratory.orders.store') }}">@csrf
        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card lab-card h-100"><div class="card-body p-4">
                    <h5><i class="bi bi-calendar2-check me-2"></i>Datos de generación</h5>
                    <label class="form-label mt-3">Periodicidad</label>
                    <select name="period" class="form-select" x-model="period" @change="applyPeriod()" required>
                        <option value="M">Mensual · M</option><option value="B">Bimensual · M + B</option>
                        <option value="T">Trimestral · M + B + T</option><option value="S">Semestral · M + B + T + S</option>
                    </select>
                    <div class="frequency-flow"><span>M</span><span :class="{active: level >= 2}">B</span><span :class="{active: level >= 3}">T</span><span :class="{active: level >= 4}">S</span></div>
                    <label class="form-label">Fecha de toma</label><input name="sampled_at" type="date" value="{{ old('sampled_at', now()->toDateString()) }}" class="form-control" required>
                    <label class="form-label mt-3">Solicitado por</label><input name="requested_by" value="{{ old('requested_by') }}" class="form-control" placeholder="Médico o área (opcional)">
                </div></div>
            </div>
            <div class="col-xl-8">
                <div class="card lab-card mb-4"><div class="card-body p-4">
                    <div class="d-flex justify-content-between"><h5><i class="bi bi-people me-2"></i>Pacientes</h5><span class="selection-count" x-text="`${selectedPatients.length} seleccionados`"></span></div>
                    <input x-model="patientQuery" class="form-control my-3" placeholder="Buscar por DNI, historia clínica o apellidos...">
                    <div class="patient-grid">
                    @foreach($patients as $patient)
                        <label class="patient-option" x-show="matches(@js(strtolower($patient->full_name.' '.$patient->dni.' '.$patient->medical_history_number)))">
                            <input type="checkbox" name="patient_ids[]" value="{{ $patient->id }}" x-model="selectedPatients">
                            <span class="avatar">{{ strtoupper(substr($patient->first_name ?? 'P', 0, 1).substr($patient->surname ?? '', 0, 1)) }}</span>
                            <span><strong>{{ $patient->full_name }}</strong><small>DNI {{ $patient->dni ?: '—' }} · H.C. {{ $patient->medical_history_number ?: '—' }}</small></span>
                        </label>
                    @endforeach
                    </div>
                    @error('patient_ids')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                </div></div>
                <div class="card lab-card"><div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center"><h5><i class="bi bi-clipboard2-pulse me-2"></i>Exámenes incluidos</h5><span class="selection-count" x-text="`${selectedTests.length} pruebas`"></span></div>
                    <p class="text-muted small">La periodicidad incluye automáticamente sus controles acumulados. Puedes ajustar la selección.</p>
                    <div class="row g-2">
                    @foreach($tests->groupBy(fn($test) => $test->area->name) as $area => $areaTests)
                        <div class="col-md-6"><div class="test-area"><h6>{{ $area }}</h6>
                        @foreach($areaTests as $test)
                            <label><input type="checkbox" name="test_ids[]" value="{{ $test->id }}" x-model.number="selectedTests"><span>{{ $test->name }} <em>{{ $test->frequency }}</em></span></label>
                        @endforeach
                        </div></div>
                    @endforeach
                    </div>
                </div></div>
                <div class="text-end mt-4"><button class="btn btn-lab btn-lg"><i class="bi bi-check2-circle me-2"></i>Generar órdenes</button></div>
            </div>
        </div>
    </form>
</div>
<style>
.lab-page{--lab:#087f5b;--ink:#173d35}.lab-hero{background:linear-gradient(120deg,#063f36,#07966b);border-radius:22px;color:#fff;padding:28px 34px;display:flex;justify-content:space-between;box-shadow:0 14px 35px #087f5b35}.lab-hero h2{font-weight:800;margin:3px 0}.lab-hero p{margin:0;opacity:.8}.lab-hero>i{font-size:58px;opacity:.3}.eyebrow{font-size:11px;letter-spacing:2px;font-weight:700}.lab-card{border:0;border-radius:18px;box-shadow:0 8px 28px #183f3514}.lab-card h5{font-weight:750;color:var(--ink)}.form-label{font-size:12px;text-transform:uppercase;color:#337064;font-weight:700}.frequency-flow{display:flex;justify-content:space-between;position:relative;margin:25px 5px 30px}.frequency-flow:before{content:'';height:3px;background:#dce9e5;position:absolute;left:18px;right:18px;top:16px}.frequency-flow span{z-index:1;width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#dce9e5;color:#73958d;font-weight:800}.frequency-flow span:first-child,.frequency-flow span.active{background:var(--lab);color:white}.patient-grid{max-height:300px;overflow:auto;display:grid;grid-template-columns:1fr 1fr;gap:10px}.patient-option{display:flex;gap:11px;align-items:center;padding:11px;border:1px solid #e1ebe8;border-radius:12px;cursor:pointer}.patient-option:has(input:checked){background:#ecfaf5;border-color:#16a476}.patient-option input{accent-color:var(--lab)}.avatar{background:#d8f3e9;color:var(--lab);font-weight:800;width:35px;height:35px;display:grid;place-items:center;border-radius:10px}.patient-option strong,.patient-option small{display:block}.patient-option strong{font-size:13px}.patient-option small{font-size:11px;color:#78918b}.selection-count{background:#e6f7f1;color:var(--lab);padding:5px 10px;border-radius:20px;font-size:12px;font-weight:700}.test-area{background:#f8fbfa;border:1px solid #e5efec;border-radius:12px;padding:14px;height:100%}.test-area h6{color:var(--lab);font-weight:800}.test-area label{display:flex;gap:8px;font-size:12px;margin:7px 0}.test-area input{accent-color:var(--lab)}.test-area em{font-style:normal;background:#dff4ec;color:var(--lab);font-size:10px;font-weight:bold;padding:2px 5px;border-radius:4px}.btn-lab{background:var(--lab);color:#fff;border-radius:12px;font-weight:700}.btn-lab:hover{background:#066b4d;color:#fff}@media(max-width:768px){.patient-grid{grid-template-columns:1fr}}
</style>
<script>
function fissalOrder(tests, oldTests){return{tests,period:@js(old('period','M')),selectedTests:oldTests.map(Number),selectedPatients:@js(collect(old('patient_ids',[]))->map(fn($id)=>(string)$id)),patientQuery:'',init(){if(!this.selectedTests.length)this.applyPeriod()},get level(){return {M:1,B:2,T:3,S:4}[this.period]},applyPeriod(){const levels={M:1,B:2,T:3,S:4};this.selectedTests=this.tests.filter(t=>levels[t.frequency]<=this.level).map(t=>t.id)},matches(value){return value.includes(this.patientQuery.toLowerCase().trim())}}}
</script>
@endsection
