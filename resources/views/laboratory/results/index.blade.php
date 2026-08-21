@extends('layouts.app')
@section('content')
<div class="container-fluid fissal-index" x-data="{selected:[], applyFilters() { this.$refs.filters.requestSubmit() }}">
 <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4"><div><span class="overline">GESTIÓN CLÍNICA</span><h2>Laboratorio FISSAL</h2><p class="text-muted mb-0">Resultados, seguimiento e impresión por paciente.</p></div><a href="{{ route('laboratory.orders.create') }}" class="btn btn-success rounded-pill px-4"><i class="bi bi-plus-lg me-2"></i>Nueva orden</a></div>
 @if(session('success'))<div class="alert alert-success border-0">{{ session('success') }}</div>@endif
 <div class="card filter-card mb-4">
  <div class="card-body">
   <form class="row g-2 align-items-end" x-ref="filters">
    <div class="col-xl-4 col-md-6">
     <label for="laboratory-query" class="form-label filter-label">Paciente</label>
     <input id="laboratory-query" name="q" value="{{ request('q') }}" class="form-control" placeholder="Buscar paciente..." @input.debounce.400ms="applyFilters()">
    </div>
    <div class="col-xl-2 col-md-3">
     <label for="laboratory-date" class="form-label filter-label">Fecha</label>
     <input id="laboratory-date" type="date" name="date" value="{{ request('date') }}" class="form-control" @change="applyFilters()">
    </div>
    <div class="col-xl-2 col-md-3">
     <label for="laboratory-sequence" class="form-label filter-label">Secuencia</label>
     <select id="laboratory-sequence" name="sequence" class="form-select" @change="applyFilters()">
      <option value="">Todas las secuencias</option>
      <option value="L-M-V" @selected(request('sequence') === 'L-M-V')>L-M-V</option>
      <option value="M-J-S" @selected(request('sequence') === 'M-J-S')>M-J-S</option>
     </select>
    </div>
    <div class="col-xl-2 col-md-4">
     <label for="laboratory-period" class="form-label filter-label">Periodo</label>
     <select id="laboratory-period" name="period" class="form-select" @change="applyFilters()"><option value="">Todos los periodos</option>@foreach(['M'=>'Mensual','B'=>'Bimensual','T'=>'Trimestral','S'=>'Semestral'] as $key=>$label)<option value="{{ $key }}" @selected(request('period')===$key)>{{ $label }}</option>@endforeach</select>
    </div>
    <div class="col-xl-1 col-md-4">
     <label for="laboratory-status" class="form-label filter-label">Estado</label>
     <select id="laboratory-status" name="status" class="form-select" @change="applyFilters()"><option value="">Todos</option><option value="pending" @selected(request('status')==='pending')>Pendiente</option><option value="completed" @selected(request('status')==='completed')>Completado</option></select>
    </div>
    <div class="col-xl-1 col-md-4"><a href="{{ route('laboratory.results.index') }}" class="btn btn-outline-secondary w-100" title="Limpiar filtros" aria-label="Limpiar filtros"><i class="bi bi-x-lg"></i></a></div>
   </form>
  </div>
 </div>
 <form method="POST" action="{{ route('laboratory.results.bulk-pdf') }}" target="_blank">@csrf
 <div class="bulkbar mb-3" x-show="selected.length" x-cloak><strong x-text="`${selected.length} órdenes seleccionadas`"></strong><button class="btn btn-light btn-sm"><i class="bi bi-printer me-2"></i>Imprimir en bloque</button></div>
 <div class="card table-card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th><input type="checkbox" @change="selected=$event.target.checked?@js($orders->pluck('id')->map(fn($id)=>(string)$id)):[]"></th><th>Paciente</th><th>Control</th><th>Fecha</th><th>Avance</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>
 @forelse($orders as $order) @php($done=$order->items->whereNotNull('completed_at')->count())
 <tr><td><input name="order_ids[]" value="{{ $order->id }}" type="checkbox" x-model="selected"></td><td><div class="patient-name">{{ $order->patient_name }}</div><small>DNI {{ $order->patient?->dni ?: '—' }} · H.C. {{ $order->patient?->medical_history_number ?: '—' }}</small></td><td><span class="period period-{{ strtolower($order->period) }}">{{ $order->period }}</span> {{ ['M'=>'Mensual','B'=>'Bimensual','T'=>'Trimestral','S'=>'Semestral'][$order->period] }}</td><td>{{ $order->sampled_at?->format('d/m/Y') ?: $order->created_at->format('d/m/Y') }}</td><td><strong>{{ $done }}/{{ $order->items->count() }}</strong><div class="progress"><div class="progress-bar" style="width:{{ $order->items->count() ? $done/$order->items->count()*100 : 0 }}%"></div></div></td><td><span class="status {{ $order->status }}">{{ $order->status==='completed'?'Completado':'Pendiente' }}</span></td><td class="text-end"><a href="{{ route('laboratory.results.show',$order) }}" class="btn btn-sm btn-outline-success" title="Ver y actualizar"><i class="bi bi-pencil-square"></i></a> <a target="_blank" href="{{ route('laboratory.results.pdf',$order) }}" class="btn btn-sm btn-outline-dark" title="PDF"><i class="bi bi-file-earmark-pdf"></i></a></td></tr>
 @empty<tr><td colspan="7" class="text-center py-5 text-muted">No hay órdenes para mostrar.</td></tr>@endforelse
 </tbody></table></div></div></form><div class="mt-3">{{ $orders->links() }}</div>
</div>
<style>.fissal-index{--green:#087f5b}.overline{font-size:11px;letter-spacing:2px;color:var(--green);font-weight:800}.fissal-index h2{font-weight:800;color:#163d35}.filter-card,.table-card{border:0;border-radius:16px;box-shadow:0 8px 28px #183f3512}.filter-label{color:#52736c;font-size:10px;font-weight:800;letter-spacing:.06em;margin-bottom:4px;text-transform:uppercase}.table thead th{background:#f1f7f5;color:#52736c;font-size:11px;text-transform:uppercase;padding:14px;border:0}.table td{padding:15px 12px;border-color:#edf2f0}.patient-name{font-weight:750;color:#183f37}.table small{color:#82958f}.period{display:inline-grid;place-items:center;width:30px;height:30px;border-radius:9px;background:#e0f4ed;color:var(--green);font-weight:800;margin-right:5px}.progress{height:4px;margin-top:5px;width:80px}.progress-bar{background:var(--green)}.status{padding:5px 9px;border-radius:20px;font-size:11px;font-weight:700}.status.completed{background:#dff5e9;color:#18794e}.status.pending{background:#fff0d4;color:#9a6400}.bulkbar{background:linear-gradient(90deg,#07684c,#07966b);color:white;padding:12px 18px;border-radius:12px;display:flex;justify-content:space-between;align-items:center}</style>
@endsection
