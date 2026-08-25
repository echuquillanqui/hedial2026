@extends('layouts.app')

@section('content')
@php
    $isConsultation = $type === \App\Models\Fua::NEPHROLOGY;
    $isMultisectorial = \App\Support\ClinicalService::isMultisectorial($type);
    $bulkRoute = $isMultisectorial ? route('fuas.multisectorial.bulk-pdf') : ($isConsultation ? route('fuas.nephrology.bulk-pdf') : route('fuas.hemodialysis.bulk-pdf'));
    $attentionLabel = mb_strtolower(\App\Support\ClinicalService::label($type));
@endphp
<div class="container py-3" x-data="fuaPdfViewer()">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <span class="text-uppercase small fw-bold text-primary">Impresiones</span>
            <h3 class="mb-1"><i class="bi bi-files me-2"></i>FUA de {{ $attentionLabel }}</h3>
            <p class="text-muted mb-0">Filtra las atenciones y prepara varias FUA en un solo documento, sin salir de esta pantalla.</p>
        </div>
    </div>

    <div class="card shadow-sm mb-4"><div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3 col-lg-2">
                <label class="form-label fw-semibold">Fecha de atención</label>
                <input type="date" name="date" value="{{ $date }}" class="form-control" @disabled(request()->boolean('all_dates'))>
            </div>
            @if($isMultisectorial)
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="col-md-3 col-lg-2"><label class="form-label fw-semibold">Profesional</label><select name="professional_id" class="form-select"><option value="">Todos</option>@foreach($professionals as $professional)<option value="{{ $professional->id }}" @selected((string)request('professional_id') === (string)$professional->id)>{{ $professional->name }}</option>@endforeach</select></div>
            <div class="col-md-3 col-lg-2"><label class="form-label fw-semibold">Estado FUA</label><select name="status" class="form-select"><option value="">Todos</option><option value="GENERATED" @selected(request('status') === 'GENERATED')>Generada</option></select></div>
            <div class="col-md-3 col-lg-2"><label class="form-label fw-semibold">Sede</label><select name="sede_id" class="form-select"><option value="">Sede activa</option>@foreach($sedes as $sede)<option value="{{ $sede->id }}" @selected((string)request('sede_id') === (string)$sede->id)>{{ $sede->name }}</option>@endforeach</select></div>
            @endif
            <div class="col-md-5 col-lg-3">
                <label class="form-label fw-semibold">Nombre o DNI del paciente</label>
                <input name="patient" value="{{ request('patient') }}" class="form-control" placeholder="Escribe el nombre, apellido o DNI">
            </div>
            @unless($isMultisectorial)<div class="col-md-3 col-lg-2">
                <label class="form-label fw-semibold" for="modulo">Módulo</label>
                <select name="modulo" id="modulo" class="form-select">
                    <option value="">Todos los módulos</option>
                    @foreach(range(1, 4) as $module)
                        <option value="{{ $module }}" @selected((string) request('modulo') === (string) $module)>Módulo {{ $module }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <label class="form-label fw-semibold" for="turno">Turno</label>
                <select name="turno" id="turno" class="form-select">
                    <option value="">Todos los turnos</option>
                    @foreach(range(1, 4) as $shift)
                        <option value="{{ $shift }}" @selected((string) request('turno') === (string) $shift)>Turno {{ $shift }}</option>
                    @endforeach
                </select>
            </div>@endunless
            <div class="col-md-3 col-lg-1">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="all_dates" value="1" id="allDates" @checked(request()->boolean('all_dates')) onchange="this.form.querySelector('[name=date]').disabled=this.checked">
                    <label class="form-check-label" for="allDates">Todas las FUA</label>
                </div>
            </div>
            <div class="col-md-3 col-lg-2"><button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filtrar</button></div>
        </form>
    </div></div>

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
                    <th>FUA</th><th>Paciente</th><th>DNI</th><th>Fecha</th><th>Sede</th><th class="text-end">Documento</th>
                </tr></thead>
                <tbody>@forelse($fuas as $fua)
                    <tr>
                        <td class="text-center"><input type="checkbox" class="form-check-input" name="fuas[]" value="{{ $fua->id }}" x-model.number="selected"></td>
                        <td><strong class="text-primary">{{ $fua->number }}</strong></td>
                        <td>{{ $fua->order?->patient?->full_name ?: 'Sin paciente' }}</td>
                        <td>{{ $fua->order?->patient?->dni ?: '—' }}</td>
                        <td>{{ $fua->order?->fecha_orden ? \Carbon\Carbon::parse($fua->order->fecha_orden)->format('d/m/Y') : $fua->created_at->format('d/m/Y') }}</td>
                        <td>{{ $fua->order?->sede?->name ?: '—' }}</td>
                        <td class="text-end"><button type="button" @click="openPdf('{{ route('fuas.pdf', $fua) }}', 'FUA {{ $fua->number }}')" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Ver</button></td>
                    </tr>
                @empty<tr><td colspan="7" class="text-center text-muted py-5">No hay FUA de {{ $attentionLabel }} para los filtros seleccionados.</td></tr>@endforelse</tbody>
            </table></div>
            @if($fuas->hasPages())<div class="card-footer bg-white">{{ $fuas->links() }}</div>@endif
        </div>
    </form>
    @include('fuas.partials.pdf-modal')
</div>

@include('fuas.partials.pdf-modal-script')
@endsection
