@extends('layouts.panel')

@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">
@endsection

@section('content')

    <div class="card shadow">
        @if (session('notification'))
            <div class="alert alert-success" role="alert">
                <span class="alert-icon"><i class="ni ni-curved-next"></i></span>
                {{ session('notification') }}
            </div>
        @endif
        <div class="card-header border-0">
            <div class="row align-items-center">
                <div class="col">
                    <h3>RECETA MENSUAL PACIENTE: {{ $recipe->patient->name }} - {{ $recipe->order->date_order }} </h3>
                </div>
                <div class="col text-right">
                    <a href="{{ url('recipes') }}" class="btn btn-sm btn-default">
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

            <form action="{{ url('recipes/'.$recipe->id) }}" method="POST">
                @csrf
                @method('PUT')

                @include('recipes.includes.drop')

                <button type="submit" class="btn btn-primary mt-2">Guardar</button>
            </form>

        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
    <script src="{{ asset('js/recetas.js') }}"></script>
@endsection

