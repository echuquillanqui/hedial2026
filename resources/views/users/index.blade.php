@extends('layouts.app')

@section('content')
<script>
    window.usersData = @json($users);
    window.rolesData = @json($roles->pluck('name'));
    window.permissionsData = @json($permissions->pluck('name'));
</script>

<div class="container-fluid py-4" x-data="userManagement">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Personal del Centro</h2>
            <p class="text-muted mb-0">Gestión de usuarios, roles y permisos en HEMODIAL.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('users.permissions-manager') }}" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm">
                <i class="bi bi-sliders me-2"></i>Gestor de permisos
            </a>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openModal()">
                <i class="bi bi-person-plus-fill me-2"></i> Registrar Personal
            </button>
        </div>
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

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">Crear Rol</h6>
                    <small class="text-muted">Asigne permisos al rol al momento de crearlo.</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('users.roles.store') }}" method="POST" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <input type="text" name="name" class="form-control" placeholder="Ej: referrals.manager" required>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted">Permisos del rol</label>
                            <select name="permissions[]" class="form-select" multiple size="6">
                                @foreach($permissions as $permission)
                                    <option value="{{ $permission->name }}">{{ $permission->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-outline-primary btn-sm rounded-pill px-3" type="submit">
                                <i class="bi bi-shield-plus me-1"></i>Crear rol
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">Crear Permiso</h6>
                    <small class="text-muted">Formato recomendado: modulo.accion (ej: referrals.view).</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('users.permissions.store') }}" method="POST" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <input type="text" name="name" class="form-control" placeholder="Ej: users.assign.massive" required>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-outline-success btn-sm rounded-pill px-3" type="submit">
                                <i class="bi bi-key-fill me-1"></i>Crear permiso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pt-3">
            <h6 class="fw-bold mb-0">Asignación masiva</h6>
            <small class="text-muted">Seleccione usuarios y aplique roles/permisos en bloque.</small>
        </div>
        <div class="card-body">
            <form action="{{ route('users.bulk-permissions') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-12">
                    <label class="small fw-bold text-muted">Usuarios</label>
                    <div class="border rounded-3 p-2" style="max-height:160px; overflow:auto;">
                        @foreach($users as $user)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="user_ids[]" value="{{ $user->id }}" id="user_bulk_{{ $user->id }}">
                                <label class="form-check-label" for="user_bulk_{{ $user->id }}">
                                    {{ $user->name }} <span class="text-muted">(@{{ $user->username }})</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">Modo</label>
                    <select name="mode" class="form-select" required>
                        <option value="add">Agregar sin quitar</option>
                        <option value="replace">Reemplazar todo</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">Roles</label>
                    <select name="roles[]" class="form-select" multiple size="4">
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-muted">Permisos directos</label>
                    <select name="permissions[]" class="form-select" multiple size="4">
                        @foreach($permissions as $permission)
                            <option value="{{ $permission->name }}">{{ $permission->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 text-end">
                    <button class="btn btn-dark rounded-pill px-4" type="submit">
                        <i class="bi bi-people-fill me-1"></i>Aplicar masivamente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold">
                    <tr>
                        <th class="ps-4">PERSONAL</th>
                        <th>COLEGIATURA / RNE</th>
                        <th>ROLES Y PERMISOS</th>
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
                                <div class="mb-1">
                                    <template x-for="role in (user.roles || [])" :key="`r-${user.id}-${role.name}`">
                                        <span class="badge rounded-pill bg-primary-subtle text-primary me-1" x-text="role.name"></span>
                                    </template>
                                </div>
                                <div>
                                    <template x-for="permission in (user.permissions || []).slice(0, 3)" :key="`p-${user.id}-${permission.name}`">
                                        <span class="badge rounded-pill bg-success-subtle text-success me-1" x-text="permission.name"></span>
                                    </template>
                                    <span class="small text-muted" x-show="(user.permissions || []).length > 3" x-text="`+${(user.permissions || []).length - 3} más`"></span>
                                </div>
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
            rolesCatalog: window.rolesData || [],
            permissionsCatalog: window.permissionsData || [],
            page: 1,
            perPage: 10,
            currentUser: {},

            normalize(text) {
                return text ? text.toString().normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase() : '';
            },

            get filteredUsers() {
                const q = this.normalize(this.search);
                return this.users.filter(u =>
                    this.normalize(u.name).includes(q) ||
                    this.normalize(u.username).includes(q) ||
                    this.normalize(u.license_number).includes(q)
                );
            },

            get totalPages() {
                return Math.max(1, Math.ceil(this.filteredUsers.length / this.perPage));
            },

            get paginatedUsers() {
                const start = (this.page - 1) * this.perPage;
                const end = start + this.perPage;
                return this.filteredUsers.slice(start, end);
            },

            openModal(user = null) {
                this.currentUser = user
                    ? {
                        ...user,
                        roles_selected: (user.roles || []).map(r => r.name),
                        permissions_selected: (user.permissions || []).map(p => p.name),
                    }
                    : { id: null, name: '', username: '', email: '', profession: '', license_number: '', specialty_number: '', roles_selected: [], permissions_selected: [] };

                const modal = window.bootstrap.Modal.getOrCreateInstance(document.getElementById('userModal'));
                modal.show();
            },
        }));
    });
</script>
@endsection
