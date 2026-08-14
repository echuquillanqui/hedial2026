@extends('layouts.app')

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Configuración global de FUA</h3>
            <p class="text-muted mb-0">Estos datos comunes y series se aplicarán automáticamente a las nuevas FUA.</p>
        </div>
        <a href="{{ route('fuas.index') }}" class="btn btn-outline-secondary"><i class="bi bi-files me-1"></i>Ver FUA generadas</a>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('fuas.configuration.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white fw-bold">Identidad de la empresa y datos comunes</div>
            <div class="card-body row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Logo de la empresa</label>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        @if($configuration->logo_path)
                            <img src="{{ asset('storage/'.$configuration->logo_path) }}" alt="Logo actual" class="border rounded bg-white p-2" style="width:100px;height:70px;object-fit:contain">
                        @endif
                        <div class="flex-grow-1">
                            <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/webp">
                            <div class="form-text">PNG, JPG o WebP, máximo 2 MB. Se adapta automáticamente al encabezado del PDF.</div>
                        </div>
                        @if($configuration->logo_path)
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="removeLogo"><label class="form-check-label" for="removeLogo">Quitar logo</label></div>
                        @endif
                    </div>
                </div>
                @foreach([
                    'ipress_code' => 'Código IPRESS', 'ipress_name' => 'Nombre de IPRESS',
                    'company_name' => 'Razón social / nombre comercial', 'company_address' => 'Dirección',
                    'company_phone' => 'Teléfono de contacto',
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

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-dark fw-bold">Plantilla de consulta nefrológica</div>
            <div class="card-body row g-3">
                @foreach([
                    'consultation_reason' => 'Motivo de consulta',
                    'default_etiology' => 'Etiología predeterminada',
                    'default_vascular_access' => 'Acceso vascular predeterminado',
                    'secondary_diagnosis_code' => 'Segundo código CIE-10',
                    'secondary_diagnosis_name' => 'Segundo diagnóstico'
                ] as $field => $label)
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">{{ $label }}</label>
                        <input name="{{ $field }}" class="form-control" value="{{ old($field, $configuration->$field) }}" @required($field === 'consultation_reason')>
                    </div>
                @endforeach
                <div class="col-12">
                    <label class="form-label fw-semibold">Anamnesis predeterminada</label>
                    <textarea name="default_anamnesis" rows="3" class="form-control" placeholder="Se usará cuando la ficha médica no tenga evaluación registrada.">{{ old('default_anamnesis', $configuration->default_anamnesis) }}</textarea>
                </div>
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
