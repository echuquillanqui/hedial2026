@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <span class="text-success small fw-bold">GESTIÓN CLÍNICA · LABORATORIO FISSAL</span>
            <h2 class="mb-1">Evolución de exámenes</h2>
            <p class="text-muted mb-0">Consulta los resultados por mes y descarga un Excel con una pestaña por paciente.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @foreach(['L-M-V' => 'L-M-V', 'M-J-S' => 'M-J-S'] as $sequence => $label)
                <a class="btn btn-outline-success" href="{{ route('laboratory.evolution.export', ['sequence' => $sequence, 'from' => $filters['from'] ?? now()->subMonths(15)->format('Y-m'), 'to' => $filters['to'] ?? now()->format('Y-m')]) }}">
                    <i class="bi bi-file-earmark-excel me-1"></i> Excel {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('laboratory.evolution.index') }}" class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label" for="sequence">Secuencia</label>
                    <select class="form-select" id="sequence" name="sequence" required>
                        <option value="L-M-V" @selected(($filters['sequence'] ?? 'L-M-V') === 'L-M-V')>L-M-V</option>
                        <option value="M-J-S" @selected(($filters['sequence'] ?? '') === 'M-J-S')>M-J-S</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <label class="form-label" for="patient">Paciente</label>
                    <select class="form-select" id="patient" name="patient">
                        <option value="">Todos (mostrar primero)</option>
                        @foreach($patientOptions as $patient)
                            <option value="{{ $patient->id }}" @selected((int) ($filters['patient'] ?? 0) === $patient->id)>{{ $patient->full_name }}{{ $patient->dni ? ' · DNI '.$patient->dni : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label" for="from">Desde</label>
                    <input class="form-control" id="from" type="month" name="from" value="{{ $filters['from'] ?? now()->subMonths(15)->format('Y-m') }}" required>
                </div>
                <div class="col-sm-6 col-lg-2">
                    <label class="form-label" for="to">Hasta</label>
                    <input class="form-control" id="to" type="month" name="to" value="{{ $filters['to'] ?? now()->format('Y-m') }}" required>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button class="btn btn-success flex-grow-1"><i class="bi bi-search me-1"></i>Ver</button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedPatient)
        @php
            $resultMap = [];
            foreach ($selectedPatient->laboratoryOrders as $laboratoryOrder) {
                $resultDate = $laboratoryOrder->sampled_at ?? $laboratoryOrder->created_at;
                if (!$resultDate) continue;
                foreach ($laboratoryOrder->items as $resultItem) {
                    $resultMap[$resultItem->test_id][$resultDate->format('Y-m')] = $resultItem->result_value;
                }
            }
            $monthLabel = fn ($month) => ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SET','OCT','NOV','DIC'][$month->month - 1].'-'.$month->format('y');
        @endphp
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-2">
            <div><h4 class="mb-1">{{ $selectedPatient->full_name }}</h4><div class="text-muted small">DNI: {{ $selectedPatient->dni ?: '—' }} · Secuencia {{ $selectedPatient->secuencia }}</div></div>
            <span class="badge text-bg-light border">{{ count($months) }} meses · {{ $tests->sum(fn ($group) => $group['tests']->count()) }} exámenes</span>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0" style="min-width: 1000px">
                    <thead class="table-light"><tr><th class="position-sticky start-0" style="min-width:260px">EXÁMENES</th>@foreach($months as $month)<th class="text-center text-nowrap">{{ $monthLabel($month) }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach($tests as $group)
                            <tr class="table-success"><th colspan="{{ count($months) + 1 }}" class="small fw-bold">{{ $group['label'] }}</th></tr>
                            @foreach($group['tests'] as $test)
                                <tr><th class="position-sticky start-0 bg-white">{{ $test->name }} <small class="text-muted fw-normal">{{ $test->unit }}</small></th>
                                    @foreach($months as $month)<td class="text-center">{{ $resultMap[$test->id][$month->format('Y-m')] ?? '—' }}</td>@endforeach
                                </tr>
                            @endforeach
                        @endforeach
                        @if($tests->isEmpty())<tr><td colspan="{{ count($months) + 1 }}" class="text-center text-muted py-4">No hay exámenes FISSAL configurados.</td></tr>@endif
                        @if(count($months) === 0)<tr><td colspan="{{ $tests->sum(fn ($group) => $group['tests']->count()) + 1 }}" class="text-center text-muted py-4">No hay resultados registrados en el rango seleccionado.</td></tr>@endif
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-info border-0">No hay pacientes activos de la secuencia seleccionada en esta sede.</div>
    @endif
</div>
@endsection
