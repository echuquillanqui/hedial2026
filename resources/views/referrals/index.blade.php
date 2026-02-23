@extends('layouts.app')

@section('content')
<div class="container-fluid px-md-4">
    <div class="row align-items-center mb-4 g-3">
        <div class="col-12 col-md-4">
            <h2 class="fw-bold text-dark mb-0">Hojas de Referencia</h2>
        </div>
        
        <div class="col-12 col-md-5">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="referralSearch" class="form-control border-start-0" placeholder="Buscar por DNI o Paciente...">
            </div>
        </div>

        <div class="col-12 col-md-3 text-md-end">
            <div class="dropdown">
                <button class="btn btn-primary btn-lg dropdown-toggle w-100 shadow-sm" data-bs-toggle="dropdown">
                    <i class="bi bi-plus-circle me-1"></i> Nueva Referencia
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><a class="dropdown-item py-2" href="{{ route('referrals.create', ['type' => 'SIS']) }}"><span class="badge bg-success me-2">SIS</span> Formato SIS</a></li>
                    <li><a class="dropdown-item py-2" href="{{ route('referrals.create', ['type' => 'ESSALUD']) }}"><span class="badge bg-primary me-2">ESS</span> EsSalud / SaludPol</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold">
                    <tr>
                        <th class="ps-4">Código</th>
                        <th>Paciente / DNI</th>
                        <th class="d-none d-md-table-cell">Seguro</th>
                        <th class="d-none d-lg-table-cell">Establecimiento Origen</th>
                        <th class="d-none d-lg-table-cell">Establecimiento Destino</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @include('referrals.partials.table_rows')
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection