@extends('layouts.app')

@section('content')
<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>No se pudieron guardar los cambios:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Catálogo de exámenes FISSAL</h3>
            <p class="text-muted mb-0">Edita la configuración de los 24 exámenes incluidos en el catálogo.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('catalog.update') }}">
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Área</th>
                                <th>Examen</th>
                                <th>Unidad</th>
                                <th>Valor de referencia</th>
                                <th>Tipo</th>
                                <th class="pe-3">Frecuencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tests as $test)
                                <tr>
                                    <td class="ps-3" style="min-width: 180px">
                                        <input type="text" name="tests[{{ $test->id }}][area]" class="form-control form-control-sm" value="{{ old("tests.{$test->id}.area", $test->area->name) }}" required list="catalog-areas">
                                    </td>
                                    <td style="min-width: 240px">
                                        <input type="text" name="tests[{{ $test->id }}][name]" class="form-control form-control-sm" value="{{ old("tests.{$test->id}.name", $test->name) }}" required>
                                    </td>
                                    <td style="min-width: 110px">
                                        <input type="text" name="tests[{{ $test->id }}][unit]" class="form-control form-control-sm" value="{{ old("tests.{$test->id}.unit", $test->unit) }}">
                                    </td>
                                    <td style="min-width: 260px">
                                        <input type="text" name="tests[{{ $test->id }}][reference_value]" class="form-control form-control-sm" value="{{ old("tests.{$test->id}.reference_value", $test->reference_value) }}">
                                    </td>
                                    <td style="min-width: 110px">
                                        <select name="tests[{{ $test->id }}][type]" class="form-select form-select-sm" required>
                                            @foreach(['number' => 'Número', 'text' => 'Texto', 'select' => 'Selección'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old("tests.{$test->id}.type", $test->type) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="pe-3" style="min-width: 140px">
                                        <select name="tests[{{ $test->id }}][frequency]" class="form-select form-select-sm" required>
                                            @foreach(['M' => 'Mensual', 'B' => 'Bimestral', 'T' => 'Trimestral', 'S' => 'Semestral'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old("tests.{$test->id}.frequency", $test->frequency) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No hay exámenes FISSAL registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <datalist id="catalog-areas">
            @foreach($areaNames as $areaName)
                <option value="{{ $areaName }}"></option>
            @endforeach
        </datalist>

        @if($tests->isNotEmpty())
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-success rounded-pill px-4">Guardar cambios</button>
            </div>
        @endif
    </form>
</div>
@endsection
