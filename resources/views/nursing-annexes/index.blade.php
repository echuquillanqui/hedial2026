@extends('layouts.app')
@section('content')
<div class="container-fluid py-4 px-lg-4">
 <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><span class="badge bg-primary bg-opacity-10 text-primary">ENFERMERÍA</span><h2 class="mb-1">Anexos diarios de hemodiálisis</h2></div></div>
 @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
 @if($errors->any())<div class="alert alert-danger"><strong>No se pudo guardar.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

 @php($activeTab = request('tab') === 'discards' ? 'discards' : (request()->filled('history_date') || request()->filled('history_frequency') || request()->has('page') ? 'history' : 'annex-12'))
 <ul class="nav nav-tabs flex-nowrap overflow-auto mb-4" id="nursingAnnexTabs" role="tablist">
  <li class="nav-item" role="presentation"><button class="nav-link {{ $activeTab === 'annex-12' ? 'active' : '' }} text-nowrap" id="annex-12-tab" data-bs-toggle="tab" data-bs-target="#annex-12-panel" type="button" role="tab" aria-controls="annex-12-panel" aria-selected="{{ $activeTab === 'annex-12' ? 'true' : 'false' }}"><i class="bi bi-clipboard2-pulse me-1"></i> Anexo 12</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link {{ $activeTab === 'history' ? 'active' : '' }} text-nowrap" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-panel" type="button" role="tab" aria-controls="history-panel" aria-selected="{{ $activeTab === 'history' ? 'true' : 'false' }}"><i class="bi bi-clock-history me-1"></i> Historial <span class="badge rounded-pill bg-secondary ms-1">{{ $history->total() }}</span></button></li>
  <li class="nav-item" role="presentation"><button class="nav-link {{ $activeTab === 'discards' ? 'active' : '' }} text-nowrap" id="discards-tab" data-bs-toggle="tab" data-bs-target="#discards-panel" type="button" role="tab" aria-controls="discards-panel" aria-selected="{{ $activeTab === 'discards' ? 'true' : 'false' }}"><i class="bi bi-file-earmark-medical me-1"></i> Anexos 11-A y 11-B</button></li>
 </ul>

 <div class="tab-content" id="nursingAnnexTabContent">
 <div class="tab-pane fade {{ $activeTab === 'annex-12' ? 'show active' : '' }}" id="annex-12-panel" role="tabpanel" aria-labelledby="annex-12-tab" tabindex="0">
 <div class="card border-0 shadow-sm"><div class="card-header bg-white py-3"><h5 class="mb-0">Plantilla de llenado · Anexo 12</h5></div><div class="card-body">
  <form method="GET" class="row g-2 align-items-end mb-4">
   <div class="col-sm-4 col-lg-3"><label class="form-label">Día</label><input class="form-control" type="date" name="date" value="{{ $date }}"></div>
   <div class="col-sm-3 col-lg-2"><label class="form-label">Frecuencia</label><select class="form-select" name="frequency"><option value="LMV" @selected($frequency==='LMV')>LMV</option><option value="MJS" @selected($frequency==='MJS')>MJS</option></select></div>
   <div class="col-sm-3 col-lg-2"><label class="form-label">Módulo</label><select class="form-select" name="module">@foreach(\App\Models\Patient::MODULES as $m)<option value="{{ $m }}" @selected($module===$m)>Módulo {{ $m }}</option>@endforeach</select></div>
   <div class="col-sm-2"><button class="btn btn-primary w-100">Cargar</button></div>
  </form>
  <div class="alert alert-info d-flex justify-content-between align-items-center"><span><strong>{{ $annexOrders->count() }} sesiones</strong> encontradas. Los valores calculados se pueden corregir, incluso cuando cargan en cero.</span>@if($annex)<span class="badge bg-primary">{{ $annex->code }}</span>@else<span class="badge bg-secondary">Borrador nuevo</span>@endif</div>
  <form method="POST" action="{{ route('nursing-annexes.care.store') }}">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="frequency" value="{{ $frequency }}"><input type="hidden" name="module" value="{{ $module }}">
   <div class="table-responsive"><table class="table table-bordered table-sm align-middle"><thead class="table-light"><tr><th>Procedimiento</th><th>Tipo / detalle</th><th style="min-width:245px">Cantidad por turno</th><th>Observaciones</th></tr></thead><tbody>
   @foreach(\App\Services\DailyNursingAnnexService::ROWS as $key => [$procedure,$detail])
    @php($automatic = data_get($automaticValues,"$key.quantity",0)) @php($current = old("values.$key.quantity",data_get($values,"$key.quantity",$automatic)))
    <tr><td class="fw-semibold">{{ $procedure }}</td><td>{{ $detail }}</td><td><input type="hidden" name="values[{{ $key }}][quantity]" value="{{ $current }}"><div class="d-flex gap-1">@foreach(['1','2','3','4'] as $shift)@php($autoShift=data_get($automaticValues,"$key.shifts.$shift",0))@php($shiftValue=old("values.$key.shifts.$shift",data_get($values,"$key.shifts.$shift",$autoShift)))<div class="text-center"><small>T{{ $shift }}</small><input aria-label="Turno {{ $shift }}" class="form-control form-control-sm text-center {{ (int)$shiftValue !== (int)$autoShift ? 'border-warning bg-warning bg-opacity-10' : '' }} style="width:52px" type="number" min="0" max="9999" name="values[{{ $key }}][shifts][{{ $shift }}]" value="{{ $shiftValue }}"></div>@endforeach</div><small class="text-muted">Total automático: {{ $automatic }}</small></td><td><input class="form-control form-control-sm" name="values[{{ $key }}][observations]" maxlength="1000" value="{{ old("values.$key.observations",data_get($values,"$key.observations")) }}" placeholder="Paciente o detalle, cuando corresponda"></td></tr>
   @endforeach</tbody></table></div>
   <div class="d-flex flex-wrap gap-2 justify-content-end"><button class="btn btn-success">{{ $annex ? 'Actualizar anexo' : 'Guardar y generar ID' }}</button>@if($annex)@can('annexes.nursing.print')<a target="_blank" class="btn btn-outline-danger" href="{{ route('nursing-annexes.care.generated-pdf',$annex) }}"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a><a class="btn btn-outline-success" href="{{ route('nursing-annexes.care.generated-xlsx',$annex) }}"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>@endcan @endif</div>
  </form>
 </div></div></div>

 <div class="tab-pane fade {{ $activeTab === 'history' ? 'show active' : '' }}" id="history-panel" role="tabpanel" aria-labelledby="history-tab" tabindex="0">
 <div class="card border-0 shadow-sm"><div class="card-header bg-white py-3"><h5 class="mb-0">Historial de anexos generados</h5></div><div class="card-body">
  <form method="GET" class="row g-2 mb-3"><input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="frequency" value="{{ $frequency }}"><input type="hidden" name="module" value="{{ $module }}"><div class="col-md-3"><input type="date" name="history_date" value="{{ request('history_date') }}" class="form-control" aria-label="Filtrar historial por día"></div><div class="col-md-3"><select name="history_frequency" class="form-select"><option value="">Todas las frecuencias</option><option value="LMV" @selected(request('history_frequency')==='LMV')>LMV</option><option value="MJS" @selected(request('history_frequency')==='MJS')>MJS</option></select></div><div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrar historial</button></div>@if(request()->filled('history_date')||request()->filled('history_frequency'))<div class="col-md-2"><a class="btn btn-link" href="{{ route('nursing-annexes.index',['date'=>$date,'frequency'=>$frequency,'module'=>$module]) }}">Limpiar</a></div>@endif</form>
  <div class="table-responsive"><table class="table align-middle"><thead><tr><th>ID</th><th>Día</th><th>Frecuencia</th><th>Módulo</th><th>Actualizado por</th><th></th></tr></thead><tbody>@forelse($history as $item)<tr><td><code>{{ $item->code }}</code></td><td>{{ $item->work_date->format('d/m/Y') }}</td><td>{{ $item->frequency }}</td><td>{{ $item->module }}</td><td>{{ $item->generator?->name ?? 'Usuario no disponible' }}<br><small class="text-muted">{{ $item->updated_at->format('d/m/Y H:i') }}</small></td><td class="text-end"><a href="{{ route('nursing-annexes.index',['date'=>$item->work_date->format('Y-m-d'),'frequency'=>$item->frequency,'module'=>$item->module]) }}" class="btn btn-sm btn-outline-primary">Editar</a> @can('annexes.nursing.print')<a target="_blank" href="{{ route('nursing-annexes.care.generated-pdf',$item) }}" class="btn btn-sm btn-outline-danger">PDF</a><a href="{{ route('nursing-annexes.care.generated-xlsx',$item) }}" class="btn btn-sm btn-outline-success">Excel</a>@endcan</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Todavía no hay anexos generados con estos filtros.</td></tr>@endforelse</tbody></table></div>{{ $history->links() }}
 </div></div></div>

 <div class="tab-pane fade {{ $activeTab === 'discards' ? 'show active' : '' }}" id="discards-panel" role="tabpanel" aria-labelledby="discards-tab" tabindex="0">
 <div class="card border-0 shadow-sm annex-card">
  <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
   <div><h5 class="mb-1">Control de descartes · Anexos 11-A y 11-B</h5><small class="text-muted">Registra aquí los lotes que aparecerán en el cuadro resumen del PDF.</small></div>
   <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>{{ $orders->count() }} finalizadas</span>
  </div>
  <div class="card-body">
   <form method="GET" class="row g-3 align-items-end p-3 mb-4 rounded-3 bg-light border">
    <input type="hidden" name="tab" value="discards">
    <div class="col-sm-6 col-lg-3"><label class="form-label fw-semibold">Fecha de sesiones</label><input class="form-control" type="date" name="date" value="{{ $date }}"></div>
    <div class="col-sm-6 col-lg-2"><label class="form-label fw-semibold">Turno</label><select class="form-select" name="discard_shift"><option value="">Todos</option>@foreach(['1','2','3','4'] as $shift)<option value="{{ $shift }}" @selected($discardFilters['shift']===$shift)>Turno {{ $shift }}</option>@endforeach</select></div>
    <div class="col-sm-6 col-lg-2"><label class="form-label fw-semibold">Módulo</label><select class="form-select" name="discard_module"><option value="">Todos</option>@foreach(\App\Models\Patient::MODULES as $item)<option value="{{ $item }}" @selected($discardFilters['module']===$item)>Módulo {{ $item }}</option>@endforeach</select></div>
    <div class="col-sm-6 col-lg-2"><label class="form-label fw-semibold">Secuencia</label><select class="form-select" name="discard_sequence"><option value="">Todas</option>@foreach(['L-M-V','M-J-S'] as $item)<option value="{{ $item }}" @selected($discardFilters['sequence']===$item)>{{ $item }}</option>@endforeach</select></div>
    <div class="col-lg-3 d-flex gap-2"><button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Aplicar</button><a class="btn btn-outline-secondary" title="Limpiar filtros" href="{{ route('nursing-annexes.index',['date'=>$date,'tab'=>'discards']) }}"><i class="bi bi-arrow-counterclockwise"></i></a></div>
   </form>
   @php($pdfParams=['date'=>$date,'month'=>substr($date,0,7),'discard_shift'=>$discardFilters['shift'],'discard_module'=>$discardFilters['module'],'discard_sequence'=>$discardFilters['sequence']])
   <div class="row g-3 mb-4">
    @foreach([[\App\Models\DisposableDiscard::DIALYZER,'ANEXO 11-A','Descarte de dializadores','success'],[\App\Models\DisposableDiscard::BLOOD_LINES,'ANEXO 11-B','Set de líneas arteriales y venosas','danger']] as [$category,$label,$title,$color])
    <div class="col-md-6"><div class="border rounded-3 p-3 h-100 d-flex flex-wrap justify-content-between align-items-center gap-2"><div><span class="badge bg-{{ $color }}-subtle text-{{ $color }} mb-2">{{ $label }}</span><h6 class="mb-1">{{ $title }}</h6><small class="text-muted">Formato mensual según los filtros activos</small></div>@can('annexes.nursing.print')<div class="d-flex gap-2"><a target="_blank" class="btn btn-outline-danger" href="{{ route('nursing-annexes.discards.pdf',array_merge($pdfParams,['category'=>$category])) }}"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a><a class="btn btn-outline-success" href="{{ route('nursing-annexes.discards.xlsx',array_merge($pdfParams,['category'=>$category])) }}"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a></div>@endcan</div></div>
    @endforeach
   </div>
   <div class="alert alert-info d-flex gap-2 align-items-start" role="alert"><i class="bi bi-info-circle-fill mt-1"></i><div><strong>¿Dónde ingreso los datos del cuadro?</strong><br>Los lotes se registran una sola vez en <a href="{{ route('dialysis-supply-lots.index') }}" class="alert-link">Configuración global de lotes</a>. La medida elegida en Medicina identifica automáticamente el lote vigente del dializador; el set de líneas se obtiene por su vigencia. El Anexo 11 asigna el código y cuenta una atención finalizada por paciente y día.</div></div>
   <div class="table-responsive"><table class="table align-middle table-hover"><thead class="table-light"><tr><th>Sesión finalizada</th><th>Paciente</th><th>Programación</th><th>Dializador</th><th>Líneas registradas / consumo</th><th class="text-center">Estado</th></tr></thead><tbody>
    @forelse($discardRows as $discardRow)
     @include('nursing-annexes.partials.discard-row', $discardRow)
    @empty
     <tr><td colspan="6" class="text-center py-5"><i class="bi bi-inbox fs-2 d-block text-muted mb-2"></i><strong>No hay hemodiálisis finalizadas</strong><div class="text-muted">Ajusta los filtros o espera el cierre de las sesiones.</div></td></tr>
    @endforelse
   </tbody></table></div>
  </div>
 </div>
 </div>
 </div>
</div>
@endsection
