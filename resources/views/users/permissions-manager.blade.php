@extends('layouts.app')

@section('content')
<script>
    window.permissionManagerUsers = @json($permissionManagerUsers);
    window.permissionCatalog = @json($permissionCatalog);
    window.rolesCatalog = @json($rolesCatalog);
</script>

<div class="container-fluid py-4" x-data="permissionManager">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Gestión de Roles y Permisos</h2>
            <p class="text-muted mb-0">Vista separada para administrar roles, permisos y asignaciones individuales/masivas.</p>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-arrow-left me-2"></i>Volver a Personal
        </a>
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
            <h6 class="fw-bold mb-0">Asignación masiva de roles y permisos</h6>
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

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <label class="small fw-bold text-muted mb-2">Buscar usuario</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 shadow-none"
                       placeholder="Nombre, usuario o correo..."
                       x-model="search">
            </div>
            <div class="small text-muted mt-2">
                Mostrando <span x-text="filteredUsers.length"></span> usuario(s)
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">Usuarios</h6>
                </div>
                <div class="list-group list-group-flush" style="max-height: 540px; overflow:auto;">
                    <template x-for="user in filteredUsers" :key="user.id">
                        <button type="button" class="list-group-item list-group-item-action"
                                :class="selectedUser && selectedUser.id === user.id ? 'active' : ''"
                                @click="selectUser(user)">
                            <div class="fw-bold" x-text="user.name"></div>
                            <div class="small" x-text="'@' + user.username"></div>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8" x-show="selectedUser">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0">Asignación individual de permisos</h6>
                        <small class="text-muted" x-text="selectedUser ? selectedUser.name + ' (@' + selectedUser.username + ')' : ''"></small>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('users.permissions-manager.update-user') }}">
                        @csrf
                        <input type="hidden" name="user_id" :value="selectedUser ? selectedUser.id : ''">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <label class="small fw-bold text-muted mb-0">Permisos actuales</label>
                                        <button type="button" class="btn btn-link btn-sm p-0" @click="toggleAllCurrent(false)">Quitar todos</button>
                                    </div>
                                    <div style="max-height: 280px; overflow:auto;" class="pe-1">
                                        <template x-for="permission in currentPermissions" :key="'current-' + permission">
                                            <label class="form-check d-flex align-items-center mb-1">
                                                <input class="form-check-input me-2" type="checkbox" :value="permission"
                                                       x-model="selectedPermissions">
                                                <span class="small" x-text="permission"></span>
                                            </label>
                                        </template>
                                        <div class="small text-muted" x-show="!currentPermissions.length">Sin permisos directos.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="d-flex justify-content-between mb-2">
                                        <label class="small fw-bold text-muted mb-0">Permisos disponibles para asignar</label>
                                        <button type="button" class="btn btn-link btn-sm p-0" @click="toggleAllAssignable(true)">Marcar todos</button>
                                    </div>
                                    <div style="max-height: 280px; overflow:auto;" class="pe-1">
                                        <template x-for="permission in assignablePermissions" :key="'assignable-' + permission">
                                            <label class="form-check d-flex align-items-center mb-1">
                                                <input class="form-check-input me-2" type="checkbox" :value="permission"
                                                       x-model="selectedPermissions">
                                                <span class="small" x-text="permission"></span>
                                            </label>
                                        </template>
                                        <div class="small text-muted" x-show="!assignablePermissions.length">No hay permisos pendientes por asignar.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <template x-for="permission in selectedPermissions" :key="'hidden-' + permission">
                            <input type="hidden" name="permissions[]" :value="permission">
                        </template>

                        <div class="text-end mt-3">
                            <button class="btn btn-primary rounded-pill px-4" type="submit">
                                <i class="bi bi-save me-1"></i>Guardar permisos del usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="fw-bold mb-0">Asignación masiva por checks</h6>
                    <small class="text-muted">Selecciona usuarios y permisos para aplicar cambios en bloque.</small>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('users.permissions-manager.bulk-update') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted">Modo</label>
                                <select class="form-select" x-model="bulkMode" name="mode" required>
                                    <option value="add">Agregar permisos</option>
                                    <option value="remove">Quitar permisos</option>
                                    <option value="replace">Reemplazar permisos</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="small fw-bold text-muted">Usuarios seleccionados</label>
                                <div class="border rounded-3 p-2" style="max-height: 140px; overflow:auto;">
                                    <div class="d-flex justify-content-end mb-1">
                                        <button type="button" class="btn btn-link btn-sm p-0" @click="toggleAllUsers(true)">Marcar todos</button>
                                    </div>
                                    <template x-for="user in filteredUsers" :key="'bulk-user-' + user.id">
                                        <label class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox" :value="user.id" x-model="bulkUserIds">
                                            <span class="form-check-label small" x-text="user.name + ' (@' + user.username + ')' "></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="small fw-bold text-muted">Permisos a aplicar</label>
                            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-2 border rounded-3 p-2" style="max-height: 220px; overflow:auto;">
                                <template x-for="permission in permissionsCatalog" :key="'bulk-perm-' + permission">
                                    <label class="form-check mb-1 col">
                                        <input class="form-check-input" type="checkbox" :value="permission" x-model="bulkPermissions">
                                        <span class="form-check-label small" x-text="permission"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <template x-for="userId in bulkUserIds" :key="'bulk-user-hidden-' + userId">
                            <input type="hidden" name="user_ids[]" :value="userId">
                        </template>
                        <template x-for="permission in bulkPermissions" :key="'bulk-perm-hidden-' + permission">
                            <input type="hidden" name="permissions[]" :value="permission">
                        </template>

                        <div class="text-end mt-3">
                            <button class="btn btn-dark rounded-pill px-4" type="submit">
                                <i class="bi bi-people-fill me-1"></i>Aplicar cambios masivos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('permissionManager', () => ({
            search: '',
            users: window.permissionManagerUsers || [],
            permissionsCatalog: window.permissionCatalog || [],
            rolesCatalog: window.rolesCatalog || [],
            selectedUser: null,
            selectedPermissions: [],
            bulkMode: 'add',
            bulkUserIds: [],
            bulkPermissions: [],

            init() {
                if (this.users.length) {
                    this.selectUser(this.users[0]);
                }
            },

            normalize(text) {
                return text ? text.toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase() : '';
            },

            get filteredUsers() {
                const q = this.normalize(this.search);
                return this.users.filter((user) => (
                    this.normalize(user.name).includes(q)
                    || this.normalize(user.username).includes(q)
                    || this.normalize(user.email).includes(q)
                ));
            },

            get currentPermissions() {
                return this.selectedUser ? (this.selectedUser.permissions || []) : [];
            },

            get assignablePermissions() {
                const current = new Set(this.currentPermissions);
                return this.permissionsCatalog.filter((permission) => !current.has(permission));
            },

            selectUser(user) {
                this.selectedUser = user;
                this.selectedPermissions = [...(user.permissions || [])];
            },

            toggleAllCurrent(mark) {
                if (!mark) {
                    this.selectedPermissions = this.selectedPermissions.filter((permission) => !this.currentPermissions.includes(permission));
                }
            },

            toggleAllAssignable(mark) {
                if (!mark) {
                    return;
                }

                const next = new Set(this.selectedPermissions);
                this.assignablePermissions.forEach((permission) => next.add(permission));
                this.selectedPermissions = Array.from(next);
            },

            toggleAllUsers(mark) {
                if (mark) {
                    this.bulkUserIds = this.filteredUsers.map((user) => user.id);
                    return;
                }
                this.bulkUserIds = [];
            },
        }));
    });
</script>
@endsection
