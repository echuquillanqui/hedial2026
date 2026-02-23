@extends('layouts.app')

@section('content')
<div class="container px-0 py-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-success text-uppercase">
            <i class="bi bi-clipboard2-pulse me-2"></i> Control Médico de Hemodiálisis
        </h4>
    </div>

    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body">
            <form action="{{ route('medicals.index') }}" method="GET" id="filterForm" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Paciente / Código</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-success text-success"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-success filter-input" 
                               placeholder="Nombre, DNI o Código..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Módulo</label>
                    <select name="modulo" class="form-select border-success filter-input">
                        <option value="">TODOS</option>
                        @foreach(['1','2','3','4'] as $m)
                            <option value="{{ $m }}" {{ request('modulo') == $m ? 'selected' : '' }}>MÓDULO {{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Turno</label>
                    <select name="turno" class="form-select border-success filter-input">
                        <option value="">TODOS</option>
                        @foreach(['1','2','3','4'] as $t)
                            <option value="{{ $t }}" {{ request('turno') == $t ? 'selected' : '' }}>TURNO {{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Fecha</label>
                    <input type="date" name="date" class="form-control border-success filter-input" 
                           value="{{ request('date', date('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted text-uppercase" style="font-size: 0.65rem;">Estado de Sesión</label>
                    <select name="estado" class="form-select border-success filter-input">
                        <option value="">TODOS</option>
                        <option value="en_curso" {{ request('estado') == 'en_curso' ? 'selected' : '' }}>🟡 EN CURSO</option>
                        <option value="finalizado" {{ request('estado') == 'finalizado' ? 'selected' : '' }}>🟢 FINALIZADO</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">PACIENTE</th>
                        <th class="text-center">MOD</th>
                        <th class="text-center">T</th>
                        <th class="text-center">ESTADO</th>
                        <th class="text-center">VITALES INICIO</th>
                        <th class="text-center">Responsable de rellenado</th>
                        <th class="text-center pe-3">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($medicals as $medical)
                    <tr>
                        <td class="ps-3">
                            <div class="fw-bold text-uppercase small text-dark">{{ $medical->order->patient->surname }} {{ $medical->order->patient->first_name }}</div>
                            <span class="text-muted fw-bold" style="font-size: 0.7rem;">{{ $medical->order->codigo_unico }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-success border border-success">{{ $medical->order->sala }}</span>
                        </td>
                        <td class="text-center small fw-bold">T-{{ $medical->order->turno }}</td>
                        <td class="text-center">
                            @if($medical->hora_final)
                                <span class="badge bg-success-subtle text-success border border-success px-2">FINALIZADO</span>
                            @else
                                <span class="badge bg-warning-subtle text-dark border border-warning px-2">EN CURSO</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <small class="d-block text-muted" style="font-size: 0.65rem;">PA: <strong>{{ $medical->pa_inicial }}</strong></small>
                            <small class="d-block text-muted" style="font-size: 0.65rem;">PESO: <strong>{{ $medical->peso_inicial }} kg</strong></small>
                        </td>

                        <td class="text-center">
                            <div class="fw-bold small">{{ $medical->usuarioInicia->name ?? '---' }}</div>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-info btn-show-modal mx-2" 
                                        data-url="{{ route('medicals.show', $medical->id) }}" title="Ver Detalles">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a href="{{ route('medicals.edit', $medical->id) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No se encontraron registros.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-0 py-3">
            {{ $medicals->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="ajaxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title text-uppercase fw-bold"><i class="bi bi-file-earmark-medical me-2"></i> Detalle de la Atención</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="text-center p-5">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2 text-muted">Cargando información médica...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('filterForm');
    const inputs = document.querySelectorAll('.filter-input');

    // Envío automático al cambiar filtros
    inputs.forEach(input => {
        input.addEventListener('change', () => form.submit());
        if (input.type === 'text') {
            let timeout = null;
            input.addEventListener('keyup', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => form.submit(), 800);
            });
        }
    });

    // Manejo del Modal AJAX
    document.querySelectorAll('.btn-show-modal').forEach(button => {
        button.addEventListener('click', function() {
            const url = this.getAttribute('data-url');
            const modalBody = document.getElementById('modalContent');
            const myModal = new bootstrap.Modal(document.getElementById('ajaxModal'));
            
            myModal.show();

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(response => response.text())
                .then(html => { modalBody.innerHTML = html; })
                .catch(error => { modalBody.innerHTML = '<div class="alert alert-danger">Error al cargar.</div>'; });
        });
    });
});
</script>
@endsection