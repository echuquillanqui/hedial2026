@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    .card-header { background: #198754; color: white; font-weight: bold; }
    .btn-action { padding: 5px 10px; font-size: 0.75rem; font-weight: bold; }
    .data-title { font-size: 0.65rem; color: #6c757d; font-weight: bold; text-transform: uppercase; }
    .filter-label { font-size: 0.7rem; font-weight: bold; color: #198754; text-transform: uppercase; margin-bottom: 2px; }
    .modal-label { font-size: 0.7rem; font-weight: 800; color: #198754; text-transform: uppercase; display: block; margin-bottom: 4px; }
    .modal-content { border-radius: 15px; overflow: hidden; }
</style>

<div class="container px-0 py-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-uppercase m-0 text-success"><i class="bi bi-file-earmark-medical me-2"></i> Control de Órdenes</h4>
        <a href="{{ route('orders.create') }}" class="btn btn-success shadow-sm fw-bold">
            <i class="bi bi-plus-circle me-1"></i> GENERAR ÓRDENES
        </a>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light py-3">
            <form id="filterForm" action="{{ route('orders.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="filter-label">Paciente / Código</label>
                    <input type="text" name="search" class="form-control form-control-sm border-success filter-input" 
                           placeholder="Escriba para buscar..." value="{{ request('search') }}" autocomplete="off">
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Fecha</label>
                    <input type="date" name="date" class="form-control form-control-sm border-success filter-input" 
                           value="{{ request('date', date('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Turno</label>
                    <select name="turno" class="form-select form-select-sm border-success filter-input">
                        <option value="">-- TODOS --</option>
                        <option value="1" {{ request('turno') == '1' ? 'selected' : '' }}>1ER TURNO</option>
                        <option value="2" {{ request('turno') == '2' ? 'selected' : '' }}>2DO TURNO</option>
                        <option value="3" {{ request('turno') == '3' ? 'selected' : '' }}>3ER TURNO</option>
                        <option value="4" {{ request('turno') == '4' ? 'selected' : '' }}>4TO TURNO</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Módulo / Sala</label>
                    <select name="sala" class="form-select form-select-sm border-success filter-input">
                        <option value="">-- TODAS LAS SALAS --</option>
                        <option value="MODULO 1" {{ request('sala') == 'MODULO 1' ? 'selected' : '' }}>MODULO 1</option>
                        <option value="MODULO 2" {{ request('sala') == 'MODULO 2' ? 'selected' : '' }}>MODULO 2</option>
                        <option value="MODULO 3" {{ request('sala') == 'MODULO 3' ? 'selected' : '' }}>MODULO 3</option>
                        <option value="MODULO 4" {{ request('sala') == 'MODULO 4' ? 'selected' : '' }}>MODULO 4</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary w-100 fw-bold">
                        <i class="bi bi-arrow-clockwise me-1"></i> LIMPIAR
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3 data-title text-left">Código</th>
                            <th class="data-title text-left">Paciente</th>
                            <th class="data-title text-center">Sala</th>
                            <th class="data-title text-center">Turno</th>
                            <th class="data-title text-center">Horas</th>
                            <th class="data-title text-center">Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td class="px-3 fw-bold text-success small text-left">{{ $order->codigo_unico }}</td>
                            <td class="text-start">
                                <div class="fw-bold text-uppercase small">{{ $order->patient->surname }} {{ $order->patient->last_name }}, {{ $order->patient->first_name }} {{ $order->patient->other_names }}</div>
                            </td>
                            <td class="text-center"><span class="badge bg-light text-success border border-success">{{ $order->sala }}</span></td>
                            <td class="fw-bold small text-center">TURNO - {{ $order->turno }}</td>
                            <td class="fw-bold text-primary text-center">{{ number_format($order->horas_dialisis, 1) }}</td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editOrderModal"
                                            data-id="{{ $order->id }}" data-paciente="{{ $order->patient->surname }} {{ $order->patient->first_name }}"
                                            data-sala="{{ $order->sala }}" data-turno="{{ $order->turno }}" data-horas="{{ $order->horas_dialisis }}" data-fecha="{{ $order->fecha_orden }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteOrderModal"
                                            data-id="{{ $order->id }}" data-paciente="{{ $order->patient->surname }} {{ $order->patient->first_name }}" data-codigo="{{ $order->codigo_unico }}">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted">No se encontraron órdenes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0">{{ $orders->links() }}</div>
    </div>
</div>

<div class="modal fade" id="editOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title fw-bold text-uppercase"><i class="bi bi-pencil-square me-2"></i> Editar Orden</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editOrderForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3 p-2 bg-light rounded border-start border-4 border-success">
                        <label class="modal-label">Paciente</label>
                        <input type="text" id="modal_paciente" class="form-control-plaintext fw-bold p-0" readonly>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="modal-label">Módulo</label>
                            <select name="sala" id="modal_sala" class="form-select border-success" required>
                                <option value="MODULO 1">MODULO 1</option>
                                <option value="MODULO 2">MODULO 2</option>
                                <option value="MODULO 3">MODULO 3</option>
                                <option value="MODULO 4">MODULO 4</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="modal-label">Turno</label>
                            <select name="turno" id="modal_turno" class="form-select border-success" required>
                                <option value="1">1ER TURNO</option>
                                <option value="2">2DO TURNO</option>
                                <option value="3">3ER TURNO</option>
                                <option value="4">4TO TURNO</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="modal-label">Horas HD</label>
                            <input type="number" name="horas_dialisis" id="modal_horas" class="form-control border-success fw-bold" step="0.5" required>
                        </div>
                        <div class="col-md-6">
                            <label class="modal-label">Fecha</label>
                            <input type="date" name="fecha_orden" id="modal_fecha" class="form-control border-success" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">CANCELAR</button>
                    <button type="submit" class="btn btn-sm btn-success fw-bold px-4">GUARDAR CAMBIOS</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white py-2">
                <h6 class="modal-title fw-bold text-uppercase"><i class="bi bi-exclamation-triangle me-2"></i> Confirmar</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="text-danger mb-3"><i class="bi bi-trash3 fs-1"></i></div>
                <p class="mb-1 fw-bold text-uppercase" id="del_paciente" style="font-size: 0.85rem;"></p>
                <p class="text-muted small">¿Está seguro de eliminar esta orden? Esta acción eliminará también la hoja médica y de enfermería asociada.</p>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-center border-0">
                <button type="button" class="btn btn-sm btn-secondary fw-bold" data-bs-dismiss="modal">NO, CANCELAR</button>
                <form id="deleteOrderForm" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger fw-bold px-3">SÍ, ELIMINAR</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lógica Filtros Reactivos
    const filterForm = document.getElementById('filterForm');
    document.querySelectorAll('.filter-input').forEach(input => {
        input.addEventListener(input.type === 'text' ? 'keyup' : 'change', () => {
            clearTimeout(window.filterTimer);
            window.filterTimer = setTimeout(() => filterForm.submit(), input.type === 'text' ? 600 : 0);
        });
    });

    // Lógica Modal Editar
    document.getElementById('editOrderModal').addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('editOrderForm').action = `/orders/${btn.getAttribute('data-id')}`;
        document.getElementById('modal_paciente').value = btn.getAttribute('data-paciente');
        document.getElementById('modal_sala').value = btn.getAttribute('data-sala');
        document.getElementById('modal_turno').value = btn.getAttribute('data-turno');
        document.getElementById('modal_horas').value = btn.getAttribute('data-horas');
        document.getElementById('modal_fecha').value = btn.getAttribute('data-fecha');
    });

    // Lógica Modal Eliminar
    document.getElementById('deleteOrderModal').addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('deleteOrderForm').action = `/orders/${btn.getAttribute('data-id')}`;
        document.getElementById('del_paciente').innerText = btn.getAttribute('data-paciente');
    });
});
</script>
@endpush
@endsection