@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Se encontraron errores:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <form method="POST" action="{{ route('catalog.store') }}" id="catalog-form">
                @csrf

                <div class="row g-0">
                    <div class="col-lg-3 border-end bg-light-subtle">
                        <div class="p-4 h-100">
                            <div class="catalog-ribbon mb-4">
                                <span>Configuración</span>
                            </div>

                            <h5 class="mb-3">Datos del catálogo</h5>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Área</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="area_name" class="form-control rounded-start-3" required value="{{ old('area_name') }}" placeholder="Ej: Bioquímica" data-area-input>
                                    <button type="button" class="btn btn-outline-danger px-2" title="Limpiar área" data-clear-area>🗑</button>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Perfil</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="profile_name" class="form-control rounded-start-3" value="{{ old('profile_name') }}" placeholder="Opcional. Ej: Perfil renal" data-profile-input>
                                    <button type="button" class="btn btn-outline-danger px-2" title="Limpiar perfil" data-clear-profile>🗑</button>
                                </div>
                            </div>

                            <div class="small text-muted">
                                Si indicas un perfil, los exámenes creados se asignarán automáticamente a ese perfil. Si no, quedarán solo en el área.
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-9">
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                                <div>
                                    <h4 class="mb-1">Exámenes</h4>
                                    <p class="text-muted mb-0">Agrega exámenes y configura sus opciones según el tipo.</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('catalog.list') }}" class="btn btn-outline-secondary rounded-pill px-4">Ver listado</a>
                                    <button type="button" class="btn btn-primary rounded-pill px-4" id="add-test-btn">+ Agregar examen</button>
                                </div>
                            </div>

                            <div id="tests-container" class="d-grid gap-3 mb-4"></div>

                            
                            <div class="mt-4">
                                <button class="btn btn-success px-4 rounded-pill">Guardar catálogo</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .catalog-ribbon {
        width: fit-content;
        color: #fff;
        background: linear-gradient(135deg, #0d6efd, #3d8bfd);
        padding: .45rem .95rem;
        border-radius: 0 .75rem .75rem 0;
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.25);
        font-weight: 600;
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const testsContainer = document.getElementById('tests-container');
    const addTestBtn = document.getElementById('add-test-btn');
    let testIndex = 0;

    function makeOptionRow(idx, optionIdx, label = '', value = '') {
        return `<div class="row g-1 mb-1 align-items-center" data-option-row>
            <div class="col-md-5"><input class="form-control form-control-sm rounded-3" name="tests[${idx}][options][${optionIdx}][label]" placeholder="Etiqueta" value="${label}"></div>
            <div class="col-md-6"><input class="form-control form-control-sm rounded-3" name="tests[${idx}][options][${optionIdx}][value]" placeholder="Valor" value="${value}"></div>
            <div class="col-md-1 d-grid"><button type="button" class="btn btn-outline-danger btn-sm py-0" title="Quitar opción" data-remove-option>✕</button></div>
        </div>`;
    }

    function addTestCard() {
        const idx = testIndex++;
        const card = document.createElement('div');
        card.className = 'border rounded-3 bg-white p-2';
        card.dataset.testCard = '1';
        card.dataset.index = idx;
        card.dataset.optionIndex = 0;

        card.innerHTML = `
            <div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="text-primary small">Examen #${idx + 1}</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" title="Eliminar examen" data-remove-test>🗑</button>
                </div>
                <div class="row g-1">
                    <div class="col-md-4"><input class="form-control form-control-sm rounded-3" name="tests[${idx}][name]" placeholder="Nombre" data-test-name required></div>
                    <div class="col-md-2"><input class="form-control form-control-sm rounded-3" name="tests[${idx}][unit]" placeholder="Unidad"></div>
                    <div class="col-md-3"><input class="form-control form-control-sm rounded-3" name="tests[${idx}][reference_value]" placeholder="Valor de referencia"></div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm rounded-3" name="tests[${idx}][type]" data-test-type required>
                            <option value="number">number</option>
                            <option value="text">text</option>
                            <option value="select">select</option>
                        </select>
                    </div>
                </div>
                <div class="mt-2 d-none" data-options-wrapper>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>Opciones del select</strong>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0" data-add-option>+ Opción</button>
                    </div>
                    <input class="form-control form-control-sm mb-1" placeholder="Ingreso rápido: POSITIVO;NEGATIVO" data-options-quick>
                    <div class="p-2 bg-light rounded-3" data-options-list></div>
                </div>
            </div>`;

        testsContainer.appendChild(card);
    }

    addTestBtn.addEventListener('click', addTestCard);


    document.addEventListener('click', (event) => {
        if (event.target.matches('[data-clear-area]')) {
            const input = document.querySelector('[data-area-input]');
            if (input) input.value = '';
        }

        if (event.target.matches('[data-clear-profile]')) {
            const input = document.querySelector('[data-profile-input]');
            if (input) input.value = '';
        }
    });


    testsContainer.addEventListener('change', (event) => {
        if (event.target.matches('[data-test-type]')) {
            const card = event.target.closest('[data-test-card]');
            const wrapper = card.querySelector('[data-options-wrapper]');
            wrapper.classList.toggle('d-none', event.target.value !== 'select');
        }
    });

    testsContainer.addEventListener('blur', (event) => {
        if (!event.target.matches('[data-options-quick]')) return;
        const raw = event.target.value.trim();
        if (!raw) return;

        const card = event.target.closest('[data-test-card]');
        const list = card.querySelector('[data-options-list]');
        const idx = card.dataset.index;
        const chunks = raw.split(/[;,\n]+/).map(v => v.trim()).filter(Boolean);

        chunks.forEach((value) => {
            const optionIdx = Number(card.dataset.optionIndex);
            list.insertAdjacentHTML('beforeend', makeOptionRow(idx, optionIdx, value, value));
            card.dataset.optionIndex = optionIdx + 1;
        });

        event.target.value = '';
    }, true);

    testsContainer.addEventListener('click', (event) => {
        const card = event.target.closest('[data-test-card]');
        if (!card) return;

        if (event.target.matches('[data-remove-test]')) {
            card.remove();
            return;
        }

        if (event.target.matches('[data-add-option]')) {
            const list = card.querySelector('[data-options-list]');
            const idx = card.dataset.index;
            const optionIdx = Number(card.dataset.optionIndex);
            list.insertAdjacentHTML('beforeend', makeOptionRow(idx, optionIdx));
            card.dataset.optionIndex = optionIdx + 1;
            return;
        }

        if (event.target.matches('[data-options-quick]')) {
            return;
        }

        if (event.target.matches('[data-remove-option]')) {
            event.target.closest('[data-option-row]').remove();
        }
    });

    addTestCard();
})();
</script>
@endpush
