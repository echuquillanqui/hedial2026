@extends('layouts.app')

@section('content')
<style>
    .medical-list { font-size: 0.95rem; }
    .medical-list .small, .medical-list small { font-size: 0.82rem !important; }
    .medical-list .form-control, .medical-list .form-select { font-size: 0.95rem; }
</style>
<div class="container px-0 py-0 medical-list">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>No se pudieron guardar los medicamentos.</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
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
                        @foreach(\App\Models\Patient::MODULES as $m)
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
                        <th class="text-center">HORA DE INICIO</th>
                        <th class="text-center">HORA FINAL</th>
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
                            <span class="badge bg-light text-success border border-success">MÓDULO {{ $medical->order->patient->modulo ?: '—' }}</span>
                        </td>
                        <td class="text-center small fw-bold">T-{{ $medical->order->turno }}</td>
                        <td class="text-center fw-bold">
                            {{ $medical->hora_inicial ? substr($medical->hora_inicial, 0, 5) : '—' }}
                        </td>
                        <td class="text-center fw-bold">
                            {{ $medical->hora_final ? substr($medical->hora_final, 0, 5) : '—' }}
                        </td>
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
                                @can('medicals.edit')
                                <button type="button" class="btn btn-sm btn-outline-success btn-medications-modal me-2"
                                        data-action="{{ route('medicals.medications.update', $medical) }}"
                                        data-patient="{{ $medical->order->patient->surname }} {{ $medical->order->patient->first_name }}"
                                        data-epo2000="{{ $medical->epo2000 }}"
                                        data-epo4000="{{ $medical->epo4000 }}"
                                        data-hierro="{{ $medical->hierro }}"
                                        data-vitamina-b12="{{ $medical->vitamina_b12 }}"
                                        data-calcitriol="{{ $medical->calcitriol }}"
                                        data-heparina="{{ $medical->heparina }}"
                                        title="Rellenar medicamentos">
                                    <i class="bi bi-capsule"></i>
                                </button>
                                @endcan
                                <a href="{{ route('medicals.edit', $medical->id) }}" class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">No se encontraron registros.</td>
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

<div class="modal fade" id="medicationsModal" tabindex="-1" aria-labelledby="medicationsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" id="medicationsForm" class="modal-content">
            @csrf
            @method('PATCH')
            <div class="modal-header bg-success text-white">
                <div>
                    <h5 class="modal-title fw-bold" id="medicationsModalLabel"><i class="bi bi-capsule me-2"></i>Medicamentos</h5>
                    <small id="medicationsPatient" class="text-white-50"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Registre la cantidad o dosis indicada para esta sesión de hemodiálisis.</p>
                <div class="row g-3">
                    @foreach([
                        'epo2000' => 'EPO 2000',
                        'epo4000' => 'EPO 4000',
                        'hierro' => 'Hierro',
                        'vitamina_b12' => 'Vitamina B12',
                        'calcitriol' => 'Calcitriol',
                        'heparina' => 'Heparina',
                    ] as $field => $label)
                    <div class="col-md-6">
                        <label for="medication_{{ $field }}" class="form-label fw-bold">{{ $label }}</label>
                        <input type="text" class="form-control" id="medication_{{ $field }}" name="{{ $field }}"
                               maxlength="50" autocomplete="off" placeholder="Cantidad o dosis">
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Guardar medicamentos</button>
            </div>
        </form>
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

    const medicationsModalElement = document.getElementById('medicationsModal');
    if (medicationsModalElement) {
        const medicationsModal = new bootstrap.Modal(medicationsModalElement);
        const medicationsForm = document.getElementById('medicationsForm');
        const medicationFields = ['epo2000', 'epo4000', 'hierro', 'vitamina_b12', 'calcitriol', 'heparina'];

        document.querySelectorAll('.btn-medications-modal').forEach(button => {
            button.addEventListener('click', function() {
                medicationsForm.action = this.dataset.action;
                document.getElementById('medicationsPatient').textContent = this.dataset.patient;

                medicationFields.forEach(field => {
                    const dataKey = field.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());
                    document.getElementById(`medication_${field}`).value = this.dataset[dataKey] || '';
                });

                medicationsModal.show();
            });
        });
    }
});
</script>
@endsection
