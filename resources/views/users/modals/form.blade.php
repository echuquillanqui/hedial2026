<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-person-badge-fill me-2"></i>
                    <span x-text="currentUser.id ? 'Editar Datos del Personal' : 'Registrar Nuevo Miembro'"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form :action="currentUser.id ? `/users/${currentUser.id}` : '/users'" method="POST" enctype="multipart/form-data">
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
                            <label class="form-label small fw-bold">DNI</label>
                            <input type="text" name="dni" x-model="currentUser.dni" class="form-control rounded-3" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" placeholder="8 dígitos">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Profesión / Rol Interno</label>
                            <select name="profession" x-model="currentUser.profession" class="form-select rounded-3">
                                <option value="">Seleccionar...</option>
                                <option value="MEDICO">MEDICO</option>
                                <option value="ENFERMERA">ENFERMERA</option>
                                <option value="NUTRICIONISTA">NUTRICIONISTA</option>
                                <option value="PSICOLOGO">PSICOLOGO</option>
                                <option value="TRABAJADOR SOCIAL">TRABAJADOR SOCIAL</option>
                                <option value="ADMINISTRATIVO">ADMINISTRATIVO</option>
                                <option value="LABORATORIO">LABORATORIO</option>
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
                        <div class="col-12" x-show="currentUser.profession === 'LABORATORIO' || (currentUser.roles_selected || []).includes('laboratorio')" x-cloak>
                            <div class="card border-success-subtle bg-success bg-opacity-10">
                                <div class="card-body row g-3 align-items-center">
                                    <div class="col-md-8">
                                        <label for="user_digital_seal" class="form-label small fw-bold text-success">
                                            <i class="bi bi-patch-check me-1"></i>Sello o firma digital de laboratorio
                                        </label>
                                        <input id="user_digital_seal" type="file" name="digital_seal" class="form-control" accept="image/png,image/jpeg,image/webp">
                                        <div class="form-text">Imagen PNG, JPG o WEBP, máximo 2 MB. Se imprimirá en los resultados validados por este usuario.</div>
                                    </div>
                                    <div class="col-md-4 text-center" x-show="currentUser.digital_seal_path">
                                        <img :src="`/storage/${currentUser.digital_seal_path}`" alt="Sello digital actual" class="img-fluid bg-white border rounded p-2" style="max-height:110px">
                                        <div class="form-check text-start mt-2">
                                            <input id="remove_digital_seal" class="form-check-input" type="checkbox" name="remove_digital_seal" value="1">
                                            <label class="form-check-label small" for="remove_digital_seal">Eliminar el sello actual</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-primary">Roles</label>
                            <select name="roles[]" class="form-select rounded-3" multiple size="6" x-model="currentUser.roles_selected">
                                <template x-for="roleName in rolesCatalog" :key="`role-${roleName}`">
                                    <option :value="roleName" x-text="roleName"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-success">Permisos directos</label>
                            <select name="permissions[]" class="form-select rounded-3" multiple size="6" x-model="currentUser.permissions_selected">
                                <template x-for="permissionName in permissionsCatalog" :key="`permission-${permissionName}`">
                                    <option :value="permissionName" x-text="permissionName"></option>
                                </template>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-warning">Sedes asignadas</label>
                            <select name="sedes[]" class="form-select rounded-3" multiple size="6" x-model="currentUser.sedes_selected" @change="syncOperationalAreasWithSedes()">
                                <template x-for="sede in sedesCatalog" :key="`sede-${sede.id}`">
                                    <option :value="String(sede.id)" x-text="sede.name"></option>
                                </template>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-info">Áreas operativas asignadas</label>
                            <select name="operational_areas[]" class="form-select rounded-3" multiple size="6" x-model="currentUser.operational_areas_selected">
                                <template x-for="area in filteredOperationalAreas" :key="`area-${area.id}`">
                                    <option :value="String(area.id)" x-text="`${area.sede?.name || 'Sin sede'} - ${area.name}`"></option>
                                </template>
                            </select>
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
