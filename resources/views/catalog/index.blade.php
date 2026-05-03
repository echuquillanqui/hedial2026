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

    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="mb-3">Catálogo</h4>
            <form method="POST" action="{{ route('catalog.store') }}" id="catalog-form">
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Área</label>
                        <input type="text" name="area_name" class="form-control" required value="{{ old('area_name') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Perfil</label>
                        <input type="text" name="profile_name" class="form-control" required value="{{ old('profile_name') }}">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Exámenes</h5>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-test-btn">Agregar examen</button>
                </div>
                <div id="tests-container" class="d-grid gap-3"></div>

                <hr>
                <h5>Asignación de exámenes al perfil</h5>
                <div id="profile-tests-list" class="row g-2"></div>

                <div class="mt-4">
                    <button class="btn btn-primary">Guardar catálogo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const testsContainer = document.getElementById('tests-container');
    const addTestBtn = document.getElementById('add-test-btn');
    const profileTestsList = document.getElementById('profile-tests-list');
    let testIndex = 0;

    function renderProfileCheckboxes() {
        profileTestsList.innerHTML = '';
        const cards = testsContainer.querySelectorAll('[data-test-card]');

        cards.forEach((card) => {
            const idx = card.dataset.index;
            const nameInput = card.querySelector('[data-test-name]');
            const label = (nameInput.value || `Examen #${Number(idx) + 1}`).trim();

            const col = document.createElement('div');
            col.className = 'col-md-4';
            col.innerHTML = `
                <div class="form-check border rounded p-2 bg-light">
                    <input class="form-check-input" type="checkbox" name="profile_tests[]" value="${idx}" id="profile_test_${idx}">
                    <label class="form-check-label" for="profile_test_${idx}">${label}</label>
                </div>`;
            profileTestsList.appendChild(col);
        });
    }

    function makeOptionRow(idx, optionIdx) {
        return `<div class="row g-2 mb-2" data-option-row>
            <div class="col-md-5"><input class="form-control" name="tests[${idx}][options][${optionIdx}][label]" placeholder="Label"></div>
            <div class="col-md-5"><input class="form-control" name="tests[${idx}][options][${optionIdx}][value]" placeholder="Value"></div>
            <div class="col-md-2"><button type="button" class="btn btn-outline-danger w-100" data-remove-option>Quitar</button></div>
        </div>`;
    }

    function addTestCard() {
        const idx = testIndex++;
        const card = document.createElement('div');
        card.className = 'card border';
        card.dataset.testCard = '1';
        card.dataset.index = idx;
        card.dataset.optionIndex = 0;

        card.innerHTML = `
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <strong>Examen #${idx + 1}</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove-test>Eliminar</button>
                </div>
                <div class="row g-2">
                    <div class="col-md-4"><input class="form-control" name="tests[${idx}][name]" placeholder="Nombre" data-test-name required></div>
                    <div class="col-md-2"><input class="form-control" name="tests[${idx}][unit]" placeholder="Unidad"></div>
                    <div class="col-md-3"><input class="form-control" name="tests[${idx}][reference_value]" placeholder="Valor de referencia"></div>
                    <div class="col-md-3">
                        <select class="form-select" name="tests[${idx}][type]" data-test-type required>
                            <option value="number">number</option>
                            <option value="text">text</option>
                            <option value="select">select</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 d-none" data-options-wrapper>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Opciones del select</strong>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-add-option>Agregar opción</button>
                    </div>
                    <div data-options-list></div>
                </div>
            </div>`;

        testsContainer.appendChild(card);
        renderProfileCheckboxes();
    }

    addTestBtn.addEventListener('click', addTestCard);

    testsContainer.addEventListener('input', (event) => {
        if (event.target.matches('[data-test-name]')) {
            renderProfileCheckboxes();
        }
    });

    testsContainer.addEventListener('change', (event) => {
        if (event.target.matches('[data-test-type]')) {
            const card = event.target.closest('[data-test-card]');
            const wrapper = card.querySelector('[data-options-wrapper]');
            wrapper.classList.toggle('d-none', event.target.value !== 'select');
        }
    });

    testsContainer.addEventListener('click', (event) => {
        const card = event.target.closest('[data-test-card]');
        if (!card) return;

        if (event.target.matches('[data-remove-test]')) {
            card.remove();
            renderProfileCheckboxes();
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

        if (event.target.matches('[data-remove-option]')) {
            event.target.closest('[data-option-row]').remove();
        }
    });

    addTestCard();
})();
</script>
@endpush
