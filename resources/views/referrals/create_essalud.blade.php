@extends('layouts.app')

@section('content')
<style>
    /* Estilos de validación y Select2 */
    .is-invalid { border: 1px solid #dc3545 !important; }
    .is-valid { border: 1px solid #198754 !important; }
    .invalid-feedback { display: block; font-size: 0.7rem; font-weight: bold; }
    .select2-container--bootstrap-5 .select2-selection { border: 1px solid #ced4da !important; height: 38px !important; display: flex !important; align-items: center !important; }
    
    /* Colores Institucionales EsSalud */
    .card-header { background: #0056b3; color: white; font-weight: bold; border-bottom: 3px solid #00aae4; }
    .section-label { background: #e7f1ff; border-left: 4px solid #0056b3; padding: 5px 10px; font-weight: bold; margin-bottom: 15px; text-transform: uppercase; font-size: 0.85rem; color: #0056b3; }
    .snapshot-card { border: 1px solid #dee2e6; border-left: 5px solid #00aae4; background: #fff; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    
    .data-title { font-size: 0.7rem; color: #6c757d; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }
    .data-value { font-weight: bold; color: #333; }
    .btn-essalud { background: #0056b3; color: white; border: none; transition: 0.3s; }
    .btn-essalud:hover { background: #004494; color: white; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
</style>

<div class="container-fluid px-4 py-3">
    <div class="mb-3">
        <a href="{{ route('referrals.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> ← Volver al Listado de Referencias
        </a>
    </div>

    <form action="{{ route('referrals.store') }}" method="POST">
        @csrf
        <div class="card shadow-sm border-0">
            <div class="card-header py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-hospital-symbol"></i> NUEVA REFERENCIA - FORMATO ESSALUD</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <span class="small fw-bold text-white">TIPO DE SEGURO:</span>
                            <span id="badge_regime" class="badge bg-white text-primary fs-6 p-2 px-3 shadow-sm">EsSalud</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="section-label">1. Identificación del Asegurado e IPRESS</div>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="data-title">Buscar Asegurado (DNI / Autogenerado)</label>
                        <select id="patient_search" name="patient_id" class="form-control @error('patient_id') is-invalid @enderror" required></select>
                        @error('patient_id') <span class="invalid-feedback">✘ Seleccione un paciente</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="data-title">IPRESS de Origen</label>
                        <input type="text" name="origin_facility" class="form-control bg-light" value="CENTRO NEFROLÓGICO INTEGRAL RENAL CARE S.A.C." readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="data-title">Centro Asistencial Destino</label>
                        <input type="text" name="destination_facility" class="form-control @error('destination_facility') is-invalid @enderror" value="{{ old('destination_facility', 'HOSPITAL NACIONAL ALBERTO SABOGAL SOLOGUREN') }}" required>
                    </div>
                </div>

                <div id="snapshot_panel" class="snapshot-card d-none">
                    <div class="row g-3">
                        <div class="col-md-2"><label class="data-title">H.C. / Autogenerado</label><div class="data-value"><span id="v_hc"></span> / <span id="v_aff"></span></div></div>
                        <div class="col-md-2 text-center"><label class="data-title">Acreditación</label><div id="v_insured" class="data-value text-success"></div></div>
                        <div class="col-md-2 text-center"><label class="data-title">Tipo Seguro</label><div id="v_regime" class="data-value"></div></div>
                        <div class="col-md-3"><label class="data-title">Apellidos y Nombres</label><div id="v_name" class="data-value text-uppercase text-primary"></div></div>
                        <div class="col-md-2"><label class="data-title">Edad / Sexo</label><div class="data-value"><span id="v_age"></span> años / <span id="v_sex"></span></div></div>
                        <div class="col-12 mt-2 pt-2 border-top"><label class="data-title">Dirección Declarada</label><div id="v_address" class="data-value small text-muted"></div></div>
                    </div>
                </div>

                <div class="section-label">2. Resumen de Historia Clínica y Signos Vitales</div>
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="data-title">Anamnesis</label>
                        <textarea name="anamnesis" class="form-control @error('anamnesis') is-invalid @enderror" rows="2">{{ old('anamnesis') }}</textarea>
                    </div>
                    <div class="col-md-2"><label class="data-title">P.A. (mmHg)</label><input type="text" name="blood_pressure" class="form-control" value="{{ old('blood_pressure') }}" placeholder="120/80"></div>
                    <div class="col-md-2"><label class="data-title">F.C. (x')</label><input type="text" name="heart_rate" class="form-control" value="{{ old('heart_rate') }}"></div>
                    <div class="col-md-2"><label class="data-title">F.R. (x')</label><input type="text" name="respiratory_rate" class="form-control" value="{{ old('respiratory_rate') }}"></div>
                    <div class="col-md-2"><label class="data-title">T° (°C)</label><input type="text" name="temperature" class="form-control" value="{{ old('temperature') }}"></div>
                    <div class="col-md-2"><label class="data-title">Sat.O2 (%)</label><input type="text" name="oxygen_saturation" class="form-control" value="{{ old('oxygen_saturation') }}"></div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6"><label class="data-title">Estado general:</label><input type="text" name="general_state" class="form-control" value="{{ old('general_state', 'Tranquila, niega molestias') }}"></div>
                    <div class="col-md-6"><label class="data-title">Aparato Respiratorio</label><input type="text" name="lungs" class="form-control" value="{{ old('lungs', 'Murmullo vesicular conservado, no ruidos agregados.') }}"></div>
                    <div class="col-md-6"><label class="data-title">Cardiovascular</label><input type="text" name="cardiovascular" class="form-control" value="{{ old('cardiovascular', 'Ruidos cardiacos rítmicos, de buena intensidad.') }}"></div>
                    <div class="col-md-6"><label class="data-title">Otros</label><input type="text" name="others" class="form-control" value="{{ old('others') }}"></div>
                    <div class="col-12">
                        <label class="data-title">Examenes auxiliares</label>
                        <textarea name="auxiliary_exams" class="form-control @error('auxiliary_exams') is-invalid @enderror" rows="2">{{ old('auxiliary_exams') }}</textarea>
                    </div>
                </div>                

                <div class="section-label">3. Diagnósticos (CIE-10) y Plan de Trabajo</div>
                <div x-data="{ rows: {{ json_encode(old('diagnoses', [['icd_10_code' => 'N18.9', 'diagnosis' => 'ENFERMEDAD RENAL CRONICA', 'treatment' => '', 'D' => true, 'P' => false, 'R' => false]])) }} }">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light text-center small">
                            <tr>
                                <th width="12%">CIE-10</th>
                                <th width="33%">Descripción Diagnóstica</th>
                                <th width="33%">Tratamiento / Recomendaciones</th>
                                <th width="4%" title="Definitivo">D</th>
                                <th width="4%" title="Presuntivo">P</th>
                                <th width="4%" title="Repetitivo">R</th>
                                <th width="5%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in rows" :key="index">
                                <tr>
                                    <td><input type="text" :name="`diagnoses[${index}][icd_10_code]`" x-model="row.icd_10_code" class="form-control form-control-sm text-center fw-bold"></td>
                                    <td><input type="text" :name="`diagnoses[${index}][diagnosis]`" x-model="row.diagnosis" class="form-control form-control-sm"></td>
                                    <td><textarea :name="`diagnoses[${index}][treatment]`" x-model="row.treatment" class="form-control form-control-sm" rows="1"></textarea></td>
                                    <td class="text-center"><input type="checkbox" :name="`diagnoses[${index}][D]`" value="X" :checked="row.D"></td>
                                    <td class="text-center"><input type="checkbox" :name="`diagnoses[${index}][P]`" value="X" :checked="row.P"></td>
                                    <td class="text-center"><input type="checkbox" :name="`diagnoses[${index}][R]`" value="X" :checked="row.R"></td>
                                    <td class="text-center"><button type="button" @click="rows.splice(index, 1)" class="btn btn-sm text-danger" x-show="rows.length > 1">×</button></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <button type="button" @click="rows.push({ icd_10_code: '', diagnosis: '', treatment: '', D: false, P: false, R: false })" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus"></i> Agregar Diagnóstico
                    </button>
                </div>

                <div class="section-label mt-5">4. Datos de la Referencia y Responsables</div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="data-title">Prioridad de Referencia</label>
                        <select name="referral_type" class="form-select">
                            <option value="EMERGENCIA" {{ old('referral_type')=='EMERGENCIA'?'selected':'' }}>I - EMERGENCIA</option>
                            <option value="CONSULTA EXTERNA" {{ old('referral_type')=='CONSULTA EXTERNA'?'selected':'' }}>II - CONSULTA EXTERNA</option>
                            <option value="APOYO AL DX" {{ old('referral_type')=='APOYO AL DX'?'selected':'' }}>III - APOYO AL DIAGNÓSTICO</option>
                        </select>
                    </div>
                    <div class="col-md-3"><label class="data-title">Especialidad Destino</label><input type="text" name="destination_specialty" class="form-control" value="{{ old('destination_specialty', 'MEDICINA / NEFROLOGÍA') }}"></div>
                    <div class="col-md-3">
                        <label class="data-title">Condición de Inicio</label>
                        <select name="patient_condition" class="form-select">
                            <option value="ESTABLE" {{ old('patient_condition')=='ESTABLE'?'selected':'' }}>ESTABLE</option>
                            <option value="MAL ESTADO" {{ old('patient_condition')=='MAL ESTADO'?'selected':'' }}>MAL ESTADO</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="data-title">Condición de Llegada</label>
                        <select name="arrival_condition" class="form-select">
                            <option value="ESTABLE" {{ old('arrival_condition')=='ESTABLE'?'selected':'' }}>ESTABLE</option>
                            <option value="MAL ESTADO" {{ old('arrival_condition')=='MAL ESTADO'?'selected':'' }}>MAL ESTADO</option>
                            <option value="FALLECIDO" {{ old('arrival_condition')=='FALLECIDO'?'selected':'' }}>FALLECIDO</option>
                        </select>
                    </div>

                    @foreach(['referral_responsible_id' => 'Médico Tratante', 'facility_responsible_id' => 'Jefe de IPRESS', 'escort_staff_id' => 'Personal Acompañante', 'receiving_staff_id' => 'Responsable Recepción'] as $name => $label)
                    <div class="col-md-3">
                        <label class="data-title">{{ $label }}</label>
                        <select name="{{ $name }}" class="form-select shadow-sm @error($name) is-invalid @enderror">
                            <option value="">Seleccione personal...</option>
                            @foreach($staff as $user) 
                                <option value="{{ $user->id }}" {{ old($name) == $user->id ? 'selected':'' }}>
                                    {{ $user->name }} ({{ $user->profession ?? 'MÉDICO' }})
                                </option> 
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="card-footer bg-light p-4 text-center">
                <button type="submit" class="btn btn-essalud btn-lg px-5 shadow">
                    <i class="fas fa-save"></i> REGISTRAR REFERENCIA ESSALUD
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('#patient_search').select2({
            theme: 'bootstrap-5',
            placeholder: 'DNI o Nombre del Asegurado...',
            ajax: { 
                url: "{{ route('patients.search') }}", 
                dataType: 'json', 
                delay: 300, 
                data: params => ({ q: params.term }), 
                processResults: data => data 
            },
            minimumInputLength: 2
        }).on('select2:select', function (e) {
            let p = e.params.data;
            $('#snapshot_panel').removeClass('d-none');
            $('#v_hc').text(p.medical_history_number || 'S/N');
            $('#v_aff').text(p.affiliation_code || p.dni);
            $('#v_insured').text(p.is_insured ? 'SÍ (ACTIVA)' : 'NO ACREDITADO');
            $('#v_regime').text(p.insurance_regime || 'TITULAR');
            $('#v_name').text(`${p.surname} ${p.last_name}, ${p.first_name}`.toUpperCase());
            $('#v_age').text(p.age || '-');
            $('#v_sex').text(p.gender == 'F' ? 'FEMENINO' : 'MASCULINO');
            $('#v_address').text(`${p.address} - ${p.district}`.toUpperCase());
        });
    });
</script>
@endpush
@endsection