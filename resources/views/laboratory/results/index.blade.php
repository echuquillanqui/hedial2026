@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h4>Resultados de laboratorio</h4>
    @foreach($orders as $order)
        <div class="card mb-3 shadow-sm">
            <div class="card-header d-flex justify-content-between">
                <strong>{{ $order->patient->surname }} {{ $order->patient->last_name }}, {{ $order->patient->first_name }}</strong>
                <span class="badge {{ $order->status === 'completed' ? 'text-bg-success' : 'text-bg-warning' }}">{{ strtoupper($order->status) }}</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('laboratory.results.update', $order) }}">
                    @csrf
                    @method('PUT')
                    @foreach($order->items as $item)
                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-md-4"><label class="form-label">{{ $item->test->name }}</label></div>
                            <div class="col-md-3"><input class="form-control" name="results[{{ $item->id }}][value]" value="{{ old('results.'.$item->id.'.value', $item->result_value) }}"></div>
                            <div class="col-md-5"><input class="form-control" name="results[{{ $item->id }}][notes]" placeholder="Notas" value="{{ old('results.'.$item->id.'.notes', $item->result_notes) }}"></div>
                        </div>
                    @endforeach
                    <button class="btn btn-outline-primary btn-sm">Guardar resultados</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
