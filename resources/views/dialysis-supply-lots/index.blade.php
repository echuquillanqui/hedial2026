@extends('layouts.app')
@section('content')
<div class="container py-4">
 <div class="mb-4"><span class="badge bg-primary bg-opacity-10 text-primary">CONFIGURACIÓN GLOBAL</span><h2 class="mb-1">Lotes para hemodiálisis</h2><p class="text-muted mb-0">Relaciona la medida del dializador y los sets de líneas con su lote. Esta configuración alimenta automáticamente el Anexo 11.</p></div>
 @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
 @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <div class="card shadow-sm mb-4"><div class="card-header bg-white fw-semibold">Registrar lote</div><div class="card-body">
  <form method="POST" action="{{ route('dialysis-supply-lots.store') }}" class="row g-3 align-items-end" x-data="{ category: '{{ old('category', \App\Models\DisposableDiscard::DIALYZER) }}' }">@csrf
   <div class="col-md-3"><label class="form-label">Insumo</label><select name="category" class="form-select" x-model="category"><option value="DIALYZER">Dializador</option><option value="BLOOD_LINES">Set de líneas</option></select></div>
   <div class="col-md-2" x-show="category === 'DIALYZER'"><label class="form-label">Medida (m²)</label><select name="measurement" class="form-select">@foreach(['1.3','1.5','1.8','1.9','2.1','2.2'] as $measure)<option @selected(old('measurement')===$measure)>{{ $measure }}</option>@endforeach</select></div>
   <div class="col-md-3"><label class="form-label">Número de lote</label><input name="lot_number" value="{{ old('lot_number') }}" maxlength="80" class="form-control" required></div>
   <div class="col-md-2"><label class="form-label">Válido desde</label><input type="date" name="valid_from" value="{{ old('valid_from') }}" class="form-control" required></div>
   <div class="col-md-2"><label class="form-label">Válido hasta</label><input type="date" name="valid_until" value="{{ old('valid_until') }}" class="form-control" required></div>
   <div class="col-12 text-end"><button class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Registrar lote global</button></div>
  </form>
 </div></div>
 <div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Insumo</th><th>Medida visible en medicina</th><th>Lote</th><th>Vigencia</th><th>Estado</th><th></th></tr></thead><tbody>
 @forelse($lots as $lot)<tr><td>{{ $lot->category === 'DIALYZER' ? 'Dializador' : 'Set de líneas' }}</td><td>{{ $lot->measurement ? $lot->measurement.' m²' : 'No aplica' }}</td><td class="fw-semibold">{{ $lot->lot_number }}</td><td>{{ $lot->valid_from->format('d/m/Y') }} al {{ $lot->valid_until->format('d/m/Y') }}</td><td><span class="badge text-bg-{{ $lot->is_active ? 'success' : 'secondary' }}">{{ $lot->is_active ? 'Activo' : 'Inactivo' }}</span></td><td>@if($lot->is_active)<form method="POST" action="{{ route('dialysis-supply-lots.destroy', $lot) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Desactivar</button></form>@endif</td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">Aún no hay lotes configurados.</td></tr>@endforelse
 </tbody></table></div></div>
</div>
@endsection
