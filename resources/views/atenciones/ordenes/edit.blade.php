@extends('layouts.app')

@section('content')
<div class="container py-3">
    <div class="section-label">Editar Orden Médica: {{ $order->codigo_unico }}</div>
    
    <div class="card shadow-sm border-0">
        <form action="{{ route('orders.update', $order) }}" method="POST">
            @csrf @method('PUT')
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="data-title">Paciente (No editable)</label>
                        <input type="text" class="form-control bg-light" value="{{ $order->patient->surname }} {{ $order->patient->first_name }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="data-title">Sala</label>
                        <select name="sala" class="form-select @error('sala') is-invalid @enderror">
                            <option value="SALA A" {{ $order->sala == 'SALA A' ? 'selected' : '' }}>SALA A</option>
                            <option value="SALA B" {{ $order->sala == 'SALA B' ? 'selected' : '' }}>SALA B</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="data-title">Turno</label>
                        <input type="text" name="turno" class="form-control" value="{{ $order->turno }}">
                    </div>
                    <div class="col-md-4">
                        <label class="data-title">Horas</label>
                        <input type="number" name="horas_dialisis" class="form-control" value="{{ $order->horas_dialisis }}">
                    </div>
                    <div class="col-md-4">
                        <label class="data-title">Fecha</label>
                        <input type="date" name="fecha_orden" class="form-control" value="{{ $order->fecha_orden }}">
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('orders.index') }}" class="btn btn-link text-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success px-4">ACTUALIZAR ORDEN</button>
            </div>
        </form>
    </div>
</div>
@endsection