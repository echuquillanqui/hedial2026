@extends('layouts.app')

@section('content')
<script>
    // Pasamos los datos a Alpine
    window.usersData = @json($users);
</script>

<div class="container-fluid py-4" x-data="userManagement">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Personal del Centro</h2>
            <p class="text-muted mb-0">Gestión de especialistas y administrativos en HEMODIAL</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openModal()">
            <i class="bi bi-person-plus-fill me-2"></i> Registrar Personal
        </button>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 shadow-none" 
                       placeholder="Buscar por nombre, usuario, colegiatura..." 
                       x-model="search" @input="page = 1">
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold">
                    <tr>
                        <th class="ps-4">PERSONAL</th>
                        <th>COLEGIATURA / RNE</th>
                        <th>PROFESIÓN</th>
                        <th class="text-end pe-4">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="user in paginatedUsers" :key="user.id">
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3 text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" 
                                         :style="`background: linear-gradient(45deg, #1a2a6c, #2a4858); width: 40px; height: 40px; border-radius: 50%;`"
                                         x-text="user.name[0]">
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" x-text="user.name"></div>
                                        <div class="small text-muted" x-text="'@' + user.username"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-dark fw-bold small" x-text="user.license_number || '---'"></div>
                                <div class="extra-small text-muted" x-text="'RNE: ' + (user.specialty_number || '---')"></div>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3" x-text="user.profession"></span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-primary border-0" @click="openModal(user)">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
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

    @include('users.modals.form')
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('userManagement', () => ({
            search: '',
            users: window.usersData || [],
            page: 1,
            perPage: 10, // Cantidad de filas por página
            currentUser: {},

            normalize(text) {
                return text ? text.toString().normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase() : '';
            },

            // 1. Primero filtramos por búsqueda
            get filteredUsers() {
                const q = this.normalize(this.search);
                return this.users.filter(u => 
                    this.normalize(u.name).includes(q) || 
                    this.normalize(u.username).includes(q) ||
                    this.normalize(u.license_number).includes(q)
                );
            },

            // 2. Calculamos el total de páginas según el filtro
            get totalPages() {
                return Math.ceil(this.filteredUsers.length / this.perPage);
            },

            // 3. Cortamos el array para mostrar solo 10
            get paginatedUsers() {
                const start = (this.page - 1) * this.perPage;
                const end = start + this.perPage;
                return this.filteredUsers.slice(start, end);
            },

            openModal(user = null) {
                this.currentUser = user ? { ...user } : { id: null, name: '', username: '', email: '', profession: '', license_number: '', specialty_number: '' };
                const modal = window.bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal'));
                modal.show();
            }
        }));
    });
</script>
@endsection