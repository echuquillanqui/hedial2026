<div class="card module-card shadow-sm border-0 mb-3">
    <div class="card-body bg-white py-3">
        <form action="{{ route('extra-materials.index') }}" method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="view" value="{{ $view }}">
            <div class="col-md-3">
                <label class="label-mini"><i class="bi bi-calendar3 me-1"></i>Periodo</label>
                <input type="month" name="month" class="form-control" value="{{ request('month', $month) }}">
            </div>
            <div class="col-md-6">
                <label class="label-mini"><i class="bi bi-person me-1"></i>Paciente</label>
                <select name="patient_id" class="form-select js-patient-select">
                    <option value="">-- Todos --</option>
                    @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                            {{ $patient->surname }} {{ $patient->last_name }}, {{ $patient->first_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary fw-bold w-100" type="submit">
                    <i class="bi bi-search me-1"></i> Aplicar filtros
                </button>
            </div>
        </form>
    </div>
</div>
