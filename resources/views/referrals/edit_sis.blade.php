@extends('layouts.app')

@section('content')
<style>
    .is-invalid { border: 1px solid #dc3545 !important; }
    .is-valid { border: 1px solid #198754 !important; }
    .invalid-feedback { display: block; font-size: 0.7rem; font-weight: bold; }
    .select2-container--bootstrap-5 .select2-selection { border: 1px solid #ced4da !important; height: 38px !important; display: flex !important; align-items: center !important; }
    .card-header { background: #198754; color: white; font-weight: bold; }
    .section-label { background: #f8f9fa; border-left: 4px solid #198754; padding: 5px 10px; font-weight: bold; margin-bottom: 15px; text-transform: uppercase; font-size: 0.85rem; }
    .snapshot-card { border: 1px solid #dee2e6; border-left: 5px solid #198754; background: #fff; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    .data-title { font-size: 0.7rem; color: #6c757d; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
    .data-value { font-weight: bold; color: #333; }
</style>

<div class="container-fluid px-4 py-3">
    <div class="mb-3">
        <a href="{{ route('referrals.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> ← Volver al Listado de Referencias
        </a>
    </div>

    <form action="{{ route('referrals.update', $referral->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0 fw-bold">EDITAR REFERENCIA - {{ $referral->referral_code }}</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <span class="small fw-bold">TIPO SEGURO:</span>
                            <span id="badge_regime" class="badge bg-white text-success fs-6 p-2 px-3">SIS</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="section-label">1. Identificación del Paciente</div>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="data-title">Paciente</label>
                        <select id="patient_search" name="patient_id" class="form-control @error('patient_id') is-invalid @enderror" required>
                            <option value="{{ $referral->patient_id }}" selected>
                                {{ $referral->patient->surname }} {{ $referral->patient->last_name }}, {{ $referral->patient->first_name }}
                            </option>
                        </select>
                        @error('patient_id') <span class="invalid-feedback">✘ Seleccione un paciente</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="data-title">Establecimiento de Origen</label>
                        <input type="text" name="origin_facility" class="form-control bg-light" value="{{ $referral->origin_facility }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="data-title">Establecimiento Destino</label>
                        <input type="text" name="destination_facility" class="form-control @error('destination_facility') is-invalid @enderror @if(old('destination_facility', $referral->destination_facility)) is-valid @endif" value="{{ old('destination_facility', $referral->destination_facility) }}" required>
                    </div>
                </div>

                <div id="snapshot_panel" class="snapshot-card">
                    <div class="row g-3">
                        <div class="col-md-2"><label class="data-title">H.C. / Código SIS</label><div class="data-value"><span id="v_hc">{{ $referral->patient->medical_history_number }}</span> / <span id="v_aff">{{ $referral->patient->affiliation_code }}</span></div></div>
                        <div class="col-md-2 text-center"><label class="data-title">¿Asegurado?</label><div id="v_insured" class="data-value">{{ $referral->patient->is_insured ? 'SÍ' : 'NO' }}</div></div>
                        <div class="col-md-2 text-center"><label class="data-title">Regimen</label><div id="v_regime" class="data-value">{{ $referral->patient->insurance_regime ?? 'SUBSIDIADO' }}</div></div>
                        <div class="col-md-3"><label class="data-title">Nombres y Apellidos</label><div id="v_name" class="data-value text-uppercase">{{ $referral->patient->surname }} {{ $referral->patient->last_name }}, {{ $referral->patient->first_name }}</div></div>
                        <div class="col-md-2"><label class="data-title">Edad / Sexo</label><div class="data-value"><span id="v_age">{{ $referral->patient->age }}</span> años / <span id="v_sex">{{ $referral->patient->gender == 'F' ? 'FEMENINO' : 'MASCULINO' }}</span></div></div>
                        <div class="col-12 mt-2 pt-2 border-top"><label class="data-title">Dirección Completa</label><div id="v_address" class="data-value small text-muted">{{ strtoupper($referral->patient->address) }} - {{ strtoupper($referral->patient->district) }}</div></div>
                    </div>
                </div>

                <div class="section-label">2. Examen Clínico</div>
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="data-title">Anamnesis</label>
                        <textarea name="anamnesis" class="form-control @error('anamnesis') is-invalid @enderror @if(old('anamnesis', $referral->anamnesis)) is-valid @endif" rows="3">{{ old('anamnesis', $referral->anamnesis) }}</textarea>
                    </div>
                    <div class="col-md-3"><label class="data-title">T°</label><input type="text" name="temperature" class="form-control @if(old('temperature', $referral->temperature)) is-valid @endif" value="{{ old('temperature', $referral->temperature) }}"></div>
                    <div class="col-md-3"><label class="data-title">PA</label><input type="text" name="blood_pressure" class="form-control @if(old('blood_pressure', $referral->blood_pressure)) is-valid @endif" value="{{ old('blood_pressure', $referral->blood_pressure) }}"></div>
                    <div class="col-md-3"><label class="data-title">FR</label><input type="text" name="respiratory_rate" class="form-control @if(old('respiratory_rate', $referral->respiratory_rate)) is-valid @endif" value="{{ old('respiratory_rate', $referral->respiratory_rate) }}"></div>
                    <div class="col-md-3"><label class="data-title">FC</label><input type="text" name="heart_rate" class="form-control @if(old('heart_rate', $referral->heart_rate)) is-valid @endif" value="{{ old('heart_rate', $referral->heart_rate) }}"></div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6"><label class="data-title">TCSC (Piel)</label><input type="text" name="skin_subcutaneous" class="form-control" value="{{ old('skin_subcutaneous', $referral->skin_subcutaneous) }}"></div>
                    <div class="col-md-6"><label class="data-title">Pulmones</label><input type="text" name="lungs" class="form-control" value="{{ old('lungs', $referral->lungs) }}"></div>
                    <div class="col-md-6"><label class="data-title">Cardiovascular</label><input type="text" name="cardiovascular" class="form-control" value="{{ old('cardiovascular', $referral->cardiovascular) }}"></div>
                    <div class="col-md-6"><label class="data-title">Neurológico</label><input type="text" name="neurological" class="form-control" value="{{ old('neurological', $referral->neurological) }}"></div>
                </div>

                <div class="section-label">Exámenes Auxiliares</div>
                <div class="row mb-4">
                    <div class="col-12">
                        <label class="data-title">Resultados</label>
                        <textarea name="auxiliary_exams" class="form-control" rows="2">{{ old('auxiliary_exams', $referral->auxiliary_exams) }}</textarea>
                    </div>
                </div>

                <div class="section-label">3. Diagnósticos y Tratamiento</div>
                <div x-data="{ rows: {{ json_encode(old('diagnoses', $referral->diagnosisTreatments->map(function($d) {
                    return [
                        'icd_10_code' => $d->icd_10_code,
                        'diagnosis' => $d->diagnosis,
                        'treatment' => $d->treatment,
                        'D' => $d->D === 'X',
                        'P' => $d->P === 'X',
                        'R' => $d->R === 'X'
                    ];
                }))) }} }">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light text-center small">
                            <tr><th width="12%">CIE-10</th><th width="33%">Diagnóstico</th><th width="33%">Tratamiento</th><th width="4%">D</th><th width="4%">P</th><th width="4%">R</th><th width="5%"></th></tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in rows" :key="index">
                                <tr>
                                    <td><input type="text" :name="`diagnoses[${index}][icd_10_code]`" x-model="row.icd_10_code" class="form-control form-control-sm"></td>
                                    <td><input type="text" :name="`diagnoses[${index}][diagnosis]`" x-model="row.diagnosis" class="form-control form-control-sm"></td>
                                    <td><textarea :name="`diagnoses[${index}][treatment]`" x-model="row.treatment" class="form-control form-control-sm" rows="1"></textarea></td>
                                    <td class="text-center"><input type="checkbox" :name="`diagnoses[${index}][D]`" value="X" :checked="row.D" @change="row.D = $event.target.checked"></td>
                                    <td class="text-center"><input type="checkbox" :name="`diagnoses[${index}][P]`" value="X" :checked="row.P" @change="row.P = $event.target.checked"></td>
                                    <td class="text-center"><input type="checkbox" :name="`diagnoses[${index}][R]`" value="X" :checked="row.R" @change="row.R = $event.target.checked"></td>
                                    <td class="text-center"><button type="button" @click="rows.splice(index, 1)" class="btn btn-sm text-danger" x-show="rows.length > 1">×</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <button type="button" @click="rows.push({ icd_10_code: '', diagnosis: '', treatment: '', D: false, P: false, R: false })" class="btn btn-sm btn-outline-success">+ Agregar Diagnóstico</button>
                </div>

                <div class="section-label mt-5">4. Datos de Referencia</div>
                <div class="row g-3">
                    <div class="col-md-2"><label class="data-title">Tipo de Referencia</label>
                    <select name="referral_type" class="form-select">
                        <option value="EMERGENCIA" {{ old('referral_type', $referral->referral_type)=='EMERGENCIA'?'selected':'' }}>EMERGENCIA</option>
                        <option value="CONSULTA EXTERNA" {{ old('referral_type', $referral->referral_type)=='CONSULTA EXTERNA'?'selected':'' }}>CONSULTA EXTERNA</option>
                        <option value="APOYO AL DX" {{ old('referral_type', $referral->referral_type)=='APOYO AL DX'?'selected':'' }}>APOYO AL DX</option>
                    </select></div>
                    <div class="col-md-2"><label class="data-title">Fecha Cita</label><input type="date" name="appointment_date" class="form-control" value="{{ old('appointment_date', $referral->appointment_date) }}"></div>
                    <div class="col-md-2"><label class="data-title">Hora Cita</label><input type="time" name="appointment_time" class="form-control" value="{{ old('appointment_time', $referral->appointment_time) }}"></div>
                    <div class="col-md-3"><label class="data-title">Atenderá</label><input type="text" name="attending_physician_name" class="form-control" value="{{ old('attending_physician_name', $referral->attending_physician_name) }}"></div>
                    <div class="col-md-3"><label class="data-title">Coordinado con</label><input type="text" name="coordination_name" class="form-control" value="{{ old('coordination_name', $referral->coordination_name) }}"></div>
                    <div class="col-md-6"><label class="data-title">Especialidad Destino</label><input type="text" name="destination_specialty" class="form-control" value="{{ old('destination_specialty', $referral->destination_specialty) }}"></div>
                    <div class="col-md-3"><label class="data-title">Condición Inicio</label>
                    <select name="patient_condition" class="form-select">
                        <option value="ESTABLE" {{ old('patient_condition', $referral->patient_condition)=='ESTABLE'?'selected':'' }}>ESTABLE</option>
                        <option value="MAL ESTADO" {{ old('patient_condition', $referral->patient_condition)=='MAL ESTADO'?'selected':'' }}>MAL ESTADO</option>
                    </select></div>
                    <div class="col-md-3"><label class="data-title">Condición Llegada</label>
                    <select name="arrival_condition" class="form-select">
                        <option value="ESTABLE" {{ old('arrival_condition', $referral->arrival_condition)=='ESTABLE'?'selected':'' }}>ESTABLE</option>
                        <option value="MAL ESTADO" {{ old('arrival_condition', $referral->arrival_condition)=='MAL ESTADO'?'selected':'' }}>MAL ESTADO</option>
                        <option value="FALLECIDO" {{ old('arrival_condition', $referral->arrival_condition)=='FALLECIDO'?'selected':'' }}>FALLECIDO</option>
                    </select></div>

                    @foreach(['referral_responsible_id' => 'Responsable RF', 'facility_responsible_id' => 'Resp. Establ.', 'escort_staff_id' => 'Acompañante', 'receiving_staff_id' => 'Recibe'] as $name => $label)
                    <div class="col-md-3"><label class="data-title">{{ $label }}</label><select name="{{ $name }}" class="form-select shadow-sm @error($name) is-invalid @enderror">
                        <option value="">Seleccione...</option>
                        @foreach($staff as $user) <option value="{{ $user->id }}" {{ old($name, $referral->$name) == $user->id ? 'selected':'' }}>{{ $user->name }}</option> @endforeach
                    </select></div>
                    @endforeach
                </div>
            </div>

            <div class="card-footer bg-light p-4 text-end">
                <button type="submit" class="btn btn-primary btn-lg px-5">ACTUALIZAR REFERENCIA SIS</button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#patient_search').select2({
                theme: 'bootstrap-5',
                ajax: { url: "{{ route('patients.search') }}", dataType: 'json', delay: 300, data: params => ({ q: params.term }), processResults: data => data },
                minimumInputLength: 2
            }).on('select2:select', function (e) {
                let p = e.params.data;
                $('#v_hc').text(p.medical_history_number || 'S/N');
                $('#v_aff').text(p.affiliation_code || 'S/C');
                $('#v_insured').text(p.is_insured ? 'SÍ' : 'NO');
                $('#v_regime').text(p.insurance_regime || 'SUBSIDIADO');
                $('#v_name').text(`${p.surname} ${p.last_name}, ${p.first_name}`.toUpperCase());
                $('#v_age').text(p.age || '-');
                $('#v_sex').text(p.gender == 'F' ? 'FEMENINO' : 'MASCULINO');
                $('#v_address').text(`${p.address} - ${p.district}`.toUpperCase());
            });
        });
    </script>
@endpush
@endsection