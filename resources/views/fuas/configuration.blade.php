@extends('layouts.app')

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Configuración global de FUA</h3>
            <p class="text-muted mb-0">Estos datos comunes y series se aplicarán automáticamente a las nuevas FUA.</p>
        </div>
        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">Volver a órdenes</a>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('fuas.configuration.update') }}">
        @csrf @method('PUT')
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white fw-bold">Datos comunes del formato</div>
            <div class="card-body row g-3">
                @foreach([
                    'ipress_code' => 'Código IPRESS', 'ipress_name' => 'Nombre de IPRESS',
                    'diagnosis_code' => 'Código CIE-10', 'diagnosis_name' => 'Diagnóstico predeterminado',
                    'responsible_name' => 'Responsable de la atención', 'responsible_document' => 'DNI del responsable',
                    'responsible_college_number' => 'N.º de colegiatura', 'responsible_specialty' => 'Especialidad'
                ] as $field => $label)
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $label }}</label>
                        <input name="{{ $field }}" class="form-control" value="{{ old($field, $configuration->$field) }}" {{ in_array($field, ['responsible_name', 'responsible_document', 'responsible_college_number']) ? '' : 'required' }}>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white fw-bold">Series y correlativos</div>
            <div class="card-body">
                <div class="alert alert-warning small">El próximo número se reserva dentro de una transacción. La combinación de serie y correlativo es única, incluso cuando se generan órdenes en bloque.</div>
                <div class="row g-3">
                    @foreach([
                        'hemodialysis' => 'FUA de la atención de hemodiálisis',
                        'nephrology' => 'FUA de consulta nefrológica',
                        'correction' => 'FUA de subsanación'
                    ] as $prefix => $label)
                        <div class="col-12"><h6 class="text-primary mb-0">{{ $label }}</h6></div>
                        <div class="col-md-7">
                            <label class="form-label">Serie</label>
                            <input name="{{ $prefix }}_series" class="form-control" value="{{ old($prefix.'_series', $configuration->{$prefix.'_series'}) }}" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Próximo correlativo</label>
                            <input type="number" min="1" name="{{ $prefix }}_next_number" class="form-control" value="{{ old($prefix.'_next_number', $configuration->{$prefix.'_next_number'}) }}" required>
                        </div>
                    @endforeach
                    <div class="col-md-5">
                        <label class="form-label">Cantidad de dígitos del correlativo</label>
                        <input type="number" min="1" max="12" name="number_length" class="form-control" value="{{ old('number_length', $configuration->number_length) }}" required>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-end mt-3"><button class="btn btn-success px-4">Guardar configuración global</button></div>
    </form>
</div>
@endsection
