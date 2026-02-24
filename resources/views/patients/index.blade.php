@extends('layouts.app')

@section('content')
<script>
    // Inyectamos los datos para Alpine.js
    window.patientsData = @json($patients);
</script>

<div class="container-fluid py-4" x-data="patientManagement">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Gestión de Pacientes</h2>
            <p class="text-muted mb-0">Base de datos centralizada de HEMODIAL</p>
        </div>
        <div class="d-flex gap-2">
            <a href="" class="btn btn-outline-success rounded-pill px-4 shadow-sm">
                <i class="bi bi-file-earmark-excel-fill me-2"></i> Exportar Excel
            </a>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" @click="openModal()">
                <i class="bi bi-person-plus-fill me-2"></i> Nuevo Paciente
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4 d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 shadow-none" 
                       placeholder="Buscar por DNI, Apellidos o Historia Clínica..." 
                       x-model="search" @input="page = 1">
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold">
                    <tr>
                        <th class="ps-4">PACIENTE</th>
                        <th>IDENTIFICACIÓN</th>
                        <th>SEGURO / RÉGIMEN</th>
                        <th>EDAD ACTUAL</th>
                        <th class="text-end pe-4">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="p in paginatedPatients" :key="p.id">
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3 text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" 
                                         :style="`background: ${p.gender === 'F' ? 'linear-gradient(45deg, #f093fb, #f5576c)' : 'linear-gradient(45deg, #4facfe, #00f2fe)'}; width: 42px; height: 42px; border-radius: 50%;`"
                                         x-text="p.surname[0]">
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" x-text="`${p.surname} ${p.last_name}, ${p.first_name}`"></div>
                                        <div class="small text-muted" x-text="p.district || 'Sin distrito'"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-dark fw-bold small" x-text="'DNI: ' + (p.dni || '---')"></div>
                                <div class="extra-small text-muted" x-text="'H.C.: ' + (p.medical_history_number || 'S/N')"></div>
                            </td>
                            <td>
                                <span :class="{
                                    'badge rounded-pill px-3': true,
                                    'bg-success bg-opacity-10 text-success': p.insurance_type === 'SIS',
                                    'bg-primary bg-opacity-10 text-primary': p.insurance_type === 'ESSALUD',
                                    'bg-info bg-opacity-10 text-info': p.insurance_type === 'SALUDPOL',
                                    'bg-secondary bg-opacity-10 text-secondary': !p.insurance_type
                                }" x-text="p.insurance_type || 'PARTICULAR'"></span>
                                <div class="extra-small text-muted mt-1" x-text="p.insurance_regime"></div>
                            </td>
                            <td>
                                <div class="small text-dark fw-bold" x-text="getRealAge(p.birth_date) + ' años'"></div>
                                <div class="extra-small text-muted" x-text="p.birth_date || 'No registra fecha'"></div>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-outline-primary border-0" @click="openModal(p)">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <form :action="`/patients/${p.id}`" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0" 
                                            onclick="return confirm('¿Eliminar paciente? Esta acción fallará si tiene referencias asociadas.')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white border-top-0 py-3" x-show="totalPages > 1">
            <div class="d-flex justify-content-center align-items-center gap-3">
                <button class="btn btn-sm btn-light rounded-pill px-3" @click="page--" :disabled="page === 1">Anterior</button>
                <span class="text-muted small">Página <strong x-text="page"></strong> de <strong x-text="totalPages"></strong></span>
                <button class="btn btn-sm btn-light rounded-pill px-3" @click="page++" :disabled="page === totalPages">Siguiente</button>
            </div>
        </div>
    </div>

    @include('patients.modals.form')
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('patientManagement', () => ({
            search: '',
            patients: window.patientsData || [],
            page: 1,
            perPage: 10,
            currentPatient: {},

            // Normalización para búsqueda
            normalize(text) {
                return text ? text.toString().normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase() : '';
            },

            // Cálculo de edad dinámico (reutilizable)
            getRealAge(birthDate) {
                if (!birthDate) return '--';
                const birth = new Date(birthDate);
                const today = new Date();
                let age = today.getFullYear() - birth.getFullYear();
                const monthDiff = today.getMonth() - birth.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                    age--;
                }
                return age >= 0 ? age : 0;
            },

            get filteredPatients() {
                const q = this.normalize(this.search);
                return this.patients.filter(p => 
                    this.normalize(`${p.first_name} ${p.surname} ${p.last_name}`).includes(q) || 
                    (p.dni && p.dni.includes(q)) ||
                    (p.medical_history_number && p.medical_history_number.includes(q))
                );
            },

            get totalPages() { return Math.ceil(this.filteredPatients.length / this.perPage); },

            get paginatedPatients() {
                const start = (this.page - 1) * this.perPage;
                return this.filteredPatients.slice(start, start + this.perPage);
            },

            openModal(patient = null) {
                if (patient) {
                    // Clonamos el objeto para evitar edición en tiempo real en la tabla
                    this.currentPatient = { ...patient };
                    
                    // IMPORTANTE: Forzamos el cálculo al abrir si existe fecha de nacimiento
                    if (this.currentPatient.birth_date) {
                        this.currentPatient.age = this.getRealAge(this.currentPatient.birth_date);
                    }
                } else {
                    // Reset para nuevo paciente
                    this.currentPatient = { 
                        id: null, dni: '', affiliation_code: '', medical_history_number: '', 
                        first_name: '', other_names: '', surname: '', last_name: '', 
                        is_insured: true, insurance_type: 'ESSALUD', insurance_regime: 'SUBSIDIADO', 
                        gender: 'M', birth_date: '', age: '', address: '', district: '', department: '', secuencia:'L-M-V', turno: "1", modulo: "1"
                    };
                }
                
                const modal = window.bootstrap.Modal.getOrCreateInstance(document.getElementById('patientModal'));
                modal.show();
            }
        }));
    });
</script>
@endsection