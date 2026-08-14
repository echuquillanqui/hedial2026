@extends('layouts.app')

@section('content')
<div class="container-fluid px-md-4 py-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div><a href="{{ route('fuas.index') }}" class="text-decoration-none small">← Volver a FUA</a><h4 class="mb-0 mt-1">Vista previa · {{ $fua->number }}</h4></div>
        <div class="d-flex gap-2">
            <a target="_blank" href="{{ route('fuas.pdf', $fua) }}" class="btn btn-outline-secondary"><i class="bi bi-box-arrow-up-right me-1"></i>Abrir PDF</a>
            <a href="{{ route('fuas.pdf', [$fua, 'download' => 1]) }}" class="btn btn-danger"><i class="bi bi-download me-1"></i>Descargar PDF</a>
        </div>
    </div>
    <form method="POST" action="{{ route('fuas.responsible.update', $fua) }}" class="card card-body shadow-sm mb-3">
        @csrf @method('PUT')
        <div class="row align-items-end g-2">
            <div class="col-md-9">
                <label for="responsible_user_id" class="form-label fw-semibold">Médico responsable de la atención</label>
                <select id="responsible_user_id" name="responsible_user_id" class="form-select">
                    <option value="">Médico que realizó la atención del día ({{ $fua->order?->medical?->usuarioInicia?->name ?? 'aún no registrado' }})</option>
                    @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}" @selected($fua->responsible_user_id === $doctor->id)>{{ $doctor->name }} · CMP {{ $doctor->license_number ?: 'sin registrar' }}{{ str_contains(mb_strtolower($doctor->profession ?? ''), 'nefr') ? ' · Nefrología' : '' }}</option>
                    @endforeach
                </select>
                <div class="form-text">Por defecto se usa el médico que inició la atención. También puede elegir otro médico o nefrólogo.</div>
            </div>
            <div class="col-md-3 d-grid"><button class="btn btn-primary">Guardar responsable</button></div>
        </div>
    </form>
    <div class="card shadow-sm overflow-hidden" style="height: calc(100vh - 290px); min-height: 620px">
        <iframe src="{{ route('fuas.pdf', $fua) }}#toolbar=1&navpanes=0" title="Vista previa de {{ $fua->number }}" class="border-0 w-100 h-100"></iframe>
    </div>
</div>
@endsection
