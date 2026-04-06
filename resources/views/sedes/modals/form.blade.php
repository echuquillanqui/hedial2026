<div class="modal fade" id="sedeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-building me-2"></i>
                    <span x-text="currentSede.id ? 'Editar sede' : 'Registrar sede'"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form :action="currentSede.id ? `/sedes/${currentSede.id}` : '/sedes'" method="POST">
                @csrf
                <template x-if="currentSede.id">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="modal-body p-4 bg-light text-start">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Nombre de sede</label>
                            <input type="text" name="name" x-model="currentSede.name" class="form-control rounded-3" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Código</label>
                            <input type="text" name="code" x-model="currentSede.code" class="form-control rounded-3" maxlength="30">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Estado</label>
                            <select name="is_active" x-model="currentSede.is_active" class="form-select rounded-3" required>
                                <option :value="1">Activo</option>
                                <option :value="0">Inactivo</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Sede principal</label>
                            <select name="is_principal" x-model="currentSede.is_principal" class="form-select rounded-3" required>
                                <option :value="1">Sí, principal</option>
                                <option :value="0">No</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-save me-1"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
