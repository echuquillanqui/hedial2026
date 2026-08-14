@extends('layouts.panel')

@section('styles')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">

@endsection

@section('content')

    <form action="{{ url('corrections') }}" method="POST">
        <div class="card shadow">
            <div class="card-header border-0">
                @if (session('notification'))
                    <div class="alert alert-success" role="alert">
                        <span class="alert-icon"><i class="ni ni-curved-next"></i></span>
                        {{ session('notification') }}
                    </div>
                @endif
                <div class="row align-items-center">
                    <div class="col">
                        <h3 class="mb-0">Nueva Subsanacion</h3>
                    </div>
                    <div class="col text-right">
                        <a href="{{ url('corrections') }}" class="btn btn-sm btn-default">
                            Cancelar y volver
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @csrf
                <div class="row mt--4 text-center">

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="serie_fua">N° FUA SUBSANACION</label>
                        <input type="text" name="serie_fua" class="form-control" value="{{ $sig_fua }}" >
                    </div>

                    <div class="form-group col-sm-12 col-lg-4">
                        <label for="order">FUA A SUBSANAR</label>
                        <select name="order" id="obtenerfua" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                            <option value="">[BUSQUE FUA A SUBSANAR]</option>
                            @foreach ($orders as $order)
                                <option value="{{ $order->id }}">{{ $order->n_fua }} - {{ $order->patient->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="order_id">ORDEN</label>
                        <input type="text" name="order_id" id="order_id" class="form-control" readonly>
                    </div>


                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="date_order">FECHA DE ORDEN</label>
                        <input type="text" name="date_order" id="date_order" class="form-control" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                    </div>

                </div>


                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
    <script src="{{ asset('js/subsanacion.js') }}"></script>
@endsection
