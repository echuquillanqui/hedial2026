@extends('layouts.app')

@section('content')
<div class="container-fluid attendance-report">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <span class="text-uppercase text-primary fw-bold small">Reportes</span>
            <h2 class="mb-1"><i class="bi bi-calendar2-check me-2"></i>Control de atenciones de hemodiálisis</h2>
            <p class="text-muted mb-0">Asistencia mensual de pacientes, organizada por módulo y turno.</p>
        </div>
        <a class="btn btn-success rounded-pill px-4" href="{{ route('reports.monthly-attendances.export', request()->query()) }}">
            <i class="bi bi-file-earmark-excel me-2"></i>Descargar Excel
        </a>
    </div>

    <form method="GET" class="card card-body shadow-sm mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3 col-xl-2"><label class="form-label fw-semibold">Mes</label><input type="month" class="form-control" name="month" value="{{ request('month', $month->format('Y-m')) }}"></div>
            <div class="col-md-4 col-xl-3"><label class="form-label fw-semibold">Paciente o DNI</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Buscar paciente..."></div>
            <div class="col-md-2"><label class="form-label fw-semibold">Secuencia</label><select class="form-select" name="secuencia"><option value="">Todas</option>@foreach(['L-M-V', 'M-J-S'] as $value)<option value="{{ $value }}" @selected(request('secuencia') === $value)>{{ $value }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label fw-semibold">Módulo</label><select class="form-select" name="modulo"><option value="">Todos</option>@foreach(\App\Models\Patient::MODULES as $value)<option value="{{ $value }}" @selected((string) request('modulo') === $value)>{{ $value }}</option>@endforeach</select></div>
            <div class="col-md-2 col-xl-1"><label class="form-label fw-semibold">Turno</label><select class="form-select" name="turno"><option value="">Todos</option>@foreach(range(1, 4) as $value)<option value="{{ $value }}" @selected((string) request('turno') === (string) $value)>{{ $value }}</option>@endforeach</select></div>
            <div class="col-auto"><button class="btn btn-primary px-4"><i class="bi bi-funnel me-2"></i>Filtrar</button></div>
            <div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('reports.monthly-attendances.index') }}">Limpiar</a></div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-muted text-capitalize"><i class="bi bi-calendar-range me-1"></i>{{ $month->translatedFormat('F Y') }}</span>
        <div><span class="me-3"><span class="text-success">✓</span> Asistencia <span class="ms-2 text-danger">✗</span> Falta</span><span class="badge rounded-pill bg-primary px-3 py-2">{{ $total }} pacientes</span></div>
    </div>

    <div class="card shadow-sm overflow-hidden">
        <div class="table-responsive attendance-grid">
            <table class="table table-sm table-bordered align-middle text-center mb-0">
                <thead class="table-dark"><tr><th class="sticky-patient text-start">Paciente</th><th>DNI</th><th>Módulo</th><th>Turno</th>@foreach($days as $day)<th title="{{ $day->translatedFormat('l d/m/Y') }}">{{ $day->format('d') }}<small class="d-block fw-normal">{{ mb_substr($day->translatedFormat('D'), 0, 2) }}</small></th>@endforeach</tr></thead>
                <tbody>
                @forelse($patients as $patient)
                    <tr><td class="sticky-patient text-start fw-semibold text-nowrap">{{ $patient->full_name }}</td><td>{{ $patient->dni ?: '—' }}</td><td>{{ $patient->modulo }}</td><td>{{ $patient->turno }}</td>
                    @foreach($days as $day)@php($attended = $patient->attendance_days->has($day->day))<td class="attendance-cell {{ $attended ? 'table-success' : 'table-danger' }}" title="{{ $attended ? 'Asistencia' : 'Falta' }}: {{ $day->format('d/m/Y') }}"><span class="fw-bold {{ $attended ? 'text-success' : 'text-danger' }}">{{ $attended ? '✓' : '✗' }}</span><span class="visually-hidden">{{ $attended ? 'Asistencia' : 'Falta' }}</span></td>@endforeach</tr>
                @empty<tr><td colspan="{{ 4 + $days->count() }}" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No hay pacientes para los filtros seleccionados.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $patients->links() }}</div>
</div>
<style>
.attendance-grid { max-height: 68vh; }
.attendance-grid th { min-width: 42px; position: sticky; top: 0; z-index: 2; }
.attendance-grid .sticky-patient { left: 0; min-width: 230px; position: sticky; z-index: 3; }
.attendance-grid tbody .sticky-patient { background: #fff; z-index: 1; }
.attendance-cell { font-size: 1.1rem; }
</style>
@endsection
