@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('warehouse.partials.navigation', [
        'title' => 'Configuración logística',
        'subtitle' => 'Defina la sede donde funciona el almacén principal de toda la organización.',
        'currentWarehouse' => $principalWarehouse,
    ])

    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="alert alert-info d-flex gap-3 align-items-start">
                        <i class="bi bi-info-circle-fill fs-4"></i>
                        <div>
                            <strong>Flujo centralizado</strong>
                            <div>Las solicitudes de todas las sedes se dirigirán a este almacén. El personal con rol <strong>LOGÍSTICA</strong> podrá revisarlas y despacharlas; el área solicitante confirmará la recepción.</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('warehouse.configuration.update') }}">
                        @csrf
                        @method('PUT')
                        <label for="principal_sede_id" class="form-label fw-semibold">Sede del almacén principal</label>
                        <select id="principal_sede_id" name="principal_sede_id" class="form-select @error('principal_sede_id') is-invalid @enderror" required>
                            <option value="">Seleccione una sede activa...</option>
                            @foreach($sedes as $sede)
                                <option value="{{ $sede->id }}" @selected((string) old('principal_sede_id', $principalWarehouse?->sede_id) === (string) $sede->id)>
                                    {{ $sede->name }}{{ $sede->code ? ' · '.$sede->code : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('principal_sede_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <p class="form-text">Solo puede existir un almacén principal. El cambio conserva los almacenes y existencias de las demás sedes.</p>
                        <div class="d-flex justify-content-end mt-4">
                            <button class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Guardar configuración</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
