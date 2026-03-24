@extends('layouts.app')

@section('content')
<div class="container-fluid px-md-4 py-4">
    <div class="row align-items-center mb-4 g-3">
        <div class="col-12 col-md-6">
            <h2 class="fw-bold text-dark mb-0">
                <i class="bi bi-file-earmark-medical text-primary me-2"></i>Gestión de Referencias
            </h2>
            <p class="text-muted small mb-0">Busque, filtre y gestione las hojas de referencia SIS y EsSalud.</p>
        </div>

        <div class="col-12 col-md-6 text-md-end">
            <div class="btn-group shadow-sm">
                <button class="btn btn-primary btn-lg dropdown-toggle px-4" data-bs-toggle="dropdown">
                    <i class="bi bi-plus-circle me-2"></i>Nueva Referencia
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><a class="dropdown-item py-2" href="{{ route('referrals.create', ['type' => 'SIS']) }}"><span class="badge bg-success me-2">SIS</span> Formato MINSA</a></li>
                    <li><a class="dropdown-item py-2" href="{{ route('referrals.create', ['type' => 'ESSALUD']) }}"><span class="badge bg-primary me-2">ESS</span> Formato EsSalud</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold text-muted">Búsqueda General</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="searchInput" class="form-control border-start-0" placeholder="DNI, Paciente o Código...">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold text-muted">Desde</label>
                    <input type="date" name="from_date" id="fromDate" class="form-control">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-bold text-muted">Hasta</label>
                    <input type="date" name="to_date" id="toDate" class="form-control">
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button type="button" id="resetFilters" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-primary text-white small fw-bold">
                    <tr>
                        <th class="ps-4 py-3">CÓDIGO / FECHA</th>
                        <th class="py-3">PACIENTE / DOCUMENTO</th>
                        <th class="py-3">SEGURO</th>
                        <th class="py-3">ESTABLECIMIENTO DESTINO</th>
                        <th class="text-end pe-4 py-3">ACCIONES</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @include('referrals.partials.table_rows')
                </tbody>
            </table>
        </div>
        <div id="tableLoader" class="d-none text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Buscando registros...</p>
        </div>
        <div id="paginationContainer" class="bg-white border-top">
            @include('referrals.partials.pagination')
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.getElementById('filterForm');
        const tableBody = document.getElementById('tableBody');
        const loader = document.getElementById('tableLoader');
        const paginationContainer = document.getElementById('paginationContainer');

        const fetchReferrals = (page = 1) => {
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);
            params.set('page', page);

            tableBody.classList.add('opacity-25');
            loader.classList.remove('d-none');

            fetch(`{{ route('referrals.index') }}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(payload => {
                tableBody.innerHTML = payload.rows;
                paginationContainer.innerHTML = payload.pagination;
            })
            .catch(error => console.error('Error:', error))
            .finally(() => {
                tableBody.classList.remove('opacity-25');
                loader.classList.add('d-none');
            });
        };

        let timeout = null;
        document.getElementById('searchInput').addEventListener('keyup', () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchReferrals(1), 300);
        });

        document.getElementById('fromDate').addEventListener('change', () => fetchReferrals(1));
        document.getElementById('toDate').addEventListener('change', () => fetchReferrals(1));

        document.getElementById('resetFilters').addEventListener('click', () => {
            filterForm.reset();
            fetchReferrals(1);
        });

        document.addEventListener('click', (event) => {
            const link = event.target.closest('#paginationContainer .pagination a');
            if (!link) return;

            event.preventDefault();
            const url = new URL(link.href);
            const page = url.searchParams.get('page') || 1;
            fetchReferrals(page);
        });
    });
</script>
@endpush
@endsection
