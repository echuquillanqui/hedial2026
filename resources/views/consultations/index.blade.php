@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h3 class="mb-0 fw-bold">Consultas nefrológicas</h3><small class="text-muted">Historia clínica y recetas médicas</small></div>
        <a href="{{ route('consultations.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Nueva consulta</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card shadow-sm">
        <div class="card-body border-bottom">
            <form class="row g-2"><div class="col-md-5"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por paciente o DNI"></div><div class="col"><button class="btn btn-outline-primary">Buscar</button></div></form>
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Fecha</th><th>Paciente</th><th>DNI</th><th>Médico</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>@forelse($consultations as $item)<tr>
                <td>{{ $item->consultation_date?->format('d/m/Y') }}</td><td class="fw-semibold">{{ $item->patient->full_name }}</td><td>{{ $item->patient->dni ?: '—' }}</td><td>{{ $item->doctor?->name ?: '—' }}</td>
                <td class="text-end"><a href="{{ route('consultations.edit', $item) }}" class="btn btn-sm btn-outline-primary">Rellenar / editar</a> <a target="_blank" href="{{ route('consultations.pdf', $item) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf"></i> Consulta PDF</a> <a target="_blank" href="{{ route('consultations.prescription.pdf', $item) }}" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf"></i> Receta</a></td>
            </tr>@empty<tr><td colspan="5" class="text-center text-muted py-5">No hay consultas registradas.</td></tr>@endforelse</tbody>
        </table></div>
        @if($consultations->hasPages())<div class="card-footer">{{ $consultations->links() }}</div>@endif
    </div>
</div>
@endsection
