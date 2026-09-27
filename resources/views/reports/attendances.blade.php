@extends('layouts.app')

@section('content')
<div class="container-fluid attendance-report">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <span class="text-uppercase text-primary fw-bold small">Reportes</span>
            <h2 class="mb-1"><i class="bi bi-calendar2-check me-2"></i>Atenciones</h2>
            <p class="text-muted mb-0">Sesiones finalizadas registradas en Auditoría FISSAL.</p>
        </div>
        <a class="btn btn-success rounded-pill px-4" href="{{ route('reports.attendances.export', request()->query()) }}">
            <i class="bi bi-file-earmark-excel me-2"></i>Exportar a Excel
        </a>
    </div>

    <form method="GET" class="card card-body shadow-sm mb-4" id="attendanceFilters">
        <div class="row g-3 align-items-end">
            <div class="col-md-3 col-xl-2">
                <label class="form-label fw-semibold">Agrupar periodo</label>
                <select class="form-select" name="period" id="periodSelector">
                    <option value="day" @selected($period === 'day')>Por día</option>
                    <option value="week" @selected($period === 'week')>Por semana</option>
                    <option value="month" @selected($period === 'month')>Por mes</option>
                </select>
            </div>
            <div class="col-md-3 col-xl-2 period-input" data-period="day">
                <label class="form-label fw-semibold">Día</label>
                <input type="date" class="form-control" name="date" value="{{ request('date', today()->toDateString()) }}">
            </div>
            <div class="col-md-3 col-xl-2 period-input" data-period="week">
                <label class="form-label fw-semibold">Semana</label>
                <input type="week" class="form-control" name="week" value="{{ request('week', today()->format('o-\WW')) }}">
            </div>
            <div class="col-md-3 col-xl-2 period-input" data-period="month">
                <label class="form-label fw-semibold">Mes</label>
                <input type="month" class="form-control" name="month" value="{{ request('month', today()->format('Y-m')) }}">
            </div>
            <div class="col-md-4 col-xl-3"><label class="form-label fw-semibold">Paciente, DNI u orden</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Buscar..."></div>
            <div class="col-md-2 col-xl-1"><label class="form-label fw-semibold">Secuencia</label><select class="form-select" name="secuencia"><option value="">Todas</option>@foreach(['L-M-V', 'M-J-S'] as $value)<option value="{{ $value }}" @selected(request('secuencia') === $value)>{{ $value }}</option>@endforeach</select></div>
            <div class="col-md-2 col-xl-1"><label class="form-label fw-semibold">Módulo</label><select class="form-select" name="modulo"><option value="">Todos</option>@foreach(range(1, 4) as $value)<option value="{{ $value }}" @selected((string) request('modulo') === (string) $value)>{{ $value }}</option>@endforeach</select></div>
            <div class="col-md-2 col-xl-1"><label class="form-label fw-semibold">Turno</label><select class="form-select" name="turno"><option value="">Todos</option>@foreach(range(1, 4) as $value)<option value="{{ $value }}" @selected((string) request('turno') === (string) $value)>{{ $value }}</option>@endforeach</select></div>
            <div class="col-auto"><button class="btn btn-primary px-4"><i class="bi bi-funnel me-2"></i>Filtrar</button></div>
            <div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('reports.attendances.index') }}">Limpiar</a></div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-muted"><i class="bi bi-calendar-range me-1"></i>{{ $start->format('d/m/Y') }} al {{ $end->format('d/m/Y') }}</span>
        <span class="badge rounded-pill bg-primary px-3 py-2">{{ $total }} {{ $total === 1 ? 'atención' : 'atenciones' }}</span>
    </div>

    <div class="card shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-dark"><tr><th>Paciente</th><th>DNI</th><th>Secuencia</th><th>Inicio</th><th>Final</th><th>FUA</th><th>EPO 2000</th><th>EPO 4000</th><th>Vit. B12</th><th>Hierro</th><th>Calcitriol</th><th>Fecha</th><th>Lic. inicia</th><th>Lic. finaliza</th><th>Nefrólogo</th><th>Módulo</th><th>Turno</th></tr></thead>
                <tbody>
                @forelse($orders as $order)
                    @php($nurse = $order->nurse) @php($medical = $order->medical)
                    <tr>
                        <td class="text-nowrap fw-semibold">{{ $order->patient?->full_name ?: '—' }}</td><td>{{ $order->patient?->dni ?: '—' }}</td><td>{{ $order->patient?->secuencia ?: '—' }}</td>
                        <td>{{ optional($order->treatments->first())->hora ? substr($order->treatments->first()->hora, 0, 5) : '—' }}</td><td>{{ optional($order->treatments->last())->hora ? substr($order->treatments->last()->hora, 0, 5) : '—' }}</td><td>{{ $order->fua?->correlative ?? '—' }}</td>
                        <td>{{ $nurse?->epo2000 ?: '0' }}</td><td>{{ $nurse?->epo4000 ?: '0' }}</td><td>{{ $nurse?->vitamina_b12 ?: '0' }}</td><td>{{ $nurse?->hierro ?: '0' }}</td><td>{{ $nurse?->calcitriol ?: '0' }}</td>
                        <td class="text-nowrap">{{ optional($order->fecha_orden)->format('d/m/Y') }}</td><td>{{ $nurse?->enfermeroInicia?->name ?: '—' }}</td><td>{{ $nurse?->enfermeroFinaliza?->name ?: '—' }}</td><td>{{ $medical?->usuarioInicia?->name ?: $medical?->usuarioFinaliza?->name ?: '—' }}</td><td class="text-nowrap">{{ $order->sala ?: '—' }}</td><td>{{ $order->turno ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="17" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No hay atenciones para los filtros seleccionados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $orders->links() }}</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selector = document.getElementById('periodSelector');
    const updatePeriod = () => document.querySelectorAll('.period-input').forEach((element) => {
        const active = element.dataset.period === selector.value;
        element.classList.toggle('d-none', !active);
        element.querySelector('input').disabled = !active;
    });
    selector.addEventListener('change', updatePeriod);
    updatePeriod();
});
</script>
@endsection
