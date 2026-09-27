@extends('layouts.app')

@section('content')
<div class="container py-0" style="max-width: 1050px;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Horas de inicio por módulo</h3>
            <p class="text-muted mb-0">Defina las cinco horas que el personal podrá seleccionar al iniciar el monitoreo.</p>
        </div>
        <a href="{{ route('nurses.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('nurses.schedules.update') }}">
        @csrf
        @method('PUT')
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="alert alert-info border-0 small">
                    Estas horas son fijas para la sede activa. Así, aunque el enfermero abra la ficha tarde, siempre verá las opciones correctas de su módulo.
                </div>

                @foreach(\App\Models\Patient::MODULES as $module)
                    @php
                        $configuredTimes = old("schedules.$module", $schedules->get($module)?->start_times ?? []);
                        $moduleLabel = $module === \App\Models\Patient::ISOLATED_MODULE ? 'Módulo aislado' : "Módulo $module";
                    @endphp
                    <fieldset class="border rounded-3 p-3 mb-3">
                        <legend class="float-none w-auto px-2 fs-6 fw-bold text-primary mb-0">{{ $moduleLabel }}</legend>
                        <div class="row g-3">
                            @for($position = 0; $position < 5; $position++)
                                <div class="col-6 col-md">
                                    <label class="form-label small fw-bold text-muted" for="schedule_{{ $module }}_{{ $position }}">HORA {{ $position + 1 }}</label>
                                    <input
                                        type="time"
                                        class="form-control @error("schedules.$module.$position") is-invalid @enderror"
                                        id="schedule_{{ $module }}_{{ $position }}"
                                        name="schedules[{{ $module }}][]"
                                        value="{{ $configuredTimes[$position] ?? '' }}"
                                        required
                                    >
                                    @error("schedules.$module.$position")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endfor
                        </div>
                        @error("schedules.$module")<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </fieldset>
                @endforeach
            </div>
            <div class="card-footer bg-white border-0 text-end p-4 pt-0">
                <button class="btn btn-primary px-4" type="submit"><i class="bi bi-check-circle me-2"></i>Guardar horas</button>
            </div>
        </div>
    </form>
</div>
@endsection
