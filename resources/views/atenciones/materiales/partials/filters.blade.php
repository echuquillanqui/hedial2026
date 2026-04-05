<div class="card module-card shadow-sm border-0 mb-3">
    <div class="card-body bg-light py-3">
        <form action="{{ route('extra-materials.index') }}" method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="view" value="{{ $view }}">
            <div class="col-md-4">
                <label class="label-mini">Mes</label>
                <input type="month" name="month" class="form-control form-control-sm" value="{{ request('month', $month) }}">
            </div>
            <div class="col-md-5">
                <label class="label-mini">Paciente</label>
                <select name="patient_id" class="form-select form-select-sm js-patient-select">
                    <option value="">-- Todos --</option>
                    @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                            {{ $patient->surname }} {{ $patient->last_name }}, {{ $patient->first_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-sm btn-outline-primary fw-bold w-100" type="submit">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>
