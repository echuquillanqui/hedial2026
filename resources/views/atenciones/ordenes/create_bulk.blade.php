@extends('layouts.app')

@section('content')

<style>
    .card-header { background: #198754; color: white; font-weight: bold; text-transform: uppercase; font-size: 0.85rem; }
    .data-title { font-size: 0.65rem; color: #6c757d; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; display: block; }
    .sticky-config { position: sticky; top: 20px; }
    .filter-card { border-radius: 10px; background-color: #f8f9fa; border: 1px solid #e9ecef; }
</style>

<div class="container-fluid px-4 py-3">
    <div class="card shadow-sm border-0 mb-4 filter-card">
        <div class="card-body py-3">
            <form action="{{ route('orders.create') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="data-title text-success">Secuencia Programada</label>
                    <select name="secuencia" class="form-select border-success shadow-sm" required>
                        <option value="">-- SELECCIONAR --</option>
                        <option value="L-M-V" {{ request('secuencia') == 'L-M-V' ? 'selected' : '' }}>L-M-V</option>
                        <option value="M-J-S" {{ request('secuencia') == 'M-J-S' ? 'selected' : '' }}>M-J-S</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="data-title text-success">Turno</label>
                    <select name="turno" class="form-select border-success shadow-sm" required>
                        <option value="">-- SELECCIONAR --</option>
                        <option value="1" {{ request('turno') == '1' ? 'selected' : '' }}>1ER TURNO</option>
                        <option value="2" {{ request('turno') == '2' ? 'selected' : '' }}>2DO TURNO</option>
                        <option value="3" {{ request('turno') == '3' ? 'selected' : '' }}>3ER TURNO</option>
                        <option value="4" {{ request('turno') == '4' ? 'selected' : '' }}>4TO TURNO</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="data-title text-success">Módulo Asignado</label>
                    <select name="modulo" class="form-select border-success shadow-sm" required>
                        <option value="">-- SELECCIONAR --</option>
                        <option value="1" {{ request('modulo') == '1' ? 'selected' : '' }}>MÓDULO 1</option>
                        <option value="2" {{ request('modulo') == '2' ? 'selected' : '' }}>MÓDULO 2</option>
                        <option value="3" {{ request('modulo') == '3' ? 'selected' : '' }}>MÓDULO 3</option>
                        <option value="4" {{ request('modulo') == '4' ? 'selected' : '' }}>MÓDULO 4</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">
                        <i class="bi bi-person-check-fill me-1"></i> BUSCAR
                    </button>
                </div>

                <div class="col-md-2">
                    <a href="{{ route('orders.index') }}" class="btn btn-danger w-100 fw-bold shadow-sm">
                        <i class="bi bi-arrow-left me-1"></i> VOLVER
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-2">
            BÚSQUEDA INDIVIDUAL (SIN FILTRO DE MÓDULO/TURNO/SECUENCIA)
        </div>
        <div class="card-body">
            <form action="{{ route('orders.create') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-10">
                    <label class="data-title text-success">Paciente (DNI / H.C. / Apellidos y Nombres)</label>
                    <input type="text" name="patient_search" class="form-control border-success shadow-sm"
                           value="{{ request('patient_search') }}" placeholder="Ej: 45879632 o Pérez Juana">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-success w-100 fw-bold shadow-sm">
                        <i class="bi bi-search me-1"></i> BUSCAR
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(isset($manualPatients) && $manualPatients->count() > 0)
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header py-2">RESULTADOS DE BÚSQUEDA INDIVIDUAL</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>PACIENTE</th>
                                <th class="text-center">DNI</th>
                                <th class="text-center">H.C.</th>
                                <th class="text-center">SECUENCIA</th>
                                <th class="text-center">TURNO</th>
                                <th class="text-center">MÓDULO</th>
                                <th class="text-center">FECHA</th>
                                <th class="text-center">HORAS HD</th>
                                <th class="text-center">ACCIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($manualPatients as $patient)
                                <tr>
                                    <td class="small fw-bold text-uppercase">{{ $patient->surname }} {{ $patient->last_name }}, {{ $patient->first_name }} {{ $patient->other_names }}</td>
                                    <td class="text-center small">{{ $patient->dni }}</td>
                                    <td class="text-center small">{{ $patient->medical_history_number ?? 'N/A' }}</td>
                                    <td class="text-center small">{{ $patient->secuencia ?? 'SIN ASIGNAR' }}</td>
                                    <td class="text-center small">{{ $patient->turno ?? 'SIN ASIGNAR' }}</td>
                                    <td class="text-center small">{{ $patient->modulo ?? 'SIN ASIGNAR' }}</td>
                                    <td colspan="4">
                                        <form action="{{ route('orders.store') }}" method="POST" class="row g-2 align-items-center manual-order-form">
                                            @csrf
                                            <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                            <div class="col-md-3">
                                                <input type="date" name="fecha_orden" class="form-control form-control-sm border-success" value="{{ date('Y-m-d') }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="horas_dialisis" class="form-control form-control-sm border-success text-center" value="3.5" step="0.5" min="0.5" required>
                                            </div>
                                            <div class="col-md-2">
                                                <select name="turno" class="form-select form-select-sm border-success" required>
                                                    <option value="">Turno</option>
                                                    @foreach(['1', '2', '3', '4'] as $t)
                                                        <option value="{{ $t }}" {{ (string) $patient->turno === $t ? 'selected' : '' }}>{{ $t }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <select name="modulo_selector" class="form-select form-select-sm border-success manual-module" required>
                                                    <option value="">Módulo</option>
                                                    @foreach(['1', '2', '3', '4'] as $m)
                                                        <option value="{{ $m }}" {{ (string) $patient->modulo === $m ? 'selected' : '' }}>{{ $m }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" name="sala" class="manual-sala" value="{{ $patient->modulo ? 'MODULO ' . $patient->modulo : '' }}">
                                            </div>
                                            <div class="col-md-3">
                                                <button type="submit" class="btn btn-sm btn-primary fw-bold w-100"
                                                        onclick="return confirm('¿Generar orden individual para este paciente?')">
                                                    GENERAR INDIVIDUAL
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif(request()->filled('patient_search'))
        <div class="alert alert-warning border-0 shadow-sm text-center py-3 mt-2 rounded-3">
            No se encontraron pacientes con el criterio de búsqueda ingresado.
        </div>
    @endif

    @if(isset($patients) && $patients->count() > 0)
    <form action="{{ route('orders.store_bulk') }}" method="POST" x-data="{ selected: [] }">
        @csrf
        <div class="row">
            <div class="col-lg-3">
                <div class="sticky-config">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-header py-3">DATOS DE GENERACIÓN</div>
                        <div class="card-body">
                            <input type="hidden" name="sala" value="MODULO {{ request('modulo') }}">
                            
                            <div class="mb-3">
                                <label class="data-title text-success">Ubicación Destino</label>
                                <div class="form-control bg-light fw-bold text-center border-0">
                                    <i class="bi bi-door-open me-2"></i>MÓDULO {{ request('modulo') }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="data-title text-success">Fecha Programada</label>
                                <input type="date" id="fecha_orden_global" name="fecha_orden" class="form-control border-success shadow-sm" value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div x-show="selected.length > 0" x-transition>
                                <div class="alert alert-success py-2 border-0 shadow-sm mb-3">
                                    <small class="fw-bold"><i class="bi bi-info-circle me-1"></i> Se crearán <span x-text="selected.length"></span> órdenes clínicas.</small>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg w-100 shadow fw-bold" 
                                        onclick="return confirm('¿Está seguro de generar estas órdenes masivamente?')">
                                    <i class="bi bi-gear-fill me-2"></i>GENERAR AHORA
                                </button>
                            </div>
                            
                            <div x-show="selected.length === 0" class="text-center p-3 border rounded border-dashed text-muted small">
                                Seleccione al menos un paciente para continuar
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="card shadow-sm border-0">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>PACIENTES DEL TURNO {{ request('turno') }} - MÓDULO {{ request('modulo') }}</span>
                        <div class="form-check m-0">
                            <input type="checkbox" class="form-check-input border-white cursor-pointer" id="checkAll" 
                                   @change="selected = $el.checked ? {{ json_encode($patients->pluck('id')) }} : []">
                            <label class="form-check-label text-white small fw-bold cursor-pointer" for="checkAll">Seleccionar Todos</label>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 60px;">SEL.</th>
                                        <th>APELLIDOS Y NOMBRES</th>
                                        <th class="text-center">H.C.</th>
                                        <th class="text-center" style="width: 140px;">HORAS HD</th>
                                        <th class="text-center">COVID</th>
                                        <th class="text-center">ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($patients as $patient)
                                    <tr data-patient-id="{{ $patient->id }}" data-turno="{{ $patient->turno }}">
                                        <td class="text-center">
                                            <input type="checkbox" name="patient_ids[]" value="{{ $patient->id }}" 
                                                   x-model="selected" class="form-check-input border-success shadow-sm">
                                        </td>
                                        <td>
                                            <div class="fw-bold text-uppercase small text-dark">{{ $patient->surname }} {{ $patient->last_name }}, {{ $patient->first_name }} {{ $patient->other_names }}</div>
                                            <span class="badge bg-light text-success border border-success small" style="font-size: 0.65rem;">
                                                MOD: {{ $patient->modulo }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold text-muted small">{{ $patient->medical_history_number ?? 'N/A' }}</td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="horas_individual[{{ $patient->id }}]" 
                                                       data-horas-input="{{ $patient->id }}"
                                                       class="form-control text-center border-success fw-bold" 
                                                       value="3.5" step="0.5" min="0.5">
                                                <span class="input-group-text bg-light text-success border-success fw-bold small">hrs</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input type="checkbox" name="covid_flags[{{ $patient->id }}]" class="form-check-input cursor-pointer shadow-sm">
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('orders.store') }}" method="POST" class="d-inline-block order-single-form" data-patient-id="{{ $patient->id }}">
                                                @csrf
                                                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                                <input type="hidden" name="turno" value="{{ $patient->turno }}">
                                                <input type="hidden" name="sala" value="MODULO {{ request('modulo') }}">
                                                <input type="hidden" name="fecha_orden" data-single-date value="{{ date('Y-m-d') }}">
                                                <input type="hidden" name="horas_dialisis" data-single-hours value="3.5">
                                                <button type="submit" class="btn btn-sm btn-outline-primary fw-bold"
                                                        onclick="return confirm('¿Generar orden individual para este paciente?')">
                                                    GENERAR
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @else
        @if(request()->filled('modulo'))
            <div class="alert alert-warning border-0 shadow-sm text-center py-5 mt-4 rounded-3">
                <i class="bi bi-people fs-1 d-block mb-3"></i>
                <h5 class="fw-bold">No se encontraron pacientes</h5>
                <p class="mb-0">Verifique que los pacientes tengan asignado el <strong>Módulo {{ request('modulo') }}</strong> en su perfil clínico para este turno.</p>
            </div>
        @endif
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const globalDateInput = document.getElementById('fecha_orden_global');

    const syncSingleForms = () => {
        const selectedDate = globalDateInput?.value || '{{ date('Y-m-d') }}';

        document.querySelectorAll('.order-single-form').forEach(form => {
            const patientId = form.getAttribute('data-patient-id');
            const hoursInput = document.querySelector(`[data-horas-input="${patientId}"]`);

            const hoursField = form.querySelector('[data-single-hours]');
            const dateField = form.querySelector('[data-single-date]');

            if (hoursField && hoursInput) {
                hoursField.value = hoursInput.value || '3.5';
            }

            if (dateField) {
                dateField.value = selectedDate;
            }
        });
    };

    syncSingleForms();

    globalDateInput?.addEventListener('change', syncSingleForms);
    document.querySelectorAll('[data-horas-input]').forEach(input => {
        input.addEventListener('input', syncSingleForms);
    });

    document.querySelectorAll('.manual-order-form').forEach(form => {
        const moduloSelect = form.querySelector('.manual-module');
        const salaInput = form.querySelector('.manual-sala');

        const syncSala = () => {
            salaInput.value = moduloSelect.value ? `MODULO ${moduloSelect.value}` : '';
        };

        syncSala();
        moduloSelect.addEventListener('change', syncSala);
    });
});
</script>
@endpush
@endsection
