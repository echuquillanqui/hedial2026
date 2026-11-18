@extends('layouts.app')
@section('content')
<div class="container result-editor"><div class="d-flex justify-content-between align-items-center mb-4"><div><a href="{{ route('laboratory.results.index') }}" class="small text-decoration-none">← Volver a Laboratorio FISSAL</a><h2 class="mt-2">Resultados del paciente</h2></div><a target="_blank" href="{{ route('laboratory.results.pdf',$order) }}" class="btn btn-outline-dark"><i class="bi bi-file-earmark-pdf me-2"></i>Vista PDF</a></div>
<div class="patient-banner mb-4"><div><small>PACIENTE</small><strong>{{ $order->patient_name }}</strong></div><div><small>DNI</small><strong>{{ $order->patient?->dni ?: '—' }}</strong></div><div><small>HISTORIA CLÍNICA</small><strong>{{ $order->patient?->medical_history_number ?: '—' }}</strong></div><div><small>CONTROL</small><strong>{{ $order->period }} · {{ $order->sampled_at?->format('d/m/Y') }}</strong></div></div>
@if(session('toastr'))<div class="alert alert-{{ session('toastr.type') === 'success' ? 'success' : 'warning' }} alert-dismissible fade show" role="status">{{ session('toastr.message') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button></div>@endif
<form method="POST" action="{{ route('laboratory.results.update',$order) }}">@csrf @method('PUT')
@foreach($order->items->sortBy(fn($item)=>sprintf('%010d-%010d',$item->test->area_id,$item->test_id))->groupBy(fn($item)=>$item->test->area->name) as $area=>$items)<div class="card result-card mb-4"><div class="card-header">{{ $area }}</div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Análisis</th><th>Resultado anterior</th><th>Fecha anterior</th><th>Resultado actual</th><th>Unidad</th><th>Valores referenciales</th><th>Observación</th></tr></thead><tbody>@foreach($items as $item)@php($previous=$previousResults->get($item->test_id))@php($previousNumeric=$previous?preg_replace('/[^0-9,.+-]/','',(string)$previous->result_value):'')@php($previousNumeric=str_replace(',','.',$previousNumeric))@php($previousNumeric=is_numeric($previousNumeric)?(float)$previousNumeric:null)@php($currentValue=old('results.'.$item->id.'.value',$item->result_value))@php($currentNumeric=preg_replace('/[^0-9,.+-]/','',(string)$currentValue))@php($currentNumeric=str_replace(',','.',$currentNumeric))@php($currentNumeric=is_numeric($currentNumeric)?(float)$currentNumeric:null)@php($largeVariation=$previousNumeric!==null&&$currentNumeric!==null&&abs($currentNumeric-$previousNumeric)>=5)<tr><td class="fw-semibold">{{ $item->test->name }}</td><td class="text-muted">{{ $previous?->result_value ?? '—' }}</td><td class="text-muted text-nowrap">{{ $previous ? ($previous->order->sampled_at?->format('d/m/Y') ?? $previous->order->created_at->format('d/m/Y')) : '—' }}</td><td><input class="form-control result-input {{ $largeVariation?'border-warning':'' }} {{ $item->test->name === 'Hemoglobina' ? 'hemoglobin-result' : '' }}" data-test-name="{{ $item->test->name }}" name="results[{{ $item->id }}][value]" value="{{ $currentValue }}" {{ $item->test->name === 'Hemoglobina' ? 'readonly tabindex="-1" aria-label="Hemoglobina calculada desde el hematocrito"' : '' }}>@if($largeVariation)<div class="alert alert-warning py-1 px-2 mt-1 mb-0 small" role="alert"><i class="bi bi-exclamation-triangle-fill me-1"></i>Variación amplia: de {{ $previous->result_value }} a {{ $currentValue }}.</div>@endif</td><td>{{ $item->test->unit ?: '—' }}</td><td class="reference">{{ $item->test->reference_value ?: '—' }}</td><td><input class="form-control" name="results[{{ $item->id }}][notes]" value="{{ old('results.'.$item->id.'.notes',$item->result_notes) }}" placeholder="Opcional" tabindex="-1"></td></tr>@endforeach</tbody></table></div></div>@endforeach
<div class="sticky-save"><span><i class="bi bi-shield-check"></i> Los cambios quedarán registrados en la orden</span><div class="d-flex gap-2"><button type="submit" form="regenerate-electrolytes-form" class="btn btn-sm btn-outline-light" title="Recalcular potasio, calcio y fósforo"><i class="bi bi-arrow-repeat"></i> Actualizar</button><button class="btn btn-success px-4">Guardar resultados</button></div></div></form>
<form id="regenerate-electrolytes-form" method="POST" action="{{ route('laboratory.results.regenerate-electrolytes',$order) }}" class="d-none">@csrf</form>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100"><div id="resultToast" class="toast align-items-center text-bg-success border-0" role="status" aria-live="polite" aria-atomic="true"><div class="d-flex"><div class="toast-body">{{ session('toastr.message') }}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button></div></div></div>
@if(session('toastr'))<script>document.addEventListener('DOMContentLoaded',()=>{const toast=document.getElementById('resultToast');if(toast&&window.bootstrap?.Toast)bootstrap.Toast.getOrCreateInstance(toast,{delay:3500}).show();});</script>@endif
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-test-name="Hematocrito"]').forEach((hematocritInput) => {
        const tableBody = hematocritInput.closest('tbody');
        const hemoglobinInput = tableBody?.querySelector('[data-test-name="Hemoglobina"]');
        if (!hemoglobinInput) return;

        const calculateHemoglobin = () => {
            const raw = hematocritInput.value.trim().replace(',', '.');
            if (raw === '') {
                hemoglobinInput.value = '';
            } else if (Number.isFinite(Number(raw))) {
                hemoglobinInput.value = (Math.round((Number(raw) / 3) * 10) / 10).toFixed(1);
            }
        };

        hematocritInput.addEventListener('input', calculateHemoglobin);
        hematocritInput.addEventListener('change', calculateHemoglobin);
    });
});
</script>
</div>
<style>.result-editor h2{font-weight:800;color:#173f36}.patient-banner{background:linear-gradient(120deg,#063f36,#098661);color:#fff;padding:23px;border-radius:18px;display:grid;grid-template-columns:2fr repeat(3,1fr);gap:20px}.patient-banner small,.patient-banner strong{display:block}.patient-banner small{opacity:.65;font-size:10px;letter-spacing:1px}.result-card{border:0;border-radius:15px;overflow:hidden;box-shadow:0 6px 25px #183f3512}.result-card .card-header{background:#e8f6f1;color:#087f5b;font-weight:800;border:0;padding:14px 18px;text-transform:uppercase}.result-card th{font-size:10px;text-transform:uppercase;color:#69847e;background:#f8fbfa}.reference{font-size:12px;max-width:250px}.result-input{font-weight:750;color:#087f5b}.sticky-save{position:sticky;bottom:12px;background:#163f36;color:#fff;padding:13px 18px;border-radius:13px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 8px 30px #0003}@media(max-width:768px){.patient-banner{grid-template-columns:1fr 1fr}.sticky-save span{display:none}}</style>
@endsection
