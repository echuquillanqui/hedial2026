@extends('layouts.app')

@section('content')
<div class="container-fluid logistics-page" x-data="{ consumptionOpen: false, materialId: null, materialName: '', automatic: false, quantity: 1 }">
    @include('warehouse.partials.navigation', ['title' => 'Catálogo y consumo automático', 'subtitle' => 'Define qué productos se descuentan al finalizar cada sesión de hemodiálisis.'])
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Productos configurados</h4>
            <small class="text-muted">Los consumibles automáticos se descuentan del stock de la sede de la atención.</small>
        </div>
        @can('warehouse.requests.create')
        @if($currentWarehouse?->is_principal)
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#materialModal">
            <i class="bi bi-plus-circle"></i> Nuevo material
        </button>
        @endif
        @endcan
    </div>

    <form method="GET" class="logistics-panel p-3 mb-4">
        <div class="row g-2">
            <div class="col-md-5"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por código o nombre..."></div>
            <div class="col-md-5">
                <select name="category_id" class="form-select">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string)request('category_id') === (string)$category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Filtrar</button></div>
        </div>
    </form>

    <div class="logistics-panel overflow-hidden">
        <div class="table-responsive">
            <table class="table logistics-table table-hover mb-0">
                <thead><tr><th>Código</th><th>Material</th><th>Categoría</th><th>Stock sede</th><th>Próximo vencimiento</th><th>Consumo por sesión</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse($materials as $material)
                        @php($stock = $material->stocks->first())
                        <tr>
                            <td>{{ $material->code }}</td>
                            <td class="fw-semibold">{{ $material->name }}</td>
                            <td>{{ $material->category?->name ?? 'Sin categoría' }}</td>
                            <td>{{ number_format((float) ($stock?->current_qty ?? 0), 2) }} {{ $material->unit }}<small class="d-block text-muted">Mín. {{ number_format((float) ($stock?->min_qty ?? 0), 2) }}</small></td>
                            @php($nextEntry = $material->stockEntries->first(fn ($entry) => $entry->expiration_date->isToday() || $entry->expiration_date->isFuture()))
                            <td>@if($nextEntry)<span class="badge {{ $nextEntry->expiration_date->lte(today()->addDays(30)) ? 'text-bg-warning' : 'text-bg-light' }}">{{ $nextEntry->expiration_date->format('d/m/Y') }}</span><small class="d-block text-muted">{{ $nextEntry->batch_number ? 'Lote '.$nextEntry->batch_number : 'Sin lote' }}</small>@else<span class="text-muted">Sin fecha vigente</span>@endif</td>
                            <td>
                                @if($material->automatic_consumption)
                                    <span class="badge rounded-pill bg-primary-subtle text-primary">
                                        <i class="bi bi-lightning-charge-fill"></i>
                                        {{ number_format((float) $material->quantity_per_session, 2) }} {{ $material->unit }}
                                    </span>
                                @else
                                    <span class="text-muted">Manual</span>
                                @endif
                            </td>
                            <td><span class="badge bg-{{ $material->is_active ? 'success' : 'secondary' }}">{{ $material->is_active ? 'ACTIVO' : 'INACTIVO' }}</span></td>
                            <td class="text-end">
                                @if($currentWarehouse?->is_principal)
                                    @can('warehouse.requests.create')
                                        <button type="button" class="btn btn-sm btn-outline-primary" @click="materialId={{ $material->id }}; materialName={{ Illuminate\Support\Js::from($material->name) }}; automatic={{ $material->automatic_consumption ? 'true' : 'false' }}; quantity='{{ $material->quantity_per_session }}'; consumptionOpen=true" data-bs-toggle="modal" data-bs-target="#consumptionModal" aria-label="Configurar consumo de {{ $material->name }}">
                                            <i class="bi bi-sliders"></i>
                                        </button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-4 text-muted">Sin materiales registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $materials->links() }}</div>
    </div>
</div>

@if($currentWarehouse?->is_principal)
@include('warehouse.requests.partials.material-modal')
<div class="modal fade" id="consumptionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><form class="modal-content" method="POST" :action="`{{ url('almacen/materiales') }}/${materialId}/consumo`">@csrf @method('PATCH')
    <div class="modal-header"><div><h5 class="modal-title">Consumo automático</h5><small class="text-muted" x-text="materialName"></small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="form-check form-switch p-3 ps-5 rounded bg-light mb-3"><input type="hidden" name="automatic_consumption" value="0"><input class="form-check-input" type="checkbox" name="automatic_consumption" value="1" x-model="automatic" id="automaticConsumption"><label class="form-check-label fw-bold" for="automaticConsumption">Descontar al finalizar una sesión</label><small class="d-block text-muted">Se aplica únicamente al almacén de la sede donde ocurrió la atención.</small></div>
      <label class="form-label">Cantidad por sesión</label><div class="input-group"><input class="form-control" type="number" name="quantity_per_session" min="0.01" step="0.01" x-model="quantity" :required="automatic" :disabled="!automatic"><span class="input-group-text">unidades</span></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar configuración</button></div>
  </form></div>
</div>
@endif
@endsection
