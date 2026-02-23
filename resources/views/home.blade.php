@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="fw-bold">Dashboard</h3>
            <p class="text-muted">Gestión de referencias e información del establecimiento.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-start border-primary border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="p-3 bg-primary bg-opacity-10 rounded">
                            <i class="bi bi-file-medical fs-2 text-primary"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold">Nueva Referencia</h5>
                    <p class="text-muted small">Generar una nueva hoja de referencia con numeración automática.</p>
                    <a href="#" class="btn btn-primary w-100 rounded-pill">Crear Ahora</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-start border-success border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="p-3 bg-success bg-opacity-10 rounded text-success">
                            <i class="bi bi-people fs-2"></i>
                        </div>
                        <span class="badge bg-success rounded-pill">{{ \App\Models\Patient::count() }}</span>
                    </div>
                    <h5 class="fw-bold">Pacientes</h5>
                    <p class="text-muted small">Base de datos de pacientes registrados para atención inmediata.</p>
                    <a href="#" class="btn btn-outline-success w-100 rounded-pill">Ver Pacientes</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-start border-info border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="p-3 bg-info bg-opacity-10 rounded text-info">
                            <i class="bi bi-journal-text fs-2"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold">Consultar Historial</h5>
                    <p class="text-muted small">Revisar referencias enviadas y recibidas en el establecimiento.</p>
                    <a href="#" class="btn btn-outline-info w-100 rounded-pill">Abrir Archivo</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection