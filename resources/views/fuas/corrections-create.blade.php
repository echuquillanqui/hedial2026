@extends('layouts.app')

@section('content')
<style>
    .correction-create-hero{padding:1.35rem 1.6rem;color:#fff;border-radius:1.05rem;background:linear-gradient(125deg,#102a67 0%,#174ea6 58%,#0d9488 135%);box-shadow:0 .6rem 1.4rem rgba(16,42,103,.18)}
    .correction-dialog[hidden]{display:none!important}.correction-dialog{position:fixed;inset:0;z-index:2050;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(10,24,48,.62);backdrop-filter:blur(3px)}
    .correction-dialog__panel{width:min(720px,calc(100vw - 2rem));max-height:calc(100vh - 2rem);overflow:hidden;background:#fff;border:1px solid rgba(255,255,255,.6);border-radius:1.1rem;box-shadow:0 1.5rem 4rem rgba(11,31,60,.32)}
    .correction-dialog__form{display:flex;flex-direction:column;max-height:calc(100vh - 2rem);min-width:0}
    .correction-dialog__header{display:flex;align-items:center;gap:1rem;padding:1.2rem 1.4rem;color:#fff;background:linear-gradient(120deg,#102a67,#174ea6 65%,#0d9488)}
    .correction-dialog__mark{display:grid;place-items:center;flex:0 0 2.8rem;height:2.8rem;border-radius:.85rem;background:rgba(255,255,255,.16);font-size:1.25rem}.correction-dialog__header h5{margin:0;font-weight:700}.correction-dialog__patient{margin:.2rem 0 0;color:rgba(255,255,255,.8);font-size:.86rem}.correction-dialog__close{margin-left:auto;border:0;background:rgba(255,255,255,.14);color:#fff;border-radius:.6rem;width:2.3rem;height:2.3rem;font-size:1.3rem;line-height:1}
    .correction-dialog__body{padding:1.35rem 1.4rem;overflow-y:auto;overflow-x:hidden}.correction-dialog__body label{font-size:.87rem;font-weight:650;color:#344563;margin-bottom:.45rem}.correction-dialog__body .form-control{width:100%;max-width:100%;border-color:#d6dfeb;border-radius:.65rem;min-height:44px;box-shadow:none}.correction-dialog__body textarea.form-control{min-height:96px;resize:vertical}
    .correction-dialog__hint{display:flex;gap:.7rem;align-items:flex-start;padding:.8rem .9rem;background:#eff6ff;border:1px solid #d8e8ff;border-radius:.7rem;color:#31547f;font-size:.82rem}.correction-dialog__options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.65rem}.correction-dialog__option{display:flex;align-items:center;gap:.7rem;min-width:0;padding:.75rem .85rem;border:1px solid #e2e8f0;border-radius:.7rem;background:#f8fafc;color:#344563;cursor:pointer}.correction-dialog__option:hover{border-color:#91b4e8;background:#f1f6ff}.correction-dialog__option input{width:1rem;height:1rem;flex:0 0 auto;accent-color:#174ea6}.correction-dialog__option span{font-size:.84rem;line-height:1.25}
    .correction-dialog__footer{display:flex;justify-content:flex-end;gap:.65rem;padding:1rem 1.4rem;border-top:1px solid #e8edf4;background:#fbfcfe}
    @media(max-width:575px){.correction-dialog{padding:.5rem}.correction-dialog__panel{width:calc(100vw - 1rem);max-height:calc(100vh - 1rem)}.correction-dialog__form{max-height:calc(100vh - 1rem)}.correction-dialog__options{grid-template-columns:1fr}.correction-dialog__header,.correction-dialog__body{padding:1rem}.correction-dialog__footer{padding:.8rem 1rem}}
</style>
<div class="container-fluid px-md-4 py-3">
    <div class="correction-create-hero d-flex justify-content-between align-items-center flex-wrap mb-4"><div><span class="small text-uppercase font-weight-bold">Centro de impresiones</span><h2 class="mb-1 text-white">Generar subsanación</h2><p class="mb-0 text-white-50">Busca la FUA original por número, fecha, paciente o DNI.</p></div><a href="{{ route('fuas.corrections.index') }}" class="btn btn-light text-primary font-weight-bold">Volver al listado</a></div>
    <form method="GET" action="{{ route('fuas.corrections.search') }}" class="card card-body shadow-sm mb-4 border-0">
        <div class="row align-items-end">
            <div class="col-lg-3 form-group mb-2"><label for="type">Tipo de atención</label><select class="form-control" id="type" name="type"><option value="">Todos los tipos</option>@foreach(\App\Support\ClinicalService::ORDINARY_TYPES as $attentionType)<option value="{{ $attentionType }}" @selected(request('type')===$attentionType)>{{ \App\Support\ClinicalService::label($attentionType) }}</option>@endforeach</select></div>
            <div class="col-lg-4 form-group mb-2"><label for="search">Número de FUA, paciente o DNI</label><input class="form-control" id="search" name="search" value="{{ request('search') }}"></div>
            <div class="col-lg-3 form-group mb-2"><label for="date">Fecha de atención</label><input class="form-control" type="date" id="date" name="date" value="{{ request('date') }}"></div>
            <div class="col-lg-2 form-group mb-2"><button class="btn btn-primary btn-block"><i class="bi bi-search mr-1"></i>Buscar FUAs</button></div>
        </div>
    </form>
    @if(isset($fuas))
    <div class="card shadow-sm border-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="thead-light"><tr><th>FUA original</th><th>Fecha</th><th>Paciente</th><th>DNI</th><th>Módulo</th><th>Turno</th><th>Secuencia</th><th>Tipo</th><th>Acción</th></tr></thead><tbody>
        @forelse($fuas as $fua)
        <tr><td>{{ $fua->number }}</td><td>{{ optional($fua->order?->fecha_orden)->format('d/m/Y') }}</td><td>{{ $fua->order?->patient?->full_name }}</td><td>{{ $fua->order?->patient?->dni }}</td><td>{{ $fua->order?->dialysis_module ?: $fua->order?->patient?->modulo ?: '—' }}</td><td>{{ $fua->order?->turno ? 'Turno '.$fua->order->turno : '—' }}</td><td>{{ $fua->order?->patient?->secuencia ?: '—' }}</td><td>{{ \App\Support\ClinicalService::label($fua->type) }}</td><td>
            @if($fua->corrections->isNotEmpty())<span class="text-muted">Ya tiene subsanación</span>@else<button class="btn btn-sm btn-primary" type="button" onclick="openCorrectionDialog('correction-{{ $fua->id }}')">Preparar subsanación</button>@endif
        </td></tr>
        @empty<tr><td colspan="9" class="text-center text-muted py-4">No se encontraron FUAs para los filtros indicados.</td></tr>@endforelse
    </tbody></table></div><div class="card-footer">{{ $fuas->links() }}</div></div>
    @foreach($fuas as $fua)@if($fua->corrections->isEmpty())
    <div class="correction-dialog" id="correction-{{ $fua->id }}" hidden role="dialog" aria-modal="true" aria-labelledby="correction-title-{{ $fua->id }}" onclick="if(event.target===this)closeCorrectionDialog(this)"><div class="correction-dialog__panel"><form class="correction-dialog__form" method="POST" action="{{ route('fuas.corrections.generate',$fua) }}">@csrf
        <header class="correction-dialog__header"><span class="correction-dialog__mark"><i class="bi bi-file-earmark-plus"></i></span><div><h5 id="correction-title-{{ $fua->id }}">Preparar subsanación</h5><p class="correction-dialog__patient">FUA {{ $fua->number }} · {{ $fua->order?->patient?->full_name }} · DNI {{ $fua->order?->patient?->dni }}</p></div><button type="button" class="correction-dialog__close" aria-label="Cerrar" onclick="closeCorrectionDialog(this.closest('.correction-dialog'))">&times;</button></header>
        <div class="correction-dialog__body">
            <div class="form-group mb-3"><label for="reason-{{ $fua->id }}">Motivo de subsanación</label><select class="form-control" id="reason-{{ $fua->id }}" name="reason" required><option value="FALTA MEDICAMENTOS">Falta medicamentos</option><option value="FALTA DATOS DEL MEDICO">Falta datos del médico</option><option value="FALTA PROCEDIMIENTO">Falta procedimiento (hemodiálisis/laboratorios)</option><option value="OTRO">Otro</option></select></div>
            <div class="form-group mb-4"><label for="preset-{{ $fua->id }}">Observación predefinida</label><select class="form-control mb-2" id="preset-{{ $fua->id }}" name="observation_preset"><option value="">Ninguna</option><option value="PACIENTE NO INDICA MOTIVO DE NO FIRMA">PACIENTE NO INDICA MOTIVO DE NO FIRMA</option></select><label for="observations-{{ $fua->id }}">Observación adicional</label><textarea class="form-control" id="observations-{{ $fua->id }}" name="observations" placeholder="Escribe aquí el texto que aparecerá en observaciones"></textarea></div>
            <label class="d-block mb-2">Secciones visibles en la FUA</label><div class="correction-dialog__options">
                @foreach(['show_doctor'=>'Datos del médico','show_stamp'=>'Sello del médico','show_medications'=>'Detalle de medicamentos','show_hemodialysis'=>'Sesiones de hemodiálisis','show_laboratories'=>'Laboratorios y exámenes','show_diagnoses'=>'Diagnósticos'] as $field=>$label)
                <label class="correction-dialog__option" for="{{ $field }}-{{ $fua->id }}"><input id="{{ $field }}-{{ $fua->id }}" name="{{ $field }}" type="checkbox" value="1" checked><span>Incluir {{ $label }}</span></label>
                @endforeach
            </div>
            <div class="correction-dialog__hint mt-3"><i class="bi bi-info-circle-fill"></i><span>El formato conserva todas sus tablas. Las opciones desmarcadas dejan vacía la información correspondiente.</span></div>
        </div><footer class="correction-dialog__footer"><button type="button" class="btn btn-outline-secondary" onclick="closeCorrectionDialog(this.closest('.correction-dialog'))">Cancelar</button><button class="btn btn-primary px-4"><i class="bi bi-printer mr-1"></i>Generar e imprimir</button></footer>
    </form></div></div>@endif @endforeach
    @endif
</div>
<script>
function openCorrectionDialog(id){const d=document.getElementById(id);if(d){d.hidden=false;document.body.style.overflow='hidden';}}
function closeCorrectionDialog(d){if(d){d.hidden=true;document.body.style.overflow='';}}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeCorrectionDialog(document.querySelector('.correction-dialog:not([hidden])'));});
</script>
@endsection
