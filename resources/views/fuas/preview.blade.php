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
    <div class="card shadow-sm overflow-hidden" style="height: calc(100vh - 175px); min-height: 620px">
        <iframe src="{{ route('fuas.pdf', $fua) }}#toolbar=1&navpanes=0" title="Vista previa de {{ $fua->number }}" class="border-0 w-100 h-100"></iframe>
    </div>
</div>
@endsection
