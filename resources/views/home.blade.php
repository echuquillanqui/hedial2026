@extends('layouts.app')

@section('content')
<div class="dashboard-wrap py-4">
    <div class="container-fluid">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 hero-card">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                    <div>
                        <span class="badge text-bg-light border border-info-subtle text-info-emphasis mb-2">Dashboard clínico</span>
                        <h3 class="fw-bold mb-1 text-white">Control Integral de Hemodiálisis</h3>
                        <p class="mb-0 text-white-50">Visión operativa de consumos, sesiones pendientes y materiales críticos por fecha.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <form action="{{ route('home') }}" method="GET" class="bg-white rounded-3 d-flex align-items-center p-2 gap-2 shadow-sm">
                            <input type="date" name="date" class="form-control form-control-sm border-0" value="{{ $fechaActual }}" onchange="this.form.submit()">
                        </form>
                        <a href="{{ route('home.export.pdf', ['date' => $fechaActual]) }}" class="btn btn-danger rounded-3 px-3">
                            <i class="bi bi-filetype-pdf me-2"></i>Exportar PDF
                        </a>
                        <a href="{{ route('home.export.excel', ['date' => $fechaActual]) }}" class="btn btn-success rounded-3 px-3">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>Exportar Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-2">
                <div class="kpi-card kpi-primary">
                    <small>Sesiones del día</small>
                    <h2>{{ $kpis['totalSesiones'] }}</h2>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <div class="kpi-card kpi-success">
                    <small>Sesiones completas</small>
                    <h2>{{ $kpis['sesionesCompletas'] }}</h2>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <div class="kpi-card kpi-warning">
                    <small>Atenciones pendientes</small>
                    <h2>{{ $kpis['sesionesPendientes'] }}</h2>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="kpi-card kpi-dark">
                    <small>Materiales de diálisis consumidos</small>
                    <h2>{{ $kpis['materialesDialisisConsumidos'] }}</h2>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="kpi-card kpi-dark">
                    <small>Materiales indirectos consumidos</small>
                    <h2>{{ $kpis['materialesIndirectosConsumidos'] }}</h2>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xxl-8">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                        <h5 class="fw-bold mb-1">Consumo registrado por sesión</h5>
                        <p class="text-muted mb-0 small">Incluye insumos documentados por médico y enfermería.</p>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Paciente</th>
                                        <th>Mod</th>
                                        <th>Turno</th>
                                        <th>Dial.</th>
                                        <th>Hep.</th>
                                        <th>EPO</th>
                                        <th>Hierro</th>
                                        <th>Vit B12</th>
                                        <th>Calc.</th>
                                        <th>Bicar.</th>
                                        <th>QB</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($resumenInsumos as $fila)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-uppercase text-truncate" style="max-width: 190px;">{{ $fila['paciente'] }}</div>
                                            <small class="text-muted">{{ $fila['codigo'] }}</small>
                                        </td>
                                        <td>{{ $fila['modulo'] }}</td>
                                        <td>{{ $fila['turno'] }}</td>
                                        <td>{{ $fila['dializador'] > 0 ? $fila['dializador'] : '-' }}</td>
                                        <td>{{ $fila['heparina'] }}</td>
                                        <td>{{ $fila['epo'] }}</td>
                                        <td>{{ $fila['hierro'] }}</td>
                                        <td>{{ $fila['vitamina_b12'] }}</td>
                                        <td>{{ $fila['calcitriol'] }}</td>
                                        <td>{{ $fila['bicarbonato'] }}</td>
                                        <td>{{ $fila['qb'] }}</td>
                                        <td>
                                            <span class="badge rounded-pill {{ $fila['completa'] ? 'text-bg-success' : 'text-bg-warning' }}">
                                                {{ $fila['estado'] }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="12" class="text-center py-4">No hay sesiones registradas para esta fecha.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xxl-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                        <h5 class="fw-bold mb-1">Materiales base consumidos en diálisis</h5>
                        <p class="small text-muted mb-0">Cantidades registradas automáticamente por sesión finalizada.</p>
                    </div>
                    <div class="card-body pt-2">
                        <ul class="list-group list-group-flush">
                            @forelse($materialesDialisis as $material)
                            <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                                <span class="small pe-2">{{ $material['nombre'] }}</span>
                                <span class="badge rounded-pill text-bg-primary">{{ $material['cantidad'] }}</span>
                            </li>
                            @empty
                            <li class="list-group-item px-0 small text-muted">No hay consumo base registrado para esta fecha.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                        <h5 class="fw-bold mb-1">Materiales indirectos consumidos</h5>
                        <p class="small text-muted mb-0">Insumos extra no presentes en los productos base de diálisis.</p>
                    </div>
                    <div class="card-body pt-2">
                        <ul class="list-group list-group-flush">
                            @forelse($materialesIndirectos as $material)
                            <li class="list-group-item d-flex justify-content-between align-items-start px-0">
                                <span class="small pe-2">{{ $material['nombre'] }}</span>
                                <span class="badge rounded-pill text-bg-secondary">{{ $material['cantidad'] }}</span>
                            </li>
                            @empty
                            <li class="list-group-item px-0 small text-muted">No hay materiales indirectos registrados para esta fecha.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">Pendientes de documentación</h6>
                        @forelse($ordenesPendientes as $pendiente)
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                <div>
                                    <div class="fw-semibold text-uppercase small">{{ $pendiente['paciente'] }}</div>
                                    <small class="text-muted">{{ $pendiente['codigo'] }}</small>
                                </div>
                                <a href="{{ route('nurses.index', ['search' => $pendiente['codigo']]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Completar</a>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No hay pendientes, excelente trabajo del equipo.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-wrap { background: linear-gradient(180deg, #eef5ff 0%, #f7fafd 100%); min-height: 100vh; }
.hero-card { background: linear-gradient(120deg, #0f3d8f 0%, #0077b6 100%); }
.kpi-card { border-radius: 1rem; padding: 1rem 1.2rem; color: #fff; box-shadow: 0 10px 24px rgba(15, 61, 143, 0.12); }
.kpi-card small { opacity: .9; }
.kpi-card h2 { margin: .35rem 0 0; font-weight: 700; }
.kpi-primary { background: linear-gradient(135deg, #3867d6, #4b7bec); }
.kpi-success { background: linear-gradient(135deg, #20bf6b, #26de81); }
.kpi-warning { background: linear-gradient(135deg, #f7b731, #fd9644); }
.kpi-dark { background: linear-gradient(135deg, #2d3436, #636e72); }
.dashboard-table th { font-size: .78rem; text-transform: uppercase; letter-spacing: .02em; background: #f5f7fb; }
.dashboard-table td { white-space: nowrap; font-size: .85rem; }
</style>
@endsection
