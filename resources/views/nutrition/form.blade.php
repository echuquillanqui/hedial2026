@extends('layouts.app')
@php($savedDiagnoses = array_values(old('diagnoses', $assessment->diagnoses ?: [])))
@push('styles')
<style>
    .nutrition-cie-results { position:absolute; z-index:20; left:0; right:0; top:100%; max-height:240px; overflow:auto; background:#fff; border:1px solid #a7f3d0; border-radius:.5rem; box-shadow:0 12px 25px rgba(15,23,42,.15) }
    .nutrition-cie-option { padding:.65rem .8rem; cursor:pointer; border-bottom:1px solid #ecfdf5 }
    .nutrition-cie-option:hover { background:#ecfdf5 }
</style>
@endpush
@section('content')<div class="container py-4" style="max-width:1100px"><div class="d-flex justify-content-between align-items-center mb-4"><div><span class="badge bg-success bg-opacity-10 text-success mb-2">ANEXO N.° 6</span><h2 class="mb-1">Atención en Nutrición</h2><p class="text-muted mb-0">Complete la evaluación por secciones. La filiación y los resultados se obtienen automáticamente.</p></div><a href="{{ route('nutrition.index') }}" class="btn btn-outline-secondary">Volver</a></div>
@include('multisectorial.patient-summary',['patient'=>$order->patient,'order'=>$order])
<form method="POST" action="{{ $assessment->exists ? route('nutrition.update',$assessment) : route('nutrition.store',$order) }}">@csrf @if($assessment->exists)@method('PUT')@endif
@include('multisectorial.errors')
<div class="card shadow-sm border-0 mb-3"><div class="card-header bg-white fw-semibold"><i class="bi bi-journal-medical text-success me-2"></i>1. Antecedentes</div><div class="card-body row g-3"><div class="col-md-3"><label class="form-label fw-semibold">Fecha de atención</label><input class="form-control" type="date" name="assessment_date" value="{{ old('assessment_date',$assessment->assessment_date?->format('Y-m-d')) }}" @readonly($assessment->exists) required></div><div class="col-12"><label class="form-label fw-semibold">Historia clínica relevante</label><textarea class="form-control" rows="3" name="clinical_history" placeholder="Diagnósticos, comorbilidades y evolución clínica relevante">{{ old('clinical_history',$assessment->clinical_history) }}</textarea></div><div class="col-12"><label class="form-label fw-semibold">Historia nutricional</label><textarea class="form-control" rows="3" name="nutritional_history" placeholder="Cambios de peso, apetito, tolerancia, hábitos y soporte nutricional previo">{{ old('nutritional_history',$assessment->nutritional_history) }}</textarea></div></div></div>
<div class="card shadow-sm border-0 mb-3"><div class="card-header bg-white fw-semibold"><i class="bi bi-activity text-success me-2"></i>2. Evaluación nutricional</div><div class="card-body row g-3">@foreach(['appetite'=>'Apetito','dietary_intake'=>'Ingesta dietética','gastrointestinal_symptoms'=>'Síntomas gastrointestinales','functional_capacity'=>'Capacidad funcional','physical_findings'=>'Examen físico / hallazgos'] as $field=>$label)<div class="col-md-6"><label class="form-label fw-semibold">{{ $label }}</label><textarea class="form-control" rows="3" name="{{ $field }}" placeholder="Describa {{ strtolower($label) }}">{{ old($field,$assessment->$field) }}</textarea></div>@endforeach</div></div>
<div class="card shadow-sm border-0 mb-3"><div class="card-header bg-white fw-semibold"><i class="bi bi-clipboard2-check text-success me-2"></i>3. Diagnóstico y recomendaciones</div><div class="card-body row g-3"><div class="col-12"><label class="form-label fw-semibold">Diagnóstico nutricional <span class="text-danger">*</span></label><textarea class="form-control" rows="3" name="nutritional_diagnosis" required>{{ old('nutritional_diagnosis',$assessment->nutritional_diagnosis) }}</textarea></div>
<div class="col-12"><div class="d-flex justify-content-between align-items-center mb-2"><div><label class="form-label fw-semibold mb-0">Diagnósticos CIE-10</label><div class="form-text">Los diagnósticos registrados se imprimirán en la FUA de nutrición.</div></div><button id="addNutritionDiagnosis" type="button" class="btn btn-sm btn-outline-success"><i class="bi bi-plus-lg"></i> Agregar</button></div><div id="nutritionDiagnoses"></div></div>
<div class="col-md-6"><label class="form-label fw-semibold">Recomendaciones generales</label><textarea class="form-control" rows="4" name="general_recommendations">{{ old('general_recommendations',$assessment->general_recommendations) }}</textarea></div><div class="col-md-6"><label class="form-label fw-semibold">Recomendaciones dietéticas</label><textarea class="form-control" rows="4" name="dietary_recommendations">{{ old('dietary_recommendations',$assessment->dietary_recommendations) }}</textarea></div></div></div><div class="d-flex justify-content-end gap-2"><a class="btn btn-light" href="{{ route('nutrition.index') }}">Cancelar</a><button class="btn btn-success px-4"><i class="bi bi-check2-circle me-1"></i>Guardar atención</button></div></form></div>@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const box = document.getElementById('nutritionDiagnoses');
    const initial = @json($savedDiagnoses);
    let index = 0;
    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character]));
    const addDiagnosis = (item = {}) => {
        if (box.children.length >= 6) return;
        const rowIndex = index++;
        box.insertAdjacentHTML('beforeend', `<div class="row g-2 align-items-center mb-2 nutrition-diagnosis-row"><div class="col-md-2"><input type="hidden" name="diagnoses[${rowIndex}][cie10_id]" value="${escapeHtml(item.cie10_id)}"><input class="form-control nutrition-cie-code" name="diagnoses[${rowIndex}][codigo]" value="${escapeHtml(item.codigo)}" placeholder="Código" autocomplete="off"></div><div class="col-md-7 position-relative"><input class="form-control nutrition-cie-description" name="diagnoses[${rowIndex}][descripcion]" value="${escapeHtml(item.descripcion)}" placeholder="Buscar diagnóstico CIE-10" autocomplete="off"><div class="nutrition-cie-results d-none"></div></div><div class="col-md-2"><select class="form-select" name="diagnoses[${rowIndex}][type]"><option value="P" ${item.type === 'P' ? 'selected' : ''}>Presuntivo</option><option value="D" ${!item.type || item.type === 'D' ? 'selected' : ''}>Definitivo</option><option value="R" ${item.type === 'R' ? 'selected' : ''}>Repetitivo</option></select></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-nutrition-diagnosis" aria-label="Eliminar diagnóstico">×</button></div></div>`);
    };
    (initial.length ? initial : [{}]).forEach(addDiagnosis);
    document.getElementById('addNutritionDiagnosis').addEventListener('click', () => addDiagnosis());
    let timer;
    box.addEventListener('input', event => {
        if (!event.target.matches('.nutrition-cie-code,.nutrition-cie-description')) return;
        const row = event.target.closest('.nutrition-diagnosis-row');
        const results = row.querySelector('.nutrition-cie-results');
        const term = event.target.value.trim();
        row.querySelector('[type=hidden]').value = '';
        clearTimeout(timer);
        if (term.length < 2) { results.classList.add('d-none'); return; }
        timer = setTimeout(async () => {
            const response = await fetch(`{{ route('referrals.cie10.search') }}?q=${encodeURIComponent(term)}`);
            const items = await response.json();
            results.innerHTML = items.map(item => `<div class="nutrition-cie-option" data-id="${item.id}" data-code="${escapeHtml(item.codigo)}" data-description="${escapeHtml(item.descripcion)}"><strong>${escapeHtml(item.codigo)}</strong> — ${escapeHtml(item.descripcion)}</div>`).join('') || '<div class="p-3 text-muted">Sin resultados</div>';
            results.classList.remove('d-none');
        }, 250);
    });
    box.addEventListener('click', event => {
        const option = event.target.closest('.nutrition-cie-option');
        if (option) {
            const row = option.closest('.nutrition-diagnosis-row');
            row.querySelector('[type=hidden]').value = option.dataset.id;
            row.querySelector('.nutrition-cie-code').value = option.dataset.code;
            row.querySelector('.nutrition-cie-description').value = option.dataset.description;
            row.querySelector('.nutrition-cie-results').classList.add('d-none');
        }
        if (event.target.classList.contains('remove-nutrition-diagnosis') && box.children.length > 1) event.target.closest('.nutrition-diagnosis-row').remove();
    });
});
</script>
@endpush
