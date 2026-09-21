@extends('layouts.app')

@section('content')
@php
    $isConsultation = $type === \App\Models\Fua::NEPHROLOGY;
    $isMultisectorial = \App\Support\ClinicalService::isMultisectorial($type);
    $bulkRoute = $isMultisectorial ? route('fuas.multisectorial.bulk-pdf') : ($isConsultation ? route('fuas.nephrology.bulk-pdf') : route('fuas.hemodialysis.bulk-pdf'));
    $attentionLabel = mb_strtolower(\App\Support\ClinicalService::label($type));
@endphp
<style>
    .fua-print-page {
        max-width: 1540px;
    }

    .fua-filter-grid {
        display: grid;
        grid-template-columns: 1.15fr 1.8fr 1.15fr 1.15fr 1.15fr .75fr 1.15fr;
        gap: 1rem;
        align-items: start;
    }

    .fua-filter-check { padding-top: 2rem; }
    .fua-filter-action { padding-top: 1.75rem; }

    .fua-workspace-tabs {
        display: flex;
        gap: .75rem;
        padding: .6rem;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 1rem;
        box-shadow: 0 .25rem .75rem rgba(33, 37, 41, .08);
    }

    .fua-workspace-tab {
        flex: 1 1 0;
        padding: .85rem 1rem;
        border: 2px solid transparent;
        border-radius: .75rem;
        font-weight: 700;
        letter-spacing: .02em;
        transition: transform .15s ease, box-shadow .15s ease, background-color .15s ease;
    }

    .fua-workspace-tab:hover { transform: translateY(-1px); }
    .fua-workspace-tab-print { color: #842029; background: #f8d7da; }
    .fua-workspace-tab-print.is-active { color: #fff; background: #dc3545; box-shadow: 0 .3rem .7rem rgba(220, 53, 69, .25); }
    .fua-workspace-tab-doctor { color: #084298; background: #cfe2ff; }
    .fua-workspace-tab-doctor.is-active { color: #fff; background: #0d6efd; box-shadow: 0 .3rem .7rem rgba(13, 110, 253, .25); }

    @media (max-width: 1199.98px) {
        .fua-filter-grid { grid-template-columns: repeat(12, minmax(0, 1fr)); }
        .fua-filter-date,
        .fua-filter-select,
        .fua-filter-check,
        .fua-filter-action { grid-column: span 3; }
        .fua-filter-patient { grid-column: span 6; }
    }

    @media (max-width: 767.98px) {
        .fua-filter-date,
        .fua-filter-patient,
        .fua-filter-select,
        .fua-filter-check,
        .fua-filter-action { grid-column: 1 / -1; }
        .fua-filter-check,
        .fua-filter-action { padding-top: 0; }
        .fua-workspace-tabs { flex-direction: column; }
    }
</style>
<div class="container-fluid fua-print-page px-3 px-xl-4 py-3" x-data="fuaPdfViewer()">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <span class="text-uppercase small fw-bold text-primary">Impresiones</span>
            <h3 class="mb-1"><i class="bi bi-files me-2"></i>FUA de {{ $attentionLabel }}</h3>
            <p class="text-muted mb-0">Filtra las atenciones y prepara varias FUA en un solo documento, sin salir de esta pantalla.</p>
        </div>
    </div>

    <div class="card shadow-sm mb-4"><div class="card-body">
        <form method="GET" class="fua-filter-grid">
            <div class="fua-filter-date">
                <label class="form-label fw-semibold">Fecha de atención</label>
                <input type="date" name="date" value="{{ $date }}" class="form-control" @disabled(request()->boolean('all_dates'))>
            </div>
            @if($isMultisectorial)
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="fua-filter-select"><label class="form-label fw-semibold">Profesional</label><select name="professional_id" class="form-select"><option value="">Todos</option>@foreach($professionals as $professional)<option value="{{ $professional->id }}" @selected((string)request('professional_id') === (string)$professional->id)>{{ $professional->name }}</option>@endforeach</select></div>
            <div class="fua-filter-select"><label class="form-label fw-semibold">Estado FUA</label><select name="status" class="form-select"><option value="">Todos</option><option value="GENERATED" @selected(request('status') === 'GENERATED')>Generada</option></select></div>
            <div class="fua-filter-select"><label class="form-label fw-semibold">Sede</label><select name="sede_id" class="form-select"><option value="">Sede activa</option>@foreach($sedes as $sede)<option value="{{ $sede->id }}" @selected((string)request('sede_id') === (string)$sede->id)>{{ $sede->name }}</option>@endforeach</select></div>
            @endif
            <div class="fua-filter-patient">
                <label class="form-label fw-semibold">Nombre o DNI del paciente</label>
                <input name="patient" value="{{ request('patient') }}" class="form-control" placeholder="Escribe el nombre, apellido o DNI">
            </div>
            @if($isConsultation)
            <div class="fua-filter-select">
                <label class="form-label fw-semibold" for="prescription_status">Estado de receta</label>
                <select name="prescription_status" id="prescription_status" class="form-select">
                    <option value="">Todos</option>
                    <option value="with_prescription" @selected(request('prescription_status') === 'with_prescription')>Con receta</option>
                    <option value="without_prescription" @selected(request('prescription_status') === 'without_prescription')>Sin receta</option>
                </select>
            </div>
            @endif
            @if($type === \App\Models\Fua::HEMODIALYSIS)
            <div class="fua-filter-select">
                <label class="form-label fw-semibold" for="sequence">Secuencia del paciente</label>
                <select name="sequence" id="sequence" class="form-select">
                    <option value="">Todas las secuencias</option>
                    <option value="L-M-V" @selected($sequence === 'L-M-V')>L-M-V</option>
                    <option value="M-J-S" @selected($sequence === 'M-J-S')>M-J-S</option>
                </select>
                <div class="form-text">Se selecciona automáticamente según la fecha.</div>
            </div>
            @endif
            @unless($isMultisectorial)<div class="fua-filter-select">
                <label class="form-label fw-semibold" for="modulo">Módulo</label>
                <select name="modulo" id="modulo" class="form-select">
                    <option value="">Todos los módulos</option>
                    @foreach(\App\Models\Patient::MODULES as $module)
                        <option value="{{ $module }}" @selected((string) request('modulo') === (string) $module)>{{ $module === \App\Models\Patient::ISOLATED_MODULE ? 'Módulo AISLADO' : 'Módulo '.$module }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fua-filter-select">
                <label class="form-label fw-semibold" for="turno">Turno</label>
                <select name="turno" id="turno" class="form-select">
                    <option value="">Todos los turnos</option>
                    @foreach(range(1, 4) as $shift)
                        <option value="{{ $shift }}" @selected((string) request('turno') === (string) $shift)>Turno {{ $shift }}</option>
                    @endforeach
                </select>
            </div>@endunless
            <div class="fua-filter-check">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="all_dates" value="1" id="allDates" @checked(request()->boolean('all_dates')) onchange="this.form.querySelector('[name=date]').disabled=this.checked">
                    <label class="form-check-label" for="allDates">Todas las FUA</label>
                </div>
            </div>
            <div class="fua-filter-action"><button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filtrar</button></div>
        </form>
    </div></div>

    @if($type === \App\Models\Fua::HEMODIALYSIS && $date)
    <div x-data="{ activeFuaTab: 'print' }">
    <div class="fua-workspace-tabs mb-4" role="tablist" aria-label="Opciones de gestión de FUA">
        <button type="button" class="fua-workspace-tab fua-workspace-tab-print" :class="{ 'is-active': activeFuaTab === 'print' }" @click="activeFuaTab = 'print'" role="tab" :aria-selected="activeFuaTab === 'print'" aria-controls="fua-print-panel">
            <i class="bi bi-printer-fill me-2"></i>FUAS A IMPRIMIR
        </button>
        <button type="button" class="fua-workspace-tab fua-workspace-tab-doctor" :class="{ 'is-active': activeFuaTab === 'doctor' }" @click="activeFuaTab = 'doctor'" role="tab" :aria-selected="activeFuaTab === 'doctor'" aria-controls="fua-doctor-panel">
            <i class="bi bi-person-badge-fill me-2"></i>CAMBIO DE MÉDICO FUA
        </button>
    </div>

    <div id="fua-doctor-panel" x-show="activeFuaTab === 'doctor'" x-cloak role="tabpanel">
    <div class="card border-primary shadow-sm mb-4" x-data="{ selected: [], saving: false }">
        <div class="card-header bg-primary text-white">
            <strong><i class="bi bi-person-badge me-2"></i>Cambiar médico firmante en bloque</strong>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">Selecciona las FUA del bloque del {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} y asigna el médico que las firmará.</p>
            <form method="POST" action="{{ route('fuas.hemodialysis.responsible.bulk-update') }}" @submit="saving = true">
                @csrf
                @method('PUT')
                <template x-for="fuaId in selected" :key="fuaId"><input type="hidden" name="fuas[]" :value="fuaId"></template>
                <div class="row g-3 align-items-end">
                    <div class="col-lg-5">
                        <label for="bulkResponsible" class="form-label fw-semibold">Médico que firmará</label>
                        <select id="bulkResponsible" name="responsible_user_id" class="form-select" required>
                            <option value="">Selecciona un médico</option>
                            @foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <button type="button" class="btn btn-outline-primary w-100" @click="selected = {{ $dailyFuaIds->toJson() }}">
                            <i class="bi bi-check2-square me-1"></i>Seleccionar todo el bloque del día ({{ $dailyFuaIds->count() }})
                        </button>
                    </div>
                    <div class="col-lg-3">
                        <button type="submit" class="btn btn-primary w-100" :disabled="selected.length === 0 || saving">
                            <i class="bi bi-save me-1"></i>Guardar en <span x-text="selected.length">0</span> FUA
                        </button>
                    </div>
                </div>
                <div class="form-text mt-2">También puedes marcar únicamente las FUA deseadas en la tabla inferior.</div>

                <div class="table-responsive mt-3"><table class="table table-sm align-middle mb-0">
                    <thead><tr><th class="text-center" style="width: 55px">Elegir</th><th>FUA</th><th>Paciente</th><th>Turno</th></tr></thead>
                    <tbody>@foreach($fuas as $fua)<tr>
                        <td class="text-center"><input type="checkbox" class="form-check-input" value="{{ $fua->id }}" x-model.number="selected" aria-label="Seleccionar FUA {{ $fua->number }}"></td>
                        <td>{{ $fua->number }}</td><td>{{ $fua->order?->patient?->full_name ?: 'Sin paciente' }}</td><td>{{ $fua->order?->turno ? 'Turno '.$fua->order->turno : '—' }}</td>
                    </tr>@endforeach</tbody>
                </table></div>
            </form>
        </div>
    </div>
    </div>
    <div id="fua-print-panel" x-show="activeFuaTab === 'print'" role="tabpanel">
    @endif

    <form method="POST" action="{{ $bulkRoute }}" @submit.prevent="openBulkPdf($event.currentTarget)">
        @csrf
        @if($isMultisectorial)<input type="hidden" name="type" value="{{ $type }}">@endif
        <div class="card shadow-sm overflow-hidden">
            <div class="card-header bg-white d-flex justify-content-between align-items-center gap-3">
                <span><strong>{{ $fuas->total() }}</strong> FUA encontradas</span>
                <button type="submit" class="btn btn-danger" :disabled="selected.length === 0 || pdfLoading"><i class="bi bi-printer me-2"></i>Imprimir seleccionadas (<span x-text="selected.length">0</span>)</button>
            </div>
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th class="text-center"><input type="checkbox" class="form-check-input" aria-label="Seleccionar esta página" @change="selected = $event.target.checked ? {{ $fuas->pluck('id')->values()->toJson() }} : []"></th>
                    <th>FUA</th><th>Paciente</th><th>DNI</th><th>Fecha</th><th>Módulo</th><th>Turno</th>@if($isConsultation)<th>Estado</th>@endif<th>Sede</th><th class="text-end">Documento</th>
                </tr></thead>
                <tbody>@forelse($fuas as $fua)
                    <tr>
                        <td class="text-center"><input type="checkbox" class="form-check-input" name="fuas[]" value="{{ $fua->id }}" x-model.number="selected"></td>
                        <td><strong class="text-primary">{{ $fua->number }}</strong></td>
                        <td>{{ $fua->order?->patient?->full_name ?: 'Sin paciente' }}</td>
                        <td>{{ $fua->order?->patient?->dni ?: '—' }}</td>
                        <td>{{ $fua->order?->fecha_orden ? \Carbon\Carbon::parse($fua->order->fecha_orden)->format('d/m/Y') : $fua->created_at->format('d/m/Y') }}</td>
                        <td>{{ $fua->order?->patient?->modulo ? 'Módulo '.$fua->order->patient->modulo : ($fua->order?->sala ?: '—') }}</td>
                        <td>{{ $fua->order?->turno ? 'Turno '.$fua->order->turno : '—' }}</td>
                        @if($isConsultation)
                        <td>
                            @if($fua->order?->nephrologyConsultation?->medications_exists)
                                <span class="badge bg-success">Con receta</span>
                            @else
                                <span class="badge bg-secondary">Sin receta</span>
                            @endif
                        </td>
                        @endif
                        <td>{{ $fua->order?->sede?->name ?: '—' }}</td>
                        <td class="text-end"><button type="button" @click="openPdf('{{ route('fuas.pdf', $fua) }}', 'FUA {{ $fua->number }}')" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Ver</button></td>
                    </tr>
                @empty<tr><td colspan="{{ $isConsultation ? 10 : 9 }}" class="text-center text-muted py-5">No hay FUA de {{ $attentionLabel }} para los filtros seleccionados.</td></tr>@endforelse</tbody>
            </table></div>
            @if($fuas->hasPages())<div class="card-footer bg-white">{{ $fuas->links() }}</div>@endif
        </div>
    </form>
    @if($type === \App\Models\Fua::HEMODIALYSIS && $date)
    </div>
    </div>
    @endif
    @include('fuas.partials.pdf-modal')
</div>

@include('fuas.partials.pdf-modal-script')
@endsection
