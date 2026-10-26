@extends('layouts.app')

@section('content')
<style>
    .correction-hero{padding:1.5rem 1.75rem;color:#fff;border-radius:1.1rem;background:linear-gradient(125deg,#102a67 0%,#174ea6 58%,#0d9488 135%);box-shadow:0 .7rem 1.7rem rgba(16,42,103,.2)}
    .correction-edit-dialog[hidden]{display:none!important}
    .correction-edit-dialog{position:fixed;inset:0;z-index:2050;display:flex;align-items:center;justify-content:center;padding:1rem;background:rgba(10,24,48,.62);backdrop-filter:blur(3px)}
    .correction-edit-panel{width:min(680px,calc(100vw - 2rem));max-height:calc(100vh - 2rem);overflow:hidden;background:#fff;border:1px solid rgba(255,255,255,.6);border-radius:1.1rem;box-shadow:0 1.5rem 4rem rgba(11,31,60,.32)}
    .correction-edit-form{display:flex;flex-direction:column;max-height:calc(100vh - 2rem);min-width:0}
    .correction-edit-head{display:flex;align-items:center;gap:1rem;padding:1.2rem 1.4rem;color:#fff;background:linear-gradient(120deg,#102a67,#174ea6 65%,#0d9488)}
    .correction-edit-mark{display:grid;place-items:center;flex:0 0 2.8rem;height:2.8rem;border-radius:.85rem;background:rgba(255,255,255,.16);font-size:1.25rem}
    .correction-edit-head h5{margin:0;font-weight:700}.correction-edit-subtitle{margin:.2rem 0 0;color:rgba(255,255,255,.78);font-size:.86rem}
    .correction-edit-close{margin-left:auto;border:0;background:rgba(255,255,255,.14);color:#fff;border-radius:.6rem;width:2.3rem;height:2.3rem;font-size:1.3rem;line-height:1}
    .correction-edit-body{padding:1.35rem 1.4rem;overflow-y:auto;overflow-x:hidden}
    .correction-edit-body label{font-size:.87rem;font-weight:650;color:#344563;margin-bottom:.45rem}
    .correction-edit-body .form-control{width:100%;max-width:100%;border-color:#d6dfeb;border-radius:.65rem;min-height:44px;box-shadow:none}
    .correction-edit-body textarea.form-control{min-height:105px;resize:vertical}
    .correction-edit-options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.65rem;margin-top:.4rem}
    .correction-edit-option{display:flex;align-items:center;gap:.7rem;min-width:0;padding:.75rem .85rem;border:1px solid #e2e8f0;border-radius:.7rem;background:#f8fafc;color:#344563;cursor:pointer;transition:border-color .15s,background .15s}
    .correction-edit-option:hover{border-color:#91b4e8;background:#f1f6ff}.correction-edit-option input{width:1rem;height:1rem;flex:0 0 auto;accent-color:#174ea6}.correction-edit-option span{font-size:.84rem;line-height:1.25}
    .correction-edit-foot{display:flex;justify-content:flex-end;gap:.65rem;padding:1rem 1.4rem;border-top:1px solid #e8edf4;background:#fbfcfe}
    @media(max-width:575px){.correction-edit-dialog{padding:.5rem}.correction-edit-panel{width:calc(100vw - 1rem);max-height:calc(100vh - 1rem)}.correction-edit-form{max-height:calc(100vh - 1rem)}.correction-edit-options{grid-template-columns:1fr}.correction-edit-head,.correction-edit-body{padding:1rem}.correction-edit-foot{padding:.8rem 1rem}}
</style>
<div class="container-fluid px-md-4 py-3" x-data="fuaPdfViewer()">
    <div class="correction-hero d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div><span class="small text-uppercase font-weight-bold">Centro de impresiones</span><h2 class="mb-1 text-white">FUAs de subsanación</h2><p class="mb-0 text-white-50">Consulta, edita e imprime las subsanaciones generadas.</p></div>
        <a href="{{ route('fuas.corrections.create') }}" class="btn btn-light text-primary font-weight-bold"><i class="bi bi-plus-lg mr-1"></i>Generar subsanación</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->has('corrections'))<div class="alert alert-warning">{{ $errors->first('corrections') }}</div>@endif
    <form method="GET" class="card card-body shadow-sm mb-4">
        <div class="row align-items-end">
            <div class="col-lg-5 form-group mb-2"><label for="search">Subsanación, FUA original, paciente o DNI</label><input class="form-control" id="search" name="search" value="{{ request('search') }}"></div>
            <div class="col-lg-3 form-group mb-2"><label for="generated_from">Generada desde</label><input type="date" class="form-control" id="generated_from" name="generated_from" value="{{ request('generated_from') }}"></div>
            <div class="col-lg-3 form-group mb-2"><label for="generated_to">Generada hasta</label><input type="date" class="form-control" id="generated_to" name="generated_to" value="{{ request('generated_to') }}"></div>
            <div class="col-lg-1 form-group mb-2"><button class="btn btn-primary btn-block"><i class="bi bi-search"></i></button></div>
        </div>
    </form>
    <form id="correctionBulkPrint" method="POST" action="{{ route('fuas.corrections.bulk-pdf') }}" x-ref="correctionBulkForm">
        @csrf
        <input type="hidden" name="scope" value="selected">
        <input type="hidden" name="search" value="{{ request('search') }}">
        <input type="hidden" name="generated_from" value="{{ request('generated_from') }}">
        <input type="hidden" name="generated_to" value="{{ request('generated_to') }}">
        <input type="hidden" name="page" value="{{ $corrections->currentPage() }}">
    </form>
    <div class="card shadow-sm"><div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap"><div><span class="font-weight-bold text-primary">Subsanaciones generadas <span class="badge badge-primary">{{ $corrections->total() }}</span></span><div class="small text-muted mt-1">Selecciona las FUAs y abre el visor de impresión por bloques.</div></div><button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#correctionBulkModal"><i class="bi bi-printer mr-1"></i>Imprimir por bloques</button></div><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="thead-light"><tr><th><input type="checkbox" aria-label="Seleccionar todas las visibles" onclick="document.querySelectorAll('.correction-row-check').forEach(el=>el.checked=this.checked)"></th><th>FUA subsanación</th><th>FUA original</th><th>Paciente</th><th>DNI</th><th>Motivo</th><th>Generada</th><th class="text-right">Acciones</th></tr></thead>
        <tbody>
        @forelse($corrections as $correction)
            <tr>
                <td><input class="correction-row-check" type="checkbox" name="corrections[]" value="{{ $correction->id }}" form="correctionBulkPrint" aria-label="Seleccionar {{ $correction->number }}"></td>
                <td><strong>{{ $correction->number }}</strong></td>
                <td>{{ $correction->originalFua?->number }}</td>
                <td>{{ $correction->order?->patient?->full_name }}</td>
                <td>{{ $correction->order?->patient?->dni }}</td>
                <td>{{ $correction->reason }}</td>
                <td>{{ $correction->created_at?->format('d/m/Y H:i') }}</td>
                <td class="text-right text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('fuas.corrections.pdf', $correction) }}"><i class="bi bi-printer"></i> PDF</a>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openCorrectionEdit(this)" data-url="{{ route('fuas.corrections.update', $correction) }}" data-number="{{ $correction->number }}" data-original="{{ $correction->originalFua?->number }}" data-patient="{{ $correction->order?->patient?->full_name }}" data-dni="{{ $correction->order?->patient?->dni }}" data-reason="{{ $correction->reason }}" data-preset="{{ $correction->observation_preset }}" data-observations="{{ $correction->observations }}" data-doctor="{{ (int)$correction->show_doctor }}" data-stamp="{{ (int)$correction->show_stamp }}" data-medications="{{ (int)$correction->show_medications }}" data-hemodialysis="{{ (int)$correction->show_hemodialysis }}" data-laboratories="{{ (int)$correction->show_laboratories }}" data-diagnoses="{{ (int)$correction->show_diagnoses }}"><i class="bi bi-pencil"></i> Editar</button>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-5">Aún no hay FUAs de subsanación generadas.</td></tr>
        @endforelse
        </tbody>
    </table></div><div class="card-footer">{{ $corrections->links() }}</div></div>
<div class="modal fade" id="correctionBulkModal" tabindex="-1" aria-labelledby="correctionBulkModalLabel" aria-hidden="true" data-page="{{ $corrections->currentPage() }}" data-total="{{ $corrections->total() }}">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg">
        <div class="modal-header text-white" style="background:linear-gradient(120deg,#102a67,#174ea6 65%,#0d9488)"><div><h5 class="modal-title" id="correctionBulkModalLabel"><i class="bi bi-printer mr-2"></i>Imprimir FUAs de subsanación</h5><small class="text-white-50">Elige qué conjunto deseas cargar en el visor PDF.</small></div><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body"><div class="list-group">
            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" @click="openCorrectionBulk('selected')"><span><i class="bi bi-check2-square text-danger mr-2"></i>FUAs seleccionadas</span><span class="badge badge-danger" id="correctionSelectedCount">0</span></button>
            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" @click="openCorrectionBulk('current_page')"><span><i class="bi bi-file-earmark text-primary mr-2"></i>Página actual ({{ $corrections->currentPage() }})</span><span class="badge badge-primary">{{ $corrections->count() }}</span></button>
            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" @click="openCorrectionBulk('all_filtered')"><span><i class="bi bi-files text-success mr-2"></i>Todo el bloque filtrado</span><span class="badge badge-success">{{ $corrections->total() }}</span></button>
        </div><div class="small text-muted mt-3">Los filtros de búsqueda y fechas aplicados se respetarán.</div></div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button></div>
    </div></div>
</div>
<div class="correction-edit-dialog" id="correctionEditDialog" hidden role="dialog" aria-modal="true" aria-labelledby="correctionEditTitle" onclick="if(event.target===this)closeCorrectionEdit()">
    <div class="correction-edit-panel"><form id="correctionEditForm" class="correction-edit-form" method="POST">@csrf @method('PUT')
        <header class="correction-edit-head"><span class="correction-edit-mark"><i class="bi bi-pencil-square"></i></span><div><h5 id="correctionEditTitle">Editar subsanación</h5><p class="correction-edit-subtitle" id="correctionEditSubtitle">Actualiza los datos de impresión.</p></div><button type="button" class="correction-edit-close" aria-label="Cerrar" onclick="closeCorrectionEdit()">&times;</button></header>
        <div class="correction-edit-body">
            <div class="form-group mb-3"><label for="editReason">Motivo de subsanación</label><select class="form-control" name="reason" id="editReason"><option value="FALTA MEDICAMENTOS">Falta medicamentos</option><option value="FALTA DATOS DEL MEDICO">Falta datos del médico</option><option value="FALTA PROCEDIMIENTO">Falta procedimiento</option><option value="OTRO">Otro</option></select></div>
            <div class="form-group mb-3"><label for="editObservationPreset">Observación predefinida</label><select class="form-control" name="observation_preset" id="editObservationPreset"><option value="">Ninguna</option><option value="PACIENTE NO INDICA MOTIVO DE NO FIRMA">PACIENTE NO INDICA MOTIVO DE NO FIRMA</option></select></div>
            <div class="form-group mb-4"><label for="editObservations">Observación adicional</label><textarea class="form-control" name="observations" id="editObservations" placeholder="Escribe el texto adicional que se imprimirá en observaciones"></textarea></div>
            <label class="d-block mb-2">Secciones visibles en la FUA</label>
            <div class="correction-edit-options">@foreach(['doctor'=>'Mostrar datos del médico','stamp'=>'Mostrar sello del médico','medications'=>'Mostrar detalle de medicamentos','hemodialysis'=>'Mostrar sesiones de hemodiálisis','laboratories'=>'Mostrar laboratorios y exámenes','diagnoses'=>'Mostrar diagnósticos'] as $key=>$label)<label class="correction-edit-option" for="edit-{{ $key }}"><input type="checkbox" id="edit-{{ $key }}" name="show_{{ $key }}" value="1"><span>{{ $label }}</span></label>@endforeach</div>
        </div><footer class="correction-edit-foot"><button type="button" class="btn btn-outline-secondary" onclick="closeCorrectionEdit()">Cancelar</button><button class="btn btn-primary px-4"><i class="bi bi-check2 mr-1"></i>Guardar cambios</button></footer>
    </form></div>
</div>
<script>
document.addEventListener('change',event=>{if(event.target.matches('.correction-row-check'))document.getElementById('correctionSelectedCount').textContent=document.querySelectorAll('.correction-row-check:checked').length;});
document.getElementById('correctionBulkModal').addEventListener('show.bs.modal',()=>document.getElementById('correctionSelectedCount').textContent=document.querySelectorAll('.correction-row-check:checked').length);
function openCorrectionEdit(button){const d=document.getElementById('correctionEditDialog'),f=document.getElementById('correctionEditForm');f.action=button.dataset.url;document.getElementById('correctionEditSubtitle').textContent='FUA '+button.dataset.number+' · Original '+button.dataset.original+' · '+button.dataset.patient+' · DNI '+button.dataset.dni;document.getElementById('editReason').value=button.dataset.reason;document.getElementById('editObservationPreset').value=button.dataset.preset||'';document.getElementById('editObservations').value=button.dataset.observations||'';['doctor','stamp','medications','hemodialysis','laboratories','diagnoses'].forEach(k=>document.getElementById('edit-'+k).checked=button.dataset[k]==='1');d.hidden=false;document.body.style.overflow='hidden';}
function closeCorrectionEdit(){document.getElementById('correctionEditDialog').hidden=true;document.body.style.overflow='';}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeCorrectionEdit();});
</script>
@include('fuas.partials.pdf-modal')
@include('fuas.partials.pdf-modal-script')
</div>
@endsection
