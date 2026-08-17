@extends('layouts.app')

@section('content')
<div class="container py-0">
    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-clipboard-pulse text-primary me-2"></i>Control de Enfermería
            </h3>
        </div>
    </div>

    @if($requiresModuleAssignment)
        <div class="alert {{ $moduleAssignment ? 'alert-primary' : 'alert-warning' }} border-0 shadow-sm d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3" role="alert">
            <div>
                <div class="fw-bold">
                    <i class="bi bi-grid-3x3-gap-fill me-2"></i>
                    {{ $moduleAssignment ? 'Módulo asignado para hoy: MÓDULO '.$moduleAssignment->module : 'Seleccione su módulo de trabajo para hoy' }}
                </div>
                <small>La lista de pacientes se filtrará automáticamente según esta selección.</small>
            </div>
            <form method="POST" action="{{ route('nurses.module-assignment.store') }}" class="d-flex gap-2">
                @csrf
                <select name="module" class="form-select form-select-sm" aria-label="Módulo de trabajo" required>
                    <option value="">Elegir módulo</option>
                    @foreach(range(1, 4) as $module)
                        <option value="{{ $module }}" @selected(optional($moduleAssignment)->module === $module)>MÓDULO {{ $module }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary text-nowrap" type="submit">
                    {{ $moduleAssignment ? 'Cambiar' : 'Confirmar' }}
                </button>
            </form>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form id="filterForm" class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">BUSCAR PACIENTE</label>
                    <input type="text" name="search" id="searchInput" class="form-control form-control-sm" placeholder="Nombre, DNI...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">FECHA</label>
                    <input type="date" name="date" id="dateSelect" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">MÓDULO</label>
                    <select name="modulo" id="moduloSelect" class="form-select form-select-sm">
                        @if($requiresModuleAssignment)
                            <option value="{{ optional($moduleAssignment)->module }}" selected>
                                {{ $moduleAssignment ? 'MÓDULO '.$moduleAssignment->module : 'SIN ASIGNAR' }}
                            </option>
                        @else
                        <option value="">TODOS</option>
                        <option value="1">MÓDULO 1</option>
                        <option value="2">MÓDULO 2</option>
                        <option value="3">MÓDULO 3</option>
                        <option value="4">MÓDULO 4</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">TURNO</label>
                    <select name="turno" id="turnoSelect" class="form-select form-select-sm">
                        <option value="">TODOS</option>
                        <option value="1">1º TURNO</option>
                        <option value="2">2º TURNO</option>
                        <option value="3">3º TURNO</option>
                        <option value="4">4º TURNO</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">ESTADO</label>
                    <select name="estado" id="estadoSelect" class="form-select form-select-sm">
                        <option value="">TODOS</option>
                        <option value="en_curso">🟡 EN CURSO</option>
                        <option value="finalizado">🟢 FINALIZADO</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" id="btnReset" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-trash"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div id="tableContainer">
        @include('atenciones.enfermeria._table')
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('filterForm');
        const container = document.getElementById('tableContainer');

        function updateTable() {
            // Animación de carga
            container.style.opacity = '0.5';
            
            // Construir la URL con los filtros actuales
            const formData = new FormData(form);
            const params = new URLSearchParams(formData).toString();
            
            fetch(`{{ route('nurses.index') }}?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Error en la red');
                return response.text();
            })
            .then(html => {
                container.innerHTML = html;
                container.style.opacity = '1';
            })
            .catch(error => {
                console.error('Error:', error);
                container.style.opacity = '1';
            });
        }

        // Eventos
        document.getElementById('searchInput').addEventListener('input', debounce(updateTable, 500));
        document.getElementById('dateSelect').addEventListener('change', updateTable);
        document.getElementById('moduloSelect').addEventListener('change', updateTable);
        document.getElementById('turnoSelect').addEventListener('change', updateTable);
        document.getElementById('estadoSelect').addEventListener('change', updateTable);

        document.getElementById('btnReset').addEventListener('click', function() {
            form.reset();
            document.getElementById('dateSelect').value = "{{ date('Y-m-d') }}";
            updateTable();
        });

        // Función para no saturar al servidor mientras se escribe
        function debounce(func, wait) {
            let timeout;
            return function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, arguments), wait);
            };
        }
    });

</script>
@endsection
