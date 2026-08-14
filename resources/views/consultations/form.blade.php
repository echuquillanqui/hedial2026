@extends('layouts.app')

@section('content')
@php
    $editing = $consultation->exists;
    $savedDiagnoses = old('diagnoses', $consultation->diagnoses ?: []);
    $savedExams = old('auxiliary_exams', $consultation->auxiliary_exams ?: []);
@endphp
<style>
    .clinical-shell { --navy:#172554; --blue:#2563eb; --cyan:#06b6d4; --soft:#eff6ff; }
    .clinical-hero { background:linear-gradient(125deg,var(--navy),var(--blue) 62%,var(--cyan)); color:#fff; border-radius:20px; padding:1.4rem 1.6rem; box-shadow:0 14px 34px rgba(37,99,235,.22); }
    .clinical-tabs { background:#fff; padding:.45rem; border-radius:16px; box-shadow:0 7px 24px rgba(15,23,42,.08); }
    .clinical-tab { border:0; background:transparent; color:#64748b; border-radius:12px; padding:.85rem 1rem; font-weight:700; flex:1; }
    .clinical-tab.active { color:#fff; background:linear-gradient(100deg,var(--blue),#4f46e5); box-shadow:0 6px 16px rgba(37,99,235,.25); }
    .clinical-panel { display:none; } .clinical-panel.active { display:block; }
    .section-card { border:1px solid #e2e8f0!important; overflow:visible; }
    .section-title { color:var(--navy); font-weight:800; }
    .cie-results { position:absolute; z-index:20; left:0; right:0; top:100%; background:#fff; border:1px solid #bfdbfe; border-radius:10px; max-height:240px; overflow:auto; box-shadow:0 12px 25px rgba(15,23,42,.15); }
    .cie-option { padding:.65rem .8rem; cursor:pointer; border-bottom:1px solid #eff6ff; } .cie-option:hover { background:#eff6ff; }
    .exam-group { height:100%; border:1px solid #dbeafe; border-radius:14px; padding:1rem; background:linear-gradient(180deg,#fff,#f8fbff); }
    .exam-group h6 { color:var(--blue); font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
    .exam-group-title { display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.4rem; }
    .exam-group-title h6 { margin:0; }
    .exam-group-toggle { color:var(--blue); font-size:.72rem; font-weight:700; white-space:nowrap; }
    .exam-check { display:flex; gap:.6rem; padding:.42rem; border-radius:8px; } .exam-check:hover { background:#e0f2fe; }
</style>
<div class="container-fluid clinical-shell">
    <div class="clinical-hero d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <div><div class="text-uppercase opacity-75 small fw-bold">Atención integral</div><h2 class="mb-1 fw-bold"><i class="bi bi-heart-pulse me-2"></i>Consulta nefrológica</h2><div>Historia clínica, receta y seguimiento en un solo formulario</div></div>
        <a href="{{ route('consultations.index') }}" class="btn btn-light fw-bold"><i class="bi bi-arrow-left me-1"></i> Volver</a>
    </div>
    @if($errors->any())<div class="alert alert-danger"><strong>Revise los datos:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $editing ? route('consultations.update', $consultation) : route('consultations.store') }}">@csrf @if($editing)@method('PUT')@endif
        <div class="clinical-tabs d-flex gap-2 mb-3" role="tablist">
            <button type="button" class="clinical-tab active" data-tab="anamnesis"><i class="bi bi-clipboard2-pulse me-1"></i> Anamnesis</button>
            <button type="button" class="clinical-tab" data-tab="recipe"><i class="bi bi-capsule me-1"></i> Receta</button>
            <button type="button" class="clinical-tab" data-tab="exams"><i class="bi bi-clipboard2-check me-1"></i> Exámenes auxiliares</button>
        </div>

        <section id="anamnesis" class="clinical-panel active">
            <div class="card section-card shadow-sm mb-3"><div class="card-body"><h5 class="section-title mb-3">Datos de atención</h5><div class="row g-3">
                <div class="col-lg-5"><label class="form-label">Paciente *</label><select name="patient_id" class="form-select" required><option value="">Seleccione...</option>@foreach($patients as $patient)<option value="{{ $patient->id }}" @selected(old('patient_id', $consultation->patient_id) == $patient->id)>{{ $patient->full_name }} — {{ $patient->dni }}</option>@endforeach</select></div>
                <div class="col-lg-4"><label class="form-label">Médico</label><select name="doctor_id" class="form-select"><option value="">Usuario actual</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}" @selected(old('doctor_id', $consultation->doctor_id) == $doctor->id)>{{ $doctor->name }}</option>@endforeach</select></div>
                <div class="col-lg-3"><label class="form-label">Fecha *</label><input type="date" name="consultation_date" class="form-control" required value="{{ old('consultation_date', optional($consultation->consultation_date)->format('Y-m-d')) }}"></div>
                @foreach(['blood_pressure'=>'Presión arterial','weight'=>'Peso (kg)','temperature'=>'Temperatura (°C)','heart_rate'=>'Frecuencia cardíaca','oxygen_saturation'=>'Sat. O₂ (%)'] as $field=>$label)<div class="col-md"><label class="form-label">{{ $label }}</label><input name="{{ $field }}" type="{{ $field === 'blood_pressure' ? 'text' : 'number' }}" step="{{ in_array($field, ['weight','temperature']) ? '0.1' : '1' }}" class="form-control" value="{{ old($field, $consultation->$field) }}"></div>@endforeach
            </div></div></div>
            <div class="card section-card shadow-sm mb-3"><div class="card-body"><h5 class="section-title mb-3">Evaluación clínica</h5><div class="row g-3">
                @foreach(['reason'=>'Motivo de consulta','current_illness'=>'Enfermedad actual','history'=>'Antecedentes','physical_exam'=>'Examen físico','treatment_plan'=>'Plan / indicaciones','observations'=>'Observaciones'] as $field=>$label)<div class="col-md-6"><label class="form-label">{{ $label }}</label><textarea name="{{ $field }}" rows="3" class="form-control">{{ old($field, $consultation->$field) }}</textarea></div>@endforeach
            </div></div></div>
            <div class="card section-card shadow-sm mb-3"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="section-title mb-0">Diagnósticos CIE-10</h5><button type="button" id="addDiagnosis" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> Agregar diagnóstico</button></div><div id="diagnoses"></div><small class="text-muted">Escriba el código o descripción; los resultados se consultan dinámicamente desde la tabla CIE-10.</small></div></div>
        </section>

        <section id="recipe" class="clinical-panel">
            <div class="card section-card shadow-sm mb-3"><div class="card-header bg-white py-3 d-flex justify-content-between"><strong class="section-title"><i class="bi bi-prescription2 me-1"></i> Receta mensual</strong><button type="button" id="addMedication" class="btn btn-sm btn-primary">Agregar medicamento</button></div><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Código</th><th style="min-width:260px">Medicamento</th><th style="min-width:240px">Prescripción</th><th>C. prescrita</th><th>C. entregada</th><th></th></tr></thead><tbody id="medications">
                @foreach(old('medications', $medications->toArray()) as $i=>$medication)<tr><td><input class="form-control" name="medications[{{ $i }}][fua_code]" value="{{ $medication['fua_code'] ?? '' }}"></td><td><input required class="form-control" name="medications[{{ $i }}][description]" value="{{ $medication['description'] ?? '' }}"></td><td><input class="form-control" name="medications[{{ $i }}][c]" value="{{ $medication['c'] ?? '' }}"></td><td><input type="number" min="0" step="0.01" class="form-control" name="medications[{{ $i }}][prescribed_quantity]" value="{{ $medication['prescribed_quantity'] ?? 0 }}"></td><td><input type="number" min="0" step="0.01" class="form-control" name="medications[{{ $i }}][delivered_quantity]" value="{{ $medication['delivered_quantity'] ?? 0 }}"></td><td><button type="button" class="btn btn-outline-danger remove-row">×</button></td></tr>@endforeach
            </tbody></table></div></div>
        </section>

        <section id="exams" class="clinical-panel">
            <div class="card section-card shadow-sm mb-3"><div class="card-body"><h5 class="section-title text-center mb-4">Exámenes auxiliares — se solicita</h5><div class="row g-3">
                @foreach($examGroups as $frequency => $exams)<div class="col-xl-3 col-md-6"><div class="exam-group" data-exam-group><div class="exam-group-title"><h6><i class="bi bi-calendar-check me-1"></i>{{ $frequency }}</h6><label class="exam-group-toggle"><input class="form-check-input me-1" type="checkbox" data-select-exam-group>Todo el bloque</label></div>@foreach($exams as $exam)<label class="exam-check"><input class="form-check-input" type="checkbox" name="auxiliary_exams[]" value="{{ $frequency }}|{{ $exam }}" data-exam-item @checked(in_array($frequency.'|'.$exam, $savedExams))><span>{{ $exam }}</span></label>@endforeach</div></div>@endforeach
            </div><div class="row g-3 mt-2"><div class="col-md-6"><label class="form-label">Fecha próximo laboratorio</label><input type="date" class="form-control" name="next_laboratory_date" value="{{ old('next_laboratory_date', optional($consultation->next_laboratory_date)->format('Y-m-d')) }}"></div><div class="col-md-6"><label class="form-label">Fecha próxima cita</label><input type="date" class="form-control" name="next_appointment_date" value="{{ old('next_appointment_date', optional($consultation->next_appointment_date)->format('Y-m-d')) }}"></div></div></div></div>
        </section>
        <div class="d-flex gap-2 justify-content-end sticky-bottom bg-light py-3"><button class="btn btn-success btn-lg px-4"><i class="bi bi-check2-circle me-1"></i> Guardar consulta</button>@if($editing)<a target="_blank" href="{{ route('consultations.prescription.pdf', $consultation) }}" class="btn btn-danger btn-lg">Generar receta PDF</a>@endif</div>
    </form>
</div>
@endsection
@push('scripts')<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-exam-group]').forEach(group => {
        const toggle = group.querySelector('[data-select-exam-group]');
        const items = [...group.querySelectorAll('[data-exam-item]')];
        const sync = () => { const count = items.filter(item => item.checked).length; toggle.checked = count === items.length; toggle.indeterminate = count > 0 && count < items.length; };
        toggle.addEventListener('change', () => items.forEach(item => { item.checked = toggle.checked; }));
        items.forEach(item => item.addEventListener('change', sync));
        sync();
    });
    document.querySelectorAll('.clinical-tab').forEach(button => button.addEventListener('click', () => {
        document.querySelectorAll('.clinical-tab,.clinical-panel').forEach(item => item.classList.remove('active'));
        button.classList.add('active'); document.getElementById(button.dataset.tab).classList.add('active');
    }));
    const meds=document.getElementById('medications');
    document.getElementById('addMedication').onclick=()=>{const i=meds.querySelectorAll('tr').length;meds.insertAdjacentHTML('beforeend',medicationRow(i));};
    meds.addEventListener('click',e=>{if(e.target.classList.contains('remove-row')&&meds.children.length>1)e.target.closest('tr').remove();});
    const diagnosisBox=document.getElementById('diagnoses'); let diagnosisIndex=0;
    const initial=@json(array_values($savedDiagnoses));
    (initial.length ? initial : [{codigo:'N18.0',descripcion:'ENFERMEDAD RENAL CRÓNICA ESTADIO 5'}]).forEach(addDiagnosis);
    document.getElementById('addDiagnosis').onclick=()=>addDiagnosis({});
    function addDiagnosis(item={}) { const i=diagnosisIndex++; diagnosisBox.insertAdjacentHTML('beforeend', `<div class="row g-2 align-items-center mb-2 diagnosis-row"><div class="col-md-3"><input type="hidden" name="diagnoses[${i}][cie10_id]" value="${escapeHtml(item.cie10_id||'')}"><input class="form-control cie-code" name="diagnoses[${i}][codigo]" placeholder="Código CIE-10" autocomplete="off" value="${escapeHtml(item.codigo||'')}"></div><div class="col-md-8 position-relative"><input class="form-control cie-description" name="diagnoses[${i}][descripcion]" placeholder="Buscar diagnóstico..." autocomplete="off" value="${escapeHtml(item.descripcion||'')}"><div class="cie-results d-none"></div></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-diagnosis">×</button></div></div>`); }
    let timer; diagnosisBox.addEventListener('input', e=>{if(!e.target.matches('.cie-code,.cie-description'))return; const row=e.target.closest('.diagnosis-row'), results=row.querySelector('.cie-results'), term=e.target.value.trim(); clearTimeout(timer); if(term.length<2){results.classList.add('d-none');return;} timer=setTimeout(async()=>{const response=await fetch(`{{ route('referrals.cie10.search') }}?q=${encodeURIComponent(term)}`); const items=await response.json(); results.innerHTML=items.map(x=>`<div class="cie-option" data-id="${x.id}" data-code="${escapeHtml(x.codigo)}" data-description="${escapeHtml(x.descripcion)}"><strong>${escapeHtml(x.codigo)}</strong> — ${escapeHtml(x.descripcion)}</div>`).join('')||'<div class="p-3 text-muted">Sin resultados</div>';results.classList.remove('d-none');},250);});
    diagnosisBox.addEventListener('click',e=>{const option=e.target.closest('.cie-option');if(option){const row=option.closest('.diagnosis-row');row.querySelector('[type=hidden]').value=option.dataset.id;row.querySelector('.cie-code').value=option.dataset.code;row.querySelector('.cie-description').value=option.dataset.description;row.querySelector('.cie-results').classList.add('d-none');}if(e.target.classList.contains('remove-diagnosis')&&diagnosisBox.children.length>1)e.target.closest('.diagnosis-row').remove();});
    function medicationRow(i){return `<tr><td><input class="form-control" name="medications[${i}][fua_code]"></td><td><input required class="form-control" name="medications[${i}][description]"></td><td><input class="form-control" name="medications[${i}][c]"></td><td><input type="number" min="0" step=".01" value="0" class="form-control" name="medications[${i}][prescribed_quantity]"></td><td><input type="number" min="0" step=".01" value="0" class="form-control" name="medications[${i}][delivered_quantity]"></td><td><button type="button" class="btn btn-outline-danger remove-row">×</button></td></tr>`;}
    function escapeHtml(value){return String(value).replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));}
});
</script>@endpush
