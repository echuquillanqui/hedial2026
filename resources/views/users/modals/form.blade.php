<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-person-badge-fill me-2"></i>
                    <span x-text="currentUser.id ? 'Editar Datos del Personal' : 'Registrar Nuevo Miembro'"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form :action="currentUser.id ? `/users/${currentUser.id}` : '/users'" method="POST">
                @csrf
                <template x-if="currentUser.id">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="modal-body p-4 bg-light text-start">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nombre Completo</label>
                            <input type="text" name="name" x-model="currentUser.name" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nombre de Usuario (Login)</label>
                            <input type="text" name="username" x-model="currentUser.username" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Correo Electrónico</label>
                            <input type="email" name="email" x-model="currentUser.email" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Profesión / Rol</label>
                            <select name="profession" x-model="currentUser.profession" class="form-select rounded-3">
                                <option value="">Seleccionar...</option>
                                <option value="MEDICO">MEDICO</option>
                                <option value="ENFERMERA">ENFERMERA</option>
                                <option value="ADMINISTRATIVO">ADMINISTRATIVO</option>
                                <option value="SUPERADMIN">SUPERADMIN</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Colegiatura (CMP/CEP)</label>
                            <input type="text" name="license_number" x-model="currentUser.license_number" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">RNE (Especialidad)</label>
                            <input type="text" name="specialty_number" x-model="currentUser.specialty_number" class="form-control rounded-3">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-primary">Contraseña</label>
                            <input type="password" name="password" class="form-control rounded-3" :required="!currentUser.id">
                            <small class="text-muted d-block mt-1" x-show="currentUser.id">
                                <i class="bi bi-info-circle me-1"></i>Deje vacío para mantener la contraseña actual.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-save me-1"></i> Guardar Información
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>