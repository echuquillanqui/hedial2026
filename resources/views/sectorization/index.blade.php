@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <div><span class="text-uppercase text-primary fw-bold small">Gestión</span><h2 class="mb-1">Sectorización</h2><p class="text-muted mb-0">Edita puesto, máquina y acceso. Puesto y máquina se comparten con las atenciones de enfermería.</p></div>
        <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-success" href="{{ route('sectorization.template') }}"><i class="bi bi-file-earmark-arrow-down me-1"></i>Descargar plantilla</a><form method="GET" class="d-flex gap-2"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por nombre o DNI"><button class="btn btn-outline-primary">Buscar</button>@if(request('search'))<a class="btn btn-outline-secondary" href="{{ route('sectorization.index') }}">Limpiar</a>@endif</form></div>
    </div>
    <div class="small text-muted mb-3">La plantilla incluye secuencia (L-M-V o M-J-S), turno (1–4), DNI, módulo (1–4 o AISLADO), puesto/máquina y accesos (CVCLP, FAV, INJ, CVCL o CVCT). Al importar, el puesto y la máquina se aplicarán con el mismo valor. Elige la fecha de corte en cada secuencia: los cambios de módulo, puesto y máquina rigen desde esa fecha; las atenciones anteriores se conservan.</div>
    <div class="d-flex flex-wrap gap-2 mb-3 small">
        <span class="badge text-bg-danger">Módulo 1 · Puestos 1–5</span><span class="badge text-bg-warning">Módulo 2 · Puestos 6–10</span><span class="badge text-bg-success">Módulo 3 · Puestos 11–15</span>
        <span id="sectorizationStatus" class="text-muted align-self-center ms-auto" aria-live="polite"></span>
    </div>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
        <div id="sectorizationToast" class="toast align-items-center text-bg-success border-0" role="status" aria-live="polite" aria-atomic="true">
            <div class="d-flex"><div class="toast-body" id="sectorizationToastMessage"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button></div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active fw-semibold" id="lmv-tab" data-bs-toggle="tab" data-bs-target="#lmv-panel" type="button" role="tab" aria-controls="lmv-panel" aria-selected="true"><i class="bi bi-calendar-week me-1"></i>Lunes · Miércoles · Viernes</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link fw-semibold" id="mjs-tab" data-bs-toggle="tab" data-bs-target="#mjs-panel" type="button" role="tab" aria-controls="mjs-panel" aria-selected="false"><i class="bi bi-calendar-week me-1"></i>Martes · Jueves · Sábado</button></li>
    </ul>

    <div class="tab-content">
    @foreach(['L-M-V' => ['id' => 'lmv', 'label' => 'Lunes · Miércoles · Viernes'], 'M-J-S' => ['id' => 'mjs', 'label' => 'Martes · Jueves · Sábado']] as $sequence => $tab)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $tab['id'] }}-panel" role="tabpanel" aria-labelledby="{{ $tab['id'] }}-tab" tabindex="0">
            <div class="d-flex align-items-end gap-2 mb-3">
                <div><label class="form-label fw-semibold mb-1" for="cutoff-{{ $tab['id'] }}">Fecha de corte para esta secuencia</label><input type="date" class="form-control form-control-sm sectorization-cutoff" id="cutoff-{{ $tab['id'] }}" data-cutoff-sequence="{{ $sequence }}" min="{{ $minimumCutoff }}" value="{{ $sequenceCutoffs[$sequence] }}"></div>
                <small class="text-muted pb-1">Se aplicará desde la fecha seleccionada en adelante.</small>
            </div>
            <div class="accordion" id="{{ $tab['id'] }}-turns">
            @foreach(['1' => 'danger', '2' => 'warning', '3' => 'success', '4' => 'primary'] as $turn => $turnColor)
                @php($turnPatients = $patientsBySequence[$sequence]->where('turno', $turn)->values())
                @php($turnCollapseId = $tab['id'].'-turn-'.$turn)
                <section class="accordion-item border-0 shadow-sm mb-3 overflow-hidden">
                    <h2 class="accordion-header" id="heading-{{ $turnCollapseId }}">
                        <button class="accordion-button collapsed bg-light fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $turnCollapseId }}" aria-expanded="false" aria-controls="{{ $turnCollapseId }}">
                            <span class="badge text-bg-primary me-2">Turno {{ $turn }}</span><span>{{ $turnPatients->count() }} pacientes</span>
                        </button>
                    </h2>
                    <div id="{{ $turnCollapseId }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $turnCollapseId }}" data-bs-parent="#{{ $tab['id'] }}-turns">
                        <div class="accordion-body bg-light">
                            <div class="d-flex justify-content-end mb-2"><button type="button" class="btn btn-sm btn-outline-primary sectorization-refresh" data-sequence="{{ $sequence }}" data-turn="{{ $turn }}"><i class="bi bi-arrow-clockwise me-1"></i>Recargar este turno</button></div>
                            <div class="sectorization-turn-content" data-sequence="{{ $sequence }}" data-turn="{{ $turn }}">@include('sectorization._turn-table', ['patients' => $turnPatients, 'sequence' => $sequence])</div>
                        </div>
                    </div>
                </section>
            @endforeach
            </div>
        </div>
    @endforeach
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const status = document.getElementById('sectorizationStatus');
    const toastElement = document.getElementById('sectorizationToast');
    const toast = bootstrap.Toast.getOrCreateInstance(toastElement, {delay: 3500});
    const showToast = (message, type = 'success') => {
        toastElement.classList.toggle('text-bg-success', type === 'success');
        toastElement.classList.toggle('text-bg-danger', type === 'error');
        document.getElementById('sectorizationToastMessage').textContent = message;
        toast.show();
    };
    const moduleColors = {'1': 'danger', '2': 'warning', '3': 'success', '4': 'primary', 'AISLADO': 'info', '': 'secondary'};
    const updateModuleColor = (select) => {
        Object.values(moduleColors).forEach((color) => select.classList.remove(`module-color-${color}`));
        select.classList.add(`module-color-${moduleColors[select.value] || 'secondary'}`);
    };
    const save = async (input) => {
        if (input.dataset.savedValue === input.value) return;
        input.disabled = true;
        status.textContent = 'Guardando…';
        try {
            const response = await fetch(@json(route('sectorization.update', ['patient' => '__PATIENT__'])).replace('__PATIENT__', input.dataset.patient), {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({
                    field: input.dataset.field,
                    value: input.value,
                    effective_from: input.closest('.tab-pane')?.querySelector('.sectorization-cutoff')?.value,
                    sequence: input.closest('.tab-pane')?.querySelector('.sectorization-cutoff')?.dataset.cutoffSequence
                })
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'No se pudo guardar.');
            input.dataset.savedValue = input.value;
            status.textContent = result.effective_from
                ? `Cambio guardado; rige desde ${new Date(`${result.effective_from}T00:00:00`).toLocaleDateString('es-PE')}.`
                : 'Cambios guardados.';
            showToast(status.textContent);
            input.classList.add('is-valid');
            setTimeout(() => input.classList.remove('is-valid'), 1200);
        } catch (error) {
            status.textContent = error.message;
            showToast(error.message, 'error');
            input.classList.add('is-invalid');
        } finally { input.disabled = false; }
    };
    const bindFields = (root = document) => root.querySelectorAll('.sectorization-field').forEach((input) => {
        input.dataset.savedValue = input.value;
        if (input.dataset.field === 'modulo') {
            updateModuleColor(input);
            input.addEventListener('change', () => updateModuleColor(input));
        }
        input.addEventListener('change', () => save(input));
        if (input.tagName === 'INPUT') input.addEventListener('blur', () => save(input));
    });
    bindFields();

    document.querySelectorAll('.sectorization-cutoff').forEach((input) => {
        input.dataset.savedValue = input.value;
        input.addEventListener('change', async () => {
            if (!input.value) return;
            input.disabled = true;
            try {
                const response = await fetch(@json(route('sectorization.cutoff')), {
                    method: 'PATCH',
                    headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                    body: JSON.stringify({effective_from: input.value, sequence: input.dataset.cutoffSequence})
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'No se pudo guardar la fecha de corte.');
                input.dataset.savedValue = input.value;
                const dateLabel = new Date(`${result.effective_from}T00:00:00`).toLocaleDateString('es-PE');
                status.textContent = `Fecha de corte guardada: ${dateLabel}.`;
                showToast(status.textContent);
                input.classList.add('is-valid');
                setTimeout(() => input.classList.remove('is-valid'), 1200);
            } catch (error) {
                input.value = input.dataset.savedValue;
                status.textContent = error.message;
                showToast(error.message, 'error');
            } finally {
                input.disabled = false;
            }
        });
    });

    document.querySelectorAll('.sectorization-refresh').forEach((button) => button.addEventListener('click', async () => {
        const content = button.closest('.accordion-body').querySelector('.sectorization-turn-content');
        const originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Cargando…';
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('partial', '1');
            url.searchParams.set('sequence', button.dataset.sequence);
            url.searchParams.set('turn', button.dataset.turn);
            const response = await fetch(url, {headers: {'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest'}});
            if (!response.ok) throw new Error('No se pudo recargar este turno.');
            content.innerHTML = await response.text();
            bindFields(content);
            status.textContent = `Turno ${button.dataset.turn} actualizado.`;
        } catch (error) {
            status.textContent = error.message;
        } finally {
            button.disabled = false;
            button.innerHTML = originalHtml;
        }
    }));
});
</script>
@endsection

<style>
.module-color-danger { border-color: var(--bs-danger) !important; background-color: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); }
.module-color-warning { border-color: var(--bs-warning) !important; background-color: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
.module-color-success { border-color: var(--bs-success) !important; background-color: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
.module-color-primary { border-color: var(--bs-primary) !important; background-color: var(--bs-primary-bg-subtle); color: var(--bs-primary-text-emphasis); }
.module-color-info { border-color: var(--bs-info) !important; background-color: var(--bs-info-bg-subtle); color: var(--bs-info-text-emphasis); }
.module-color-secondary { border-color: var(--bs-secondary) !important; background-color: var(--bs-secondary-bg-subtle); color: var(--bs-secondary-text-emphasis); }
</style>
