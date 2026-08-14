@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold text-primary text-uppercase mb-1"><i class="bi bi-clipboard2-pulse me-2"></i>Consultas nefrológicas</h4>
            <small class="text-muted">Genera la orden y la FUA de consulta, sin crear hojas de hemodiálisis ni registros de laboratorio.</small>
        </div>
        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary fw-bold"><i class="bi bi-arrow-left me-1"></i> VOLVER</a>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body bg-light">
            <form method="GET" action="{{ route('orders.nephrology.create') }}" class="row g-2 align-items-end">
                <div class="col-md-10">
                    <label class="form-label small fw-bold text-primary text-uppercase">Buscar por DNI, H.C. o nombre</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control border-primary" placeholder="Ingrese el criterio de búsqueda">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100 fw-bold"><i class="bi bi-search me-1"></i> BUSCAR</button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('orders.nephrology.store') }}">
        @csrf
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-bold text-uppercase">Seleccionar pacientes</span>
                <div class="d-flex align-items-center gap-2">
                    <label for="fecha_orden" class="small fw-bold mb-0">FECHA:</label>
                    <input id="fecha_orden" type="date" name="fecha_orden" value="{{ old('fecha_orden', date('Y-m-d')) }}" class="form-control form-control-sm" required>
                    <button type="submit" class="btn btn-light btn-sm text-primary fw-bold" onclick="return confirm('¿Generar las consultas nefrológicas seleccionadas?')">
                        <i class="bi bi-file-earmark-plus me-1"></i> GENERAR
                    </button>
                </div>
            </div>
            @if($errors->any())
                <div class="alert alert-danger rounded-0 mb-0">{{ $errors->first() }}</div>
            @endif
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th class="text-center">SEL.</th><th>PACIENTE</th><th>DNI</th><th>H.C.</th><th>TURNO</th></tr></thead>
                    <tbody>
                        @forelse($patients as $patient)
                            <tr>
                                <td class="text-center"><input type="checkbox" name="patient_ids[]" value="{{ $patient->id }}" class="form-check-input border-primary"></td>
                                <td class="fw-bold text-uppercase small">{{ $patient->surname }} {{ $patient->last_name }}, {{ $patient->first_name }} {{ $patient->other_names }}</td>
                                <td>{{ $patient->dni ?? '-' }}</td>
                                <td>{{ $patient->medical_history_number ?? '-' }}</td>
                                <td>{{ $patient->turno ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">No se encontraron pacientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $patients->links() }}</div>
        </div>
    </form>
</div>
@endsection
