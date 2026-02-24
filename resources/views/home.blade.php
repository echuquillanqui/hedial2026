@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" style="background-color: #f4f7f6; min-height: 100vh;">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0"><i class="bi bi-view-list text-primary me-2"></i>Control de Consumo y Pendientes</h4>
            <p class="text-muted small mb-0">Resumen de insumos aplicados y estado de historias clínicas</p>
        </div>
        <form action="{{ route('home') }}" method="GET" class="bg-white p-2 shadow-sm rounded-3 border d-flex gap-2">
            <input type="date" name="date" class="form-control form-control-sm border-0 fw-bold" value="{{ $fechaActual }}" onchange="this.form.submit()">
        </form>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs nav-justified custom-tabs" id="mainTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active py-3 fw-bold" data-bs-toggle="tab" data-bs-target="#tab-audit" type="button">
                        <i class="bi bi-shield-exclamation me-2"></i>PENDIENTES ({{ $ordenesPendientes->count() }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-bold" data-bs-toggle="tab" data-bs-target="#tab-insumos" type="button">
                        <i class="bi bi-box-seam me-2"></i>REGISTRO DE CONSUMO ({{ $total }})
                    </button>
                </li>
            </ul>
        </div>
        
        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-audit">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 table-hover">
                        <thead class="bg-light small fw-bold text-muted">
                            <tr>
                                <th class="ps-4 py-3">PACIENTE</th>
                                <th class="text-center">MOD</th>
                                <th class="text-center">TURNO</th>
                                <th class="text-center">MÉDICO</th>
                                <th class="text-center">ENFERM.</th>
                                <th class="text-center">MONIT.</th>
                                <th class="pe-4 text-end">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ordenesPendientes as $o)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 200px;">{{ $o->patient->surname }} {{ $o->patient->first_name }}</div>
                                    <small class="text-muted">{{ $o->codigo_unico }}</small>
                                </td>
                                <td class="text-center"><span class="badge bg-light text-dark border-0">{{ $o->sala }}</span></td>
                                <td class="text-center"><span class="badge bg-primary-subtle text-primary border-0">{{ $o->turno }}</span></td>
                                <td class="text-center"><i class="bi {{ $o->medical_ok ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }} fs-5"></i></td>
                                <td class="text-center"><i class="bi {{ $o->nurse_ok ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }} fs-5"></i></td>
                                <td class="text-center"><i class="bi {{ $o->treatment_ok ? 'bi-activity text-success' : 'bi-activity text-danger' }} fs-5"></i></td>
                                <td class="pe-4 text-end">
                                    <a href="{{ route('nurses.index', ['search' => $o->codigo_unico]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">Completar</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-5">No hay pendientes.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-insumos">
                
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 text-center" style="font-size: 0.75rem;">
                        <thead class="bg-dark text-white border-dark">
                            <tr>
                                <th class="text-start ps-3 py-3" style="width: 200px;">PACIENTE</th>
                                <th style="width: 50px;">MOD</th>
                                <th class="bg-primary">DIAL.</th>
                                <th class="bg-primary">HEP.</th>
                                <th class="bg-success">EPO</th>
                                <th class="bg-success">HIERRO</th>
                                <th class="bg-success">VIT. B</th>
                                <th class="bg-success">CALC.</th>
                                <th class="bg-info text-dark">BICAR.</th>
                                <th class="bg-info text-dark">QB</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ordenesCompletas as $o)
                            <tr class="bg-white">
                                <td class="text-start ps-3 fw-bold text-uppercase">
                                    <div class="text-truncate" style="max-width: 180px;">{{ $o->patient->surname }}</div>
                                </td>
                                <td>{{ $o->sala }}</td>
                                
                                <td class="fw-bold">
                                    {{ ($o->nurse && !empty($o->nurse->filtro) && $o->nurse->filtro != 'NO') ? '1' : '-' }}
                                </td>

                                <td class="fw-bold">
                                    {{ ($o->medical && $o->medical->heparina > 0) ? '1' : '-' }}
                                </td>

                                <td class="fw-bold text-success">
                                    @php $epo = ($o->medical->epo2000 ?? 0) + ($o->medical->epo4000 ?? 0); @endphp
                                    {{ $epo > 0 ? $epo : '-' }}
                                </td>
                                <td class="fw-bold text-success">{{ ($o->medical && $o->medical->hierro > 0) ? $o->medical->hierro : '-' }}</td>
                                <td class="fw-bold text-success">{{ ($o->medical && $o->medical->vitamina_b12 > 0) ? $o->medical->vitamina_b12 : '-' }}</td>
                                <td class="fw-bold text-success">{{ ($o->medical && $o->medical->calcitriol > 0) ? $o->medical->calcitriol : '-' }}</td>

                                <td class="fw-bold">{{ ($o->medical && !empty($o->medical->bicarbonato)) ? '1' : '-' }}</td>
                                <td class="fw-bold">{{ ($o->medical && $o->medical->qb > 0) ? '1' : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .table td, .table th { white-space: nowrap; padding: 0.5rem; }
    .text-truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .custom-tabs .nav-link { color: #64748b; border: none; border-bottom: 4px solid transparent; }
    .custom-tabs .nav-link.active { color: #1a2a6c; background: white; border-bottom: 4px solid #1a2a6c; }
</style>
@endsection