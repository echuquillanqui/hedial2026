@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Logística - Materiales</h4>
            <small class="text-muted">Todos los materiales deben pertenecer a una categoría.</small>
        </div>
        @can('warehouse.requests.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#materialModal">
            <i class="bi bi-plus-circle"></i> Nuevo material
        </button>
        @endcan
    </div>

    <form method="GET" class="card shadow-sm p-3 mb-3">
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

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Código</th><th>Material</th><th>Categoría</th><th>Unidad</th><th>Estado</th></tr></thead>
                <tbody>
                    @forelse($materials as $material)
                        <tr>
                            <td>{{ $material->code }}</td>
                            <td class="fw-semibold">{{ $material->name }}</td>
                            <td>{{ $material->category?->name ?? 'Sin categoría' }}</td>
                            <td>{{ $material->unit }}</td>
                            <td><span class="badge bg-{{ $material->is_active ? 'success' : 'secondary' }}">{{ $material->is_active ? 'ACTIVO' : 'INACTIVO' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">Sin materiales registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $materials->links() }}</div>
    </div>
</div>

@include('warehouse.requests.partials.material-modal')
@endsection
