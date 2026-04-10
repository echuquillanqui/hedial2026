@extends('layouts.app')

@section('content')
<script>
    window.sedesData = @json($sedes);
</script>

<div class="container-fluid py-4" x-data="sedeManagement()">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Sedes</h2>
            <p class="text-muted mb-0">Cree y actualice sedes con estado activo o inactivo.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openModal()">
            <i class="bi bi-building-add me-2"></i> Nueva sede
        </button>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 shadow-none"
                       placeholder="Buscar por nombre o código"
                       x-model="search" @input="page = 1">
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold">
                    <tr>
                        <th class="ps-4">SEDE</th>
                        <th>CÓDIGO</th>
                        <th>ESTADO</th>
                        <th>PRINCIPAL</th>
                        <th class="text-end pe-4">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="sede in paginatedSedes" :key="sede.id">
                        <tr>
                            <td class="ps-4 fw-semibold" x-text="sede.name"></td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary" x-text="sede.code || '---'"></span>
                            </td>
                            <td>
                                <span class="badge rounded-pill"
                                      :class="sede.is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'"
                                      x-text="sede.is_active ? 'Activo' : 'Inactivo'"></span>
                            </td>
                            <td>
                                <span class="badge rounded-pill"
                                      :class="sede.is_principal ? 'bg-primary-subtle text-primary' : 'bg-light text-muted'"
                                      x-text="sede.is_principal ? 'Principal' : 'Secundaria'"></span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-primary border-0" @click="openModal(sede)">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredSedes.length === 0">
                        <td colspan="5" class="text-center text-muted py-4">No hay sedes para mostrar.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white border-0 py-3" x-show="totalPages > 1">
            <div class="d-flex justify-content-center align-items-center gap-3">
                <button class="btn btn-sm btn-info rounded-pill px-3" @click="page--" :disabled="page === 1">Anterior</button>
                <span class="text-muted small">Página <strong x-text="page"></strong> de <strong x-text="totalPages"></strong></span>
                <button class="btn btn-sm btn-info rounded-pill px-3" @click="page++" :disabled="page === totalPages">Siguiente</button>
            </div>
        </div>
    </div>

    @include('sedes.modals.form')
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sedeManagement', () => ({
            sedes: window.sedesData || [],
            search: '',
            page: 1,
            perPage: 10,
            currentSede: { id: null, name: '', code: '', is_active: 1, is_principal: 0 },

            normalize(text) {
                return text ? text.toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase() : '';
            },

            get filteredSedes() {
                const q = this.normalize(this.search);

                return this.sedes.filter((sede) =>
                    this.normalize(sede.name).includes(q) || this.normalize(sede.code).includes(q)
                );
            },

            get totalPages() {
                return Math.max(1, Math.ceil(this.filteredSedes.length / this.perPage));
            },

            get paginatedSedes() {
                const start = (this.page - 1) * this.perPage;
                const end = start + this.perPage;

                return this.filteredSedes.slice(start, end);
            },

            openModal(sede = null) {
                this.currentSede = sede
                    ? {
                        id: sede.id,
                        name: sede.name || '',
                        code: sede.code || '',
                        is_active: sede.is_active ? 1 : 0,
                        is_principal: sede.is_principal ? 1 : 0,
                    }
                    : { id: null, name: '', code: '', is_active: 1, is_principal: 0 };

                const modal = window.bootstrap.Modal.getOrCreateInstance(document.getElementById('sedeModal'));
                modal.show();
            },
        }));
    });
</script>
@endsection
