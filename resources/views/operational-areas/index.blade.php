@extends('layouts.app')

@section('content')
<script>
    window.areasData = @json($areas);
    window.sedesData = @json($sedes);
</script>

<div class="container-fluid py-4" x-data="areaManagement">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Áreas operativas</h2>
            <p class="text-muted mb-0">Configure las áreas por sede para organizar las solicitudes al almacén central.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openModal()">
            <i class="bi bi-diagram-3 me-2"></i> Nueva área
        </button>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold">
                    <tr>
                        <th class="ps-4">ÁREA</th>
                        <th>SEDE</th>
                        <th>ESTADO</th>
                        <th class="text-end pe-4">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                <template x-for="area in areas" :key="area.id">
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold" x-text="area.name"></div>
                            <div class="small text-muted" x-text="area.code || 'Sin código'"></div>
                        </td>
                        <td x-text="area.sede?.name || 'Sin sede'"></td>
                        <td>
                            <span class="badge" :class="area.is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'" x-text="area.is_active ? 'Activa' : 'Inactiva'"></span>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary border-0" @click="openModal(area)">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        </td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="areaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" x-text="currentArea.id ? 'Editar área operativa' : 'Registrar área operativa'"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form :action="currentArea.id ? `/areas-operativas/${currentArea.id}` : '/areas-operativas'" method="POST">
                    @csrf
                    <template x-if="currentArea.id">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Sede</label>
                            <select name="sede_id" class="form-select" x-model="currentArea.sede_id" required>
                                <option value="">Seleccione...</option>
                                <template x-for="sede in sedesCatalog" :key="`sede-${sede.id}`">
                                    <option :value="String(sede.id)" x-text="sede.name"></option>
                                </template>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nombre del área</label>
                            <input type="text" name="name" class="form-control" x-model="currentArea.name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Código</label>
                            <input type="text" name="code" class="form-control" x-model="currentArea.code" maxlength="30">
                        </div>
                        <div>
                            <label class="form-label">Estado</label>
                            <select name="is_active" class="form-select" x-model="currentArea.is_active" required>
                                <option value="1">Activa</option>
                                <option value="0">Inactiva</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('areaManagement', () => ({
            areas: window.areasData || [],
            sedesCatalog: window.sedesData || [],
            currentArea: { id: null, sede_id: '', name: '', code: '', is_active: '1' },

            openModal(area = null) {
                this.currentArea = area
                    ? {
                        id: area.id,
                        sede_id: String(area.sede_id ?? ''),
                        name: area.name ?? '',
                        code: area.code ?? '',
                        is_active: area.is_active ? '1' : '0',
                    }
                    : { id: null, sede_id: '', name: '', code: '', is_active: '1' };

                const modal = window.bootstrap.Modal.getOrCreateInstance(document.getElementById('areaModal'));
                modal.show();
            },
        }));
    });
</script>
@endsection
