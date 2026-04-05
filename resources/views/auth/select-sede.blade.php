@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width: 520px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-2">Seleccionar sede</h4>
            <p class="text-muted mb-4">Tiene varias sedes asignadas. Elija una para continuar.</p>

            <form action="{{ route('sede.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sede</label>
                    <select name="sede_id" class="form-select" required>
                        <option value="">Seleccione...</option>
                        @foreach($sedes as $sede)
                            <option value="{{ $sede->id }}">{{ $sede->name }}</option>
                        @endforeach
                    </select>
                    @error('sede_id')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <button class="btn btn-primary w-100">Ingresar</button>
            </form>
        </div>
    </div>
</div>
@endsection
