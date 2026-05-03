@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h4 class="mb-1">Editar área de laboratorio</h4>
            <p class="text-muted">Actualiza el nombre del área para el catálogo de laboratorio.</p>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('catalog.areas.update', $area) }}" class="mt-3">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Nombre del área</label>
                    <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $area->name) }}" required maxlength="255">
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('catalog.list') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
