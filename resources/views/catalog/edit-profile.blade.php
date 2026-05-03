@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Editar perfil</h4>
        <a href="{{ route('catalog.list') }}" class="btn btn-outline-secondary rounded-pill">Volver al listado</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('catalog.profiles.update', $profile) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre del perfil</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $profile->name) }}" required>
                </div>

                <label class="form-label fw-semibold">Selecciona exámenes (individual)</label>
                <div class="row g-2 mb-3">
                    @foreach($tests as $test)
                        <div class="col-md-6">
                            <label class="border rounded px-2 py-2 w-100 d-flex align-items-center gap-2">
                                <input type="checkbox" name="test_ids[]" value="{{ $test->id }}" @checked(in_array($test->id, old('test_ids', $selectedTestIds), true))>
                                <span>
                                    <strong>{{ $test->name }}</strong>
                                    <small class="text-muted d-block">{{ $test->area->name }} · {{ $test->type }}</small>
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>

                <button class="btn btn-success rounded-pill px-4">Guardar cambios</button>
            </form>
        </div>
    </div>
</div>
@endsection
