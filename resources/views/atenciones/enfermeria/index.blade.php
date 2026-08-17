@extends('layouts.app')

@section('content')
<div class="container py-0">
    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-clipboard-pulse text-primary me-2"></i>Control de Enfermería
            </h3>
        </div>
        <div class="col-md-6 text-md-end mt-2 mt-md-0">
            <button type="button" id="btnBulkPrint" class="btn btn-danger shadow-sm">
                <i class="bi bi-printer-fill me-2"></i>Imprimir en bloque
            </button>
        </div>
    </div>

    @if($requiresModuleAssignment && $moduleAssignment)
        <div class="alert alert-primary border-0 shadow-sm d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3" role="alert">
            <div>
                <div class="fw-bold">
                    <i class="bi bi-grid-3x3-gap-fill me-2"></i>
                    Módulo asignado para hoy: MÓDULO {{ $moduleAssignment->module }}
                </div>
                <small>La lista de pacientes se filtrará automáticamente según esta selección.</small>
            </div>
            <button class="btn btn-sm btn-primary text-nowrap" type="button" data-bs-toggle="modal" data-bs-target="#moduleAssignmentModal">
                Cambiar módulo
            </button>
        </div>
    @endif

    @if($requiresModuleAssignment)
        <div class="modal fade" id="moduleAssignmentModal" data-bs-backdrop="static" data-bs-keyboard="false" data-auto-show="{{ $moduleAssignment ? 'false' : 'true' }}" tabindex="-1" aria-labelledby="moduleAssignmentModalLabel" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title fw-bold" id="moduleAssignmentModalLabel">
                                <i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Seleccione su módulo
                            </h5>
                            <p class="text-muted small mb-0 mt-2">Elija el módulo en el que trabajará hoy para mostrar los pacientes correspondientes.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('nurses.module-assignment.store') }}">
                        @csrf
                        <div class="modal-body py-4">
                            <label for="moduleAssignmentSelect" class="form-label fw-bold">Módulo de trabajo</label>
                            <select name="module" id="moduleAssignmentSelect" class="form-select" required autofocus>
                                <option value="">Elegir módulo</option>
                                @foreach(range(1, 4) as $module)
                                    <option value="{{ $module }}" @selected(optional($moduleAssignment)->module === $module)>MÓDULO {{ $module }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="bi bi-check-circle me-2"></i>Confirmar módulo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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

<div class="modal fade" id="bulkPrintModal" tabindex="-1" aria-labelledby="bulkPrintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95vw;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold" id="bulkPrintModalLabel">
                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>Vista previa de impresión en bloque
                    </h5>
                    <small class="text-muted">Se incluyen todos los registros que coinciden con los filtros, no solo la página visible.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-0 position-relative" style="height: 78vh;">
                <div id="bulkPrintLoading" class="position-absolute top-50 start-50 translate-middle text-center">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="small text-muted mt-2">Generando vista previa...</div>
                </div>
                <iframe id="bulkPrintFrame" class="w-100 h-100 border-0" title="Vista previa de fichas de enfermería"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" id="btnPrintPreview" class="btn btn-danger">
                    <i class="bi bi-printer me-2"></i>Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('filterForm');
        const container = document.getElementById('tableContainer');
        const bulkPrintFrame = document.getElementById('bulkPrintFrame');
        const bulkPrintLoading = document.getElementById('bulkPrintLoading');
        const bulkPrintModalElement = document.getElementById('bulkPrintModal');

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

        document.getElementById('btnBulkPrint').addEventListener('click', function() {
            const params = new URLSearchParams(new FormData(form));
            bulkPrintLoading.classList.remove('d-none');
            bulkPrintFrame.src = `{{ route('enfermeria.print.bulk') }}?${params.toString()}`;
            window.bootstrap.Modal.getOrCreateInstance(bulkPrintModalElement).show();
        });

        bulkPrintFrame.addEventListener('load', function() {
            bulkPrintLoading.classList.add('d-none');
        });

        document.getElementById('btnPrintPreview').addEventListener('click', function() {
            bulkPrintFrame.contentWindow?.print();
        });

        bulkPrintModalElement.addEventListener('hidden.bs.modal', function() {
            bulkPrintFrame.src = 'about:blank';
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

    @if($requiresModuleAssignment && ! $moduleAssignment)
        (function openRequiredModuleModal() {
            const modalElement = document.getElementById('moduleAssignmentModal');

            if (!modalElement || modalElement.dataset.autoShow !== 'true') {
                return;
            }

            const showModal = function() {
                if (!window.bootstrap?.Modal) {
                    return false;
                }

                window.bootstrap.Modal.getOrCreateInstance(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                }).show();

                return true;
            };

            if (showModal()) {
                return;
            }

            window.addEventListener('load', showModal, { once: true });
        })();
    @endif

</script>
@endsection
