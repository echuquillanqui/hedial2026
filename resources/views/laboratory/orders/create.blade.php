@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-body">
            <h4>Generar orden de laboratorio</h4>
            <form method="POST" action="{{ route('laboratory.orders.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Paciente</label>
                        <select id="patient_search" class="form-select" required></select>
                        <input type="hidden" name="patient_id" id="patient_id" value="{{ old('patient_id') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Solicitado por</label>
                        <input type="text" name="requested_by" class="form-control" value="{{ old('requested_by') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Perfiles</label>
                        <select id="profile_select" class="form-select" multiple>
                            @foreach($profiles as $profile)
                                <option value="{{ $profile->id }}" data-tests='@json($profile->tests->pluck("id"))'>{{ $profile->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Exámenes individuales</label>
                        <select name="test_ids[]" id="test_ids" class="form-select" multiple required>
                            @foreach($tests as $test)
                                <option value="{{ $test->id }}">{{ $test->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary mt-3">Guardar orden</button>
            </form>
        </div>
    </div>
</div>

<script>
$(function () {
    $('#patient_search').select2({
        placeholder: 'Buscar por DNI, nombres o apellidos',
        minimumInputLength: 2,
        ajax: {
            url: '{{ route('patients.search') }}',
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data.results })
        }
    }).on('select2:select', function (e) {
        $('#patient_id').val(e.params.data.id);
    });

    $('#test_ids, #profile_select').select2();

    $('#profile_select').on('change', function () {
        const selectedTests = new Set($('#test_ids').val() || []);
        $('#profile_select option:selected').each(function () {
            (JSON.parse($(this).attr('data-tests')) || []).forEach(id => selectedTests.add(String(id)));
        });
        $('#test_ids').val(Array.from(selectedTests)).trigger('change');
    });
});
</script>
@endsection
