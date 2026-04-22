@extends('layouts.app')

@section('content')
<style>
    .module-card { border-radius: 14px; }
    .section-title { font-size: .8rem; font-weight: 800; text-transform: uppercase; color: #0d6efd; }
    .label-mini { font-size: .7rem; font-weight: 700; text-transform: uppercase; color: #6c757d; margin-bottom: 4px; }
</style>

<div class="container px-0">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="m-0 fw-bold text-primary text-uppercase"><i class="bi bi-box-seam me-2"></i>Materiales por Hemodiálisis</h4>
            <small class="text-muted">Módulos separados para resumen, registro de extras, base y consumo automático.</small>
        </div>
        <a class="btn btn-success btn-sm fw-bold" href="{{ route('extra-materials.report.monthly', ['month' => request('month', $month)]) }}">
            <i class="bi bi-file-earmark-excel me-1"></i>Exportar mensual
        </a>
    </div>

    <ul class="nav nav-pills mb-3 gap-2">
        <li class="nav-item"><a class="nav-link {{ $view === 'resumen' ? 'active' : '' }}" href="{{ route('extra-materials.index', array_merge(request()->except('view'), ['view' => 'resumen'])) }}">Resumen</a></li>
        <li class="nav-item"><a class="nav-link {{ $view === 'extras' ? 'active' : '' }}" href="{{ route('extra-materials.index', array_merge(request()->except('view'), ['view' => 'extras'])) }}">Materiales extra</a></li>
        <li class="nav-item"><a class="nav-link {{ $view === 'base' ? 'active' : '' }}" href="{{ route('extra-materials.index', array_merge(request()->except('view'), ['view' => 'base'])) }}">Materiales base</a></li>
        <li class="nav-item"><a class="nav-link {{ $view === 'consumo' ? 'active' : '' }}" href="{{ route('extra-materials.index', array_merge(request()->except('view'), ['view' => 'consumo'])) }}">Consumo automático</a></li>
    </ul>

    @include('atenciones.materiales.partials.filters')

    @if($view === 'resumen')
        @include('atenciones.materiales.partials.resumen')
    @elseif($view === 'extras')
        @include('atenciones.materiales.partials.extras')
    @elseif($view === 'base')
        @include('atenciones.materiales.partials.base')
    @else
        @include('atenciones.materiales.partials.consumo')
    @endif
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('.js-patient-select').select2({
            width: '100%',
            placeholder: 'Buscar paciente por nombre o DNI',
            allowClear: true,
        });

        $('.js-warehouse-material-select').select2({
            width: '100%',
            placeholder: 'Buscar material por código, nombre o unidad',
            allowClear: true,
        });

        $('.js-warehouse-material-select').on('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const targetNameId = this.dataset.targetName;
            const targetUnitId = this.dataset.targetUnit;

            if (!targetNameId || !targetUnitId || !selectedOption) {
                return;
            }

            const nameInput = document.getElementById(targetNameId);
            const unitInput = document.getElementById(targetUnitId);

            if (!nameInput || !unitInput) {
                return;
            }

            const selectedName = selectedOption.value || '';
            const selectedUnit = selectedOption.dataset.unit || '';

            nameInput.value = selectedName;

            if (selectedUnit) {
                unitInput.value = selectedUnit;
            }
        });
    });
</script>
@endpush
