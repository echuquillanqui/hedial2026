@extends('layouts.app')

@section('content')
<div class="container py-0" style="max-width: 1150px;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="bi bi-clock-history text-success me-2"></i>Horarios médicos por módulo</h3>
            <p class="text-muted mb-0">Configure rangos de inicio y cierre; el sistema generará opciones usando el intervalo elegido.</p>
        </div>
        <a href="{{ route('medicals.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @if(session('success'))<div class="alert alert-success shadow-sm">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>Revise la configuración.</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="alert alert-info border-0 small">
        Se admite que un rango cruce la medianoche (por ejemplo, 23:45 a 00:30). La hora final sugerida siempre se filtrará para que sea posterior al último control del tratamiento.
    </div>
    <div class="card border-0 shadow-sm"><div class="card-body p-4">
        <ul class="nav nav-tabs" role="tablist">
            @foreach(\App\Models\Patient::MODULES as $module)
                <li class="nav-item"><button class="nav-link @if($loop->first) active @endif" data-bs-toggle="tab" data-bs-target="#medical-module-{{ $loop->index }}" type="button">{{ $module === \App\Models\Patient::ISOLATED_MODULE ? 'Módulo aislado' : "Módulo $module" }}</button></li>
            @endforeach
        </ul>
        <div class="tab-content border border-top-0 rounded-bottom p-3">
            @foreach(\App\Models\Patient::MODULES as $module)
                <div class="tab-pane fade @if($loop->first) show active @endif" id="medical-module-{{ $loop->index }}">
                    <form method="POST" action="{{ route('medicals.schedules.update') }}">
                        @csrf @method('PUT')
                        @foreach(range(1, 4) as $shift)
                            @php $schedule = $schedules->get($module)?->get($shift); @endphp
                            <fieldset class="border rounded-3 p-3 mb-3">
                                <legend class="float-none w-auto px-2 fs-6 fw-bold text-success mb-0">Turno {{ $shift }}</legend>
                                <div class="row g-3 align-items-end">
                                    @foreach(['start_from' => 'Inicio desde', 'start_to' => 'Inicio hasta', 'finish_from' => 'Final desde', 'finish_to' => 'Final hasta'] as $field => $label)
                                        <div class="col-6 col-lg-2"><label class="form-label small fw-bold" for="{{ $field }}_{{ $module }}_{{ $shift }}">{{ $label }}</label><input type="time" id="{{ $field }}_{{ $module }}_{{ $shift }}" name="schedules[{{ $module }}][{{ $shift }}][{{ $field }}]" class="form-control @error("schedules.$module.$shift.$field") is-invalid @enderror" value="{{ old("schedules.$module.$shift.$field", $schedule?->{$field} ? substr($schedule->{$field}, 0, 5) : '') }}" required></div>
                                    @endforeach
                                    <div class="col-12 col-lg-3"><label class="form-label small fw-bold">Intervalo dinámico</label><select name="schedules[{{ $module }}][{{ $shift }}][interval_minutes]" class="form-select" required>@foreach([1,2,5,10,15,20,30] as $minutes)<option value="{{ $minutes }}" @selected((int) old("schedules.$module.$shift.interval_minutes", $schedule?->interval_minutes ?? 5) === $minutes)>Cada {{ $minutes }} min</option>@endforeach</select></div>
                                </div>
                            </fieldset>
                        @endforeach
                        <div class="text-end"><button class="btn btn-success px-4" type="submit"><i class="bi bi-check-circle me-2"></i>Guardar este módulo</button></div>
                    </form>
                </div>
            @endforeach
        </div>
    </div></div>
</div>
@endsection
