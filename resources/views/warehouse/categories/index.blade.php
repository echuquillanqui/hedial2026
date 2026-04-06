@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Logística - Categorías</h4>
            <small class="text-muted">Registro base para clasificar materiales.</small>
        </div>
        @can('warehouse.requests.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
            <i class="bi bi-plus-circle"></i> Nueva categoría
        </button>
        @endcan
    </div>

    <form method="GET" class="card shadow-sm p-3 mb-3">
        <div class="row g-2">
            <div class="col-md-10">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar categoría...">
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Filtrar</button></div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Nombre</th><th>Descripción</th><th># Materiales</th></tr></thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td class="fw-semibold">{{ $category->name }}</td>
                            <td>{{ $category->description ?: '-' }}</td>
                            <td>{{ $category->materials_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-4 text-muted">Sin categorías registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $categories->links() }}</div>
    </div>
</div>

@can('warehouse.requests.create')
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="{{ route('warehouse.categories.store') }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Registrar categoría</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Nombre</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div>
          <label class="form-label">Descripción</label>
          <input type="text" name="description" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>
@endcan
@endsection
