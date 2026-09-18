@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="consultationAudit()" @keydown.window="handleShortcut($event)">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div><h3 class="mb-1"><i class="bi bi-clipboard2-pulse me-2"></i>Auditoría de consultas</h3><p class="text-muted mb-0">Consultas nefrológicas atendidas por un médico y medicamentos recetados.</p></div>
        <span class="badge rounded-pill text-bg-light border px-3 py-2"><strong>{{ $consultations->total() }}</strong> {{ $consultations->total() === 1 ? 'consulta encontrada' : 'consultas encontradas' }}</span>
    </div>

    <form method="GET" class="card card-body shadow-sm mb-4 audit-consultation-filters">
        <div class="row g-2 align-items-end">
            <div class="col-xl-3 col-lg-4"><label class="form-label">Paciente o DNI</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Nombres, apellidos o DNI"></div>
            <div class="col-xl-2 col-sm-6"><label class="form-label">Fecha</label><input type="date" class="form-control" name="date" value="{{ request('date', today()->toDateString()) }}"></div>
            <div class="col-xl-2 col-sm-6"><label class="form-label">Médico</label><select class="form-select" name="doctor"><option value="">Todos</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}" @selected((string) request('doctor') === (string) $doctor->id)>{{ $doctor->name }}</option>@endforeach</select></div>
            <div class="col-xl col-sm-4"><label class="form-label">Secuencia</label><select class="form-select" name="secuencia"><option value="">Todas</option>@foreach(['L-M-V', 'M-J-S'] as $value)<option value="{{ $value }}" @selected(request('secuencia') === $value)>{{ $value }}</option>@endforeach</select></div>
            <div class="col-xl col-sm-4"><label class="form-label">Módulo</label><select class="form-select" name="modulo"><option value="">Todos</option>@foreach(\App\Models\Patient::MODULES as $value)<option value="{{ $value }}" @selected((string) request('modulo') === (string) $value)>MÓDULO {{ $value }}</option>@endforeach</select></div>
            <div class="col-xl col-sm-4"><label class="form-label">Turno</label><select class="form-select" name="turno"><option value="">Todos</option>@foreach(range(1, 4) as $value)<option value="{{ $value }}" @selected((string) request('turno') === (string) $value)>Turno {{ $value }}</option>@endforeach</select></div>
            <div class="col-xl-auto d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-search me-1"></i>Buscar</button><a class="btn btn-outline-secondary" href="{{ route('audit.consultations') }}">Limpiar</a></div>
        </div>
    </form>

    <div class="alert alert-light border py-2 small"><i class="bi bi-keyboard me-2"></i>Presione <kbd>1</kbd> (teclado alfanumérico o numérico) para ver la receta de la primera consulta visible.</div>
    <div class="card shadow-sm overflow-hidden"><div class="table-responsive"><table class="table table-sm table-striped align-middle mb-0 consultation-audit-table">
        <thead class="table-dark"><tr><th>Fecha</th><th>Apellidos y nombres completos</th><th>DNI</th><th>N.° FUA</th><th>Hora de consulta</th><th>Médico que atendió</th><th class="text-center">Receta</th></tr></thead>
        <tbody>@forelse($consultations as $consultation)<tr>
            <td class="text-nowrap">{{ $consultation->consultation_date?->format('d/m/Y') ?: '—' }}</td>
            <td><strong>{{ $consultation->patient?->full_name ?: '—' }}</strong></td>
            <td>{{ $consultation->patient?->dni ?: '—' }}</td>
            <td class="text-center">{{ $consultation->order?->fua?->correlative ?? '—' }}</td>
            <td class="text-center">{{ $consultation->consultation_time ? substr($consultation->consultation_time, 0, 5) : '—' }}</td>
            <td>{{ $consultation->doctor->name }}</td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-primary prescription-button" @click="openPrescription(@js($consultation->patient?->full_name), @js($consultation->medications->map->only(['fua_code', 'description', 'c', 'prescribed_quantity', 'delivered_quantity'])->values()))" title="Ver medicamentos"><i class="bi bi-capsule me-1"></i>Medicamentos <span class="badge text-bg-primary">{{ $consultation->medications->count() }}</span></button></td>
        </tr>@empty<tr><td colspan="7" class="text-center text-muted py-5">No hay consultas con médico asignado para los filtros seleccionados.</td></tr>@endforelse</tbody>
    </table></div></div>
    @if($consultations->hasPages())<div class="mt-3">{{ $consultations->links() }}</div>@endif

    <div class="modal fade" tabindex="-1" x-ref="prescriptionModal" aria-labelledby="prescriptionModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><div><h5 class="modal-title" id="prescriptionModalTitle"><i class="bi bi-capsule me-2"></i>Medicamentos de la receta</h5><small class="text-muted" x-text="patient"></small></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body p-0"><div class="table-responsive"><table class="table align-middle mb-0"><thead class="table-light"><tr><th>Código FUA</th><th>Medicamento</th><th>Concentración</th><th class="text-center">Prescrita</th><th class="text-center">Entregada</th></tr></thead><tbody><template x-for="(medication, index) in medications" :key="index"><tr><td x-text="medication.fua_code || '—'"></td><td class="fw-semibold" x-text="medication.description"></td><td x-text="medication.c || '—'"></td><td class="text-center" x-text="medication.prescribed_quantity"></td><td class="text-center" x-text="medication.delivered_quantity"></td></tr></template><tr x-show="medications.length === 0"><td colspan="5" class="text-center text-muted py-4">La receta no tiene medicamentos registrados.</td></tr></tbody></table></div></div>
        </div></div>
    </div>
</div>
<style>
    .audit-consultation-filters { padding: .75rem; }
    .audit-consultation-filters .form-label { margin-bottom: .25rem; font-size: .75rem; }
    .audit-consultation-filters .form-control,.audit-consultation-filters .form-select,.audit-consultation-filters .btn { min-height: 32px; padding-top: .25rem; padding-bottom: .25rem; font-size: .8rem; }
    .consultation-audit-table { font-size: .86rem; }
    .consultation-audit-table td { padding-top: .75rem; padding-bottom: .75rem; }
</style>
<script>
document.addEventListener('alpine:init', () => Alpine.data('consultationAudit', () => ({
    patient: '', medications: [],
    openPrescription(patient, medications) { this.patient = patient || 'Paciente'; this.medications = medications; bootstrap.Modal.getOrCreateInstance(this.$refs.prescriptionModal).show(); },
    handleShortcut(event) {
        if (!['Digit1', 'Numpad1'].includes(event.code) || event.ctrlKey || event.altKey || event.metaKey || ['INPUT', 'SELECT', 'TEXTAREA'].includes(event.target.tagName) || event.target.isContentEditable) return;
        const button = this.$root.querySelector('.prescription-button');
        if (button) { event.preventDefault(); button.click(); }
    }
})))
</script>
@endsection
