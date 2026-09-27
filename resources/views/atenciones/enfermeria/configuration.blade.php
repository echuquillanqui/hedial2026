@extends('layouts.app')

@section('content')
<div class="container py-0" style="max-width: 1050px;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Horas de inicio por módulo</h3>
            <p class="text-muted mb-0">Defina cinco horas independientes para cada módulo y cada uno de sus cuatro turnos.</p>
        </div>
        <a href="{{ route('nurses.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
                <div class="alert alert-info border-0 small">
                    Las horas son fijas para la sede activa. Puede guardar cada módulo por separado, sin completar los demás.
                </div>
                <ul class="nav nav-tabs" role="tablist">
                    @foreach(\App\Models\Patient::MODULES as $module)
                        <li class="nav-item" role="presentation"><button class="nav-link @if($loop->first) active @endif" data-bs-toggle="tab" data-bs-target="#module-{{ $loop->index }}" type="button">{{ $module === \App\Models\Patient::ISOLATED_MODULE ? 'Módulo aislado' : "Módulo $module" }}</button></li>
                    @endforeach
                </ul>
                <div class="tab-content border border-top-0 rounded-bottom p-3">
                    @foreach(\App\Models\Patient::MODULES as $module)
                        <div class="tab-pane fade @if($loop->first) show active @endif" id="module-{{ $loop->index }}">
                            <form method="POST" action="{{ route('nurses.schedules.update') }}">
                                @csrf
                                @method('PUT')
                            @foreach(range(1, 4) as $shift)
                                @php $configuredTimes = old("schedules.$module.$shift", $schedules->get($module)?->get($shift)?->start_times ?? []); @endphp
                                <fieldset class="border rounded-3 p-3 mb-3">
                                    <legend class="float-none w-auto px-2 fs-6 fw-bold text-primary mb-0">Turno {{ $shift }}</legend>
                                    <div class="row g-3">
                                        @for($position = 0; $position < 5; $position++)
                                            <div class="col-6 col-md">
                                                <label class="form-label small fw-bold text-muted" for="schedule_{{ $module }}_{{ $shift }}_{{ $position }}">HORA {{ $position + 1 }}</label>
                                                <input type="time" class="form-control @error("schedules.$module.$shift.$position") is-invalid @enderror" id="schedule_{{ $module }}_{{ $shift }}_{{ $position }}" name="schedules[{{ $module }}][{{ $shift }}][]" value="{{ $configuredTimes[$position] ?? '' }}" required>
                                                @error("schedules.$module.$shift.$position")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        @endfor
                                    </div>
                                    @error("schedules.$module.$shift")<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                                </fieldset>
                            @endforeach
                                <div class="text-end pt-2">
                                    <button class="btn btn-primary px-4" type="submit"><i class="bi bi-check-circle me-2"></i>Guardar {{ $module === \App\Models\Patient::ISOLATED_MODULE ? 'módulo aislado' : "módulo $module" }}</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
        </div>
    </div>
</div>
@endsection
