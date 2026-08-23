@extends('layouts.app')

@section('content')
<style>
    .materials-page { --materials-primary: #1859d8; --materials-ink: #17233c; max-width: 1380px; }
    .materials-hero { background: linear-gradient(120deg, #123c93 0%, #1769e0 72%, #3986ee 100%); border-radius: 18px; box-shadow: 0 12px 30px rgba(18, 60, 147, .18); color: #fff; overflow: hidden; padding: 1.5rem 1.75rem; position: relative; }
    .materials-hero::after { background: rgba(255,255,255,.08); border-radius: 50%; content: ''; height: 190px; position: absolute; right: -45px; top: -90px; width: 190px; }
    .materials-hero .hero-icon { align-items: center; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.2); border-radius: 14px; display: flex; font-size: 1.55rem; height: 52px; justify-content: center; width: 52px; }
    .materials-hero p { color: rgba(255,255,255,.78); }
    .materials-tabs { background: #fff; border: 1px solid #e5eaf2; border-radius: 14px; box-shadow: 0 5px 18px rgba(31, 48, 82, .06); padding: .4rem; }
    .materials-tabs .nav-link { border-radius: 10px; color: #5e6b82; font-size: .86rem; font-weight: 700; padding: .7rem 1rem; white-space: nowrap; }
    .materials-tabs .nav-link.active { background: #eaf2ff; color: var(--materials-primary); }
    .module-card { border: 1px solid #e8edf4 !important; border-radius: 14px; box-shadow: 0 6px 20px rgba(31, 48, 82, .07) !important; overflow: hidden; }
    .module-card .card-header { border-bottom-color: #edf0f5; padding: .9rem 1.1rem; }
    .section-title { color: var(--materials-primary); font-size: .78rem; font-weight: 800; letter-spacing: .035em; text-transform: uppercase; }
    .label-mini { color: #657188; display: block; font-size: .69rem; font-weight: 800; letter-spacing: .025em; margin-bottom: 5px; text-transform: uppercase; }
    .materials-page .form-control, .materials-page .form-select, .materials-page .select2-selection { border-color: #dce3ed !important; }
    .materials-page .table thead th { color: #667188; font-size: .7rem; letter-spacing: .025em; padding-bottom: .7rem; padding-top: .7rem; text-transform: uppercase; }
    .materials-page .table tbody td { border-color: #edf0f5; padding-bottom: .7rem; padding-top: .7rem; }
    @media (max-width: 767.98px) { .materials-hero { padding: 1.2rem; } .materials-tabs { flex-wrap: nowrap; overflow-x: auto; } }
</style>

<div class="container-fluid materials-page px-lg-3">
    <header class="materials-hero mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 position-relative" style="z-index: 1;">
            <div class="d-flex align-items-center gap-3">
                <div class="hero-icon"><i class="bi bi-box-seam"></i></div>
                <div>
                    <div class="small fw-semibold text-uppercase opacity-75 mb-1">Área clínica · Hemodiálisis</div>
                    <h3 class="m-0 fw-bold">Materiales extra</h3>
                    <p class="mb-0 mt-1 small">Controla insumos, costos y consumo por paciente desde un solo lugar.</p>
                </div>
            </div>
            <a class="btn btn-light btn-sm fw-bold px-3 py-2 text-primary" href="{{ route('extra-materials.report.monthly', ['month' => request('month', $month)]) }}">
                <i class="bi bi-file-earmark-excel me-1"></i> Exportar reporte
            </a>
        </div>
    </header>

    <ul class="nav nav-pills materials-tabs mb-3 gap-1" aria-label="Secciones de materiales">
        <li class="nav-item"><a class="nav-link {{ $view === 'resumen' ? 'active' : '' }}" href="{{ route('extra-materials.index', array_merge(request()->except('view'), ['view' => 'resumen'])) }}"><i class="bi bi-grid me-1"></i> Resumen</a></li>
        <li class="nav-item"><a class="nav-link {{ $view === 'extras' ? 'active' : '' }}" href="{{ route('extra-materials.index', array_merge(request()->except('view'), ['view' => 'extras'])) }}"><i class="bi bi-plus-circle me-1"></i> Registrar extras</a></li>
        <li class="nav-item"><a class="nav-link {{ $view === 'base' ? 'active' : '' }}" href="{{ route('extra-materials.index', array_merge(request()->except('view'), ['view' => 'base'])) }}"><i class="bi bi-boxes me-1"></i> Materiales base</a></li>
        <li class="nav-item"><a class="nav-link {{ $view === 'consumo' ? 'active' : '' }}" href="{{ route('extra-materials.index', array_merge(request()->except('view'), ['view' => 'consumo'])) }}"><i class="bi bi-lightning-charge me-1"></i> Consumo automático</a></li>
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
