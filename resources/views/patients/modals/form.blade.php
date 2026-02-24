<div class="modal fade" id="patientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-person-vcard-fill me-2"></i>
                    <span x-text="currentPatient.id ? 'Actualizar Datos' : 'Registrar Paciente'"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form :action="currentPatient.id ? `/patients/${currentPatient.id}` : '/patients'" method="POST">
                @csrf
                <template x-if="currentPatient.id"><input type="hidden" name="_method" value="PUT"></template>

                <div class="modal-body p-4 bg-light">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">DNI</label>
                            <input type="text" name="dni" x-model="currentPatient.dni" class="form-control rounded-3 border-0 shadow-sm" maxlength="8">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Historia Clínica</label>
                            <input type="text" name="medical_history_number" x-model="currentPatient.medical_history_number" class="form-control rounded-3 border-0 shadow-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Cód. Afiliación</label>
                            <input type="text" name="affiliation_code" x-model="currentPatient.affiliation_code" class="form-control rounded-3 border-0 shadow-sm">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Apellido Paterno</label>
                            <input type="text" name="surname" x-model="currentPatient.surname" class="form-control rounded-3 border-0 shadow-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Apellido Materno</label>
                            <input type="text" name="last_name" x-model="currentPatient.last_name" class="form-control rounded-3 border-0 shadow-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nombres</label>
                            <input type="text" name="first_name" x-model="currentPatient.first_name" class="form-control rounded-3 border-0 shadow-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Otros Nombres</label>
                            <input type="text" name="other_names" x-model="currentPatient.other_names" class="form-control rounded-3 border-0 shadow-sm">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Fecha de Nacimiento</label>
                            <input type="date" name="birth_date" x-model="currentPatient.birth_date" 
                                   @change="currentPatient.age = getRealAge(currentPatient.birth_date)"
                                   class="form-control rounded-3 border-0 shadow-sm">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Edad</label>
                            <input type="number" name="age" x-model="currentPatient.age" 
                                   class="form-control rounded-3 border-0 shadow-sm bg-white" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Género</label>
                            <select name="gender" x-model="currentPatient.gender" class="form-select border-0 shadow-sm">
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Tipo de Seguro</label>
                            <select name="insurance_type" x-model="currentPatient.insurance_type" class="form-select border-0 shadow-sm">
                                <option value="ESSALUD">ESSALUD</option>
                                <option value="SALUDPOL">SALUDPOL</option>
                                <option value="SIS">SIS</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Distrito</label>
                            <input type="text" name="district" x-model="currentPatient.district" class="form-control rounded-3 border-0 shadow-sm">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Dirección</label>
                            <input type="text" name="address" x-model="currentPatient.address" class="form-control rounded-3 border-0 shadow-sm">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Seecuencia</label>
                            <select name="secuencia" x-model="currentPatient.secuencia" class="form-select border-0 shadow-sm">
                                <option value="L-M-V">L-M-V</option>
                                <option value="M-J-S">M-J-S</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Turno</label>
                            <select name="turno" x-model="currentPatient.turno" class="form-select border-0 shadow-sm">
                                <option value="1">Turno 1</option>
                                <option value="2">Turno 2</option>
                                <option value="3">Turno 3</option>
                                <option value="4">Turno 4</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Modulo</label>
                            <select name="modulo" x-model="currentPatient.modulo" class="form-select border-0 shadow-sm">
                                <option value="1">Modulo 1</option>
                                <option value="2">Modulo 2</option>
                                <option value="3">Modulo 3</option>
                                <option value="4">Modulo 4</option>
                            </select>
                        </div>
                        
                    </div>
                </div>

                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>