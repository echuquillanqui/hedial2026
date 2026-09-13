@extends('layouts.app')

@section('content')
<div class="container py-3">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><strong>No se pudieron guardar los cambios:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="mb-3">
        <h3 class="mb-1">Catálogo de medicamentos</h3>
        <p class="text-muted mb-0">Complete manualmente los códigos FUA. Los medicamentos estarán disponibles por nombre o código en la receta nefrológica.</p>
    </div>
    <form method="POST" action="{{ route('medication-catalog.update') }}">@csrf @method('PUT')
        <div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th class="ps-3">Código FUA</th><th>Producto farmacéutico</th><th>Cantidad referencial</th><th>Frecuencia</th><th class="pe-3">Indicación</th></tr></thead>
            <tbody>@foreach($medications as $medication)<tr>
                <td class="ps-3" style="min-width:140px"><input class="form-control form-control-sm" name="medications[{{ $medication->id }}][code]" value="{{ old("medications.{$medication->id}.code", $medication->code) }}" placeholder="Completar código"></td>
                <td style="min-width:360px"><input required class="form-control form-control-sm" name="medications[{{ $medication->id }}][name]" value="{{ old("medications.{$medication->id}.name", $medication->name) }}"></td>
                <td style="min-width:150px"><input required type="number" min="1" class="form-control form-control-sm" name="medications[{{ $medication->id }}][reference_quantity]" value="{{ old("medications.{$medication->id}.reference_quantity", $medication->reference_quantity) }}"></td>
                <td style="min-width:140px"><input required class="form-control form-control-sm" name="medications[{{ $medication->id }}][frequency]" value="{{ old("medications.{$medication->id}.frequency", $medication->frequency) }}"></td>
                <td class="pe-3" style="min-width:320px"><textarea rows="2" class="form-control form-control-sm" name="medications[{{ $medication->id }}][indication]" placeholder="Sin indicación registrada">{{ old("medications.{$medication->id}.indication", $medication->indication) }}</textarea></td>
            </tr>@endforeach</tbody>
        </table></div></div>
        <div class="text-end mt-3"><button class="btn btn-success rounded-pill px-4">Guardar cambios</button></div>
    </form>
</div>
@endsection
