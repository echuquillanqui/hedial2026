@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 900px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h2 class="mb-1">Nueva orden de {{ \App\Support\ClinicalService::label($type) }}</h2><p class="text-muted mb-0">El sistema asignará automáticamente el período y la fecha límite.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('orders.multisectorial.index', ['type' => $type]) }}">Volver</a>
    </div>
    <div class="card shadow-sm border-0"><div class="card-body p-4">
        <form method="POST" action="{{ route('orders.multisectorial.store') }}">
            @csrf<input type="hidden" name="type" value="{{ $type }}">
            <div class="mb-3"><label class="form-label">Paciente</label><select name="patient_id" class="form-select @error('patient_id') is-invalid @enderror" required><option value="">Seleccione</option>@foreach($patients as $patient)<option value="{{ $patient->id }}" @selected((string) old('patient_id') === (string) $patient->id)>{{ $patient->full_name }} — {{ $patient->dni }}</option>@endforeach</select>@error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label class="form-label">Profesional responsable</label><select name="assigned_professional_id" class="form-select @error('assigned_professional_id') is-invalid @enderror" required><option value="">Seleccione</option>@foreach($professionals as $professional)<option value="{{ $professional->id }}" @selected((string) old('assigned_professional_id') === (string) $professional->id)>{{ $professional->name }}</option>@endforeach</select>@error('assigned_professional_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-4"><label class="form-label">Fecha programada</label><input type="date" name="fecha_orden" value="{{ old('fecha_orden', today()->toDateString()) }}" class="form-control @error('fecha_orden') is-invalid @enderror" required>@error('fecha_orden')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="alert alert-info small">No se generará todavía una FUA ni un formulario de atención. Esos procesos corresponden a los bloques siguientes.</div>
            <button class="btn btn-primary">Registrar orden</button>
        </form>
    </div></div>
</div>
@endsection
