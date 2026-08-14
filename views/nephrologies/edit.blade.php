@extends('layouts.panel')

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
                    <h3>Paciente: {{ $nephrology->patient->name }} - Fecha: {{ $nephrology->order->date_order }} </h3>
                </div>
                <div class="col text-right">
                    <a href="{{ url('nephrologies') }}" class="btn btn-sm btn-default">
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


            <form action="{{ url('nephrologies/'.$nephrology->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="nav-wrapper mt--5">
                    <ul class="nav nav-pills nav-fill flex-column flex-md-row" id="tabs-icons-text" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link mb-sm-3 mb-md-0 active" id="tabs-icons-text-1-tab" data-toggle="tab" href="#tabs-icons-text-1" role="tab" aria-controls="tabs-icons-text-1" aria-selected="true"><i class="fas fa-file-alt mr-2"></i>ANAMNESIS</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-2-tab" data-toggle="tab" href="#tabs-icons-text-2" role="tab" aria-controls="tabs-icons-text-2" aria-selected="false"><i class="fas fa-medkit mr-2"></i>TRATAMIENTO COMPLEMENTARIO</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-3-tab" data-toggle="tab" href="#tabs-icons-text-3" role="tab" aria-controls="tabs-icons-text-3" aria-selected="false"><i class="fas fa-file-archive mr-2"></i>INDICACIONES DE EXAMENES AUXILIARES</a>
                        </li>
                    </ul>
                </div>
                <div class="card shadow">
                    <div class="card-body">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="tabs-icons-text-1" role="tabpanel" aria-labelledby="tabs-icons-text-1-tab">
                                @include('nephrologies.includes.anamnesis')
                            </div>

                            <div class="tab-pane fade" id="tabs-icons-text-2" role="tabpanel" aria-labelledby="tabs-icons-text-2-tab">
                                @include('nephrologies.includes.tratamientos')
                            </div>

                            <div class="tab-pane fade" id="tabs-icons-text-3" role="tabpanel" aria-labelledby="tabs-icons-text-3-tab">
                                @include('nephrologies.includes.indicaciones')
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-2">Guardar</button>
            </form>



        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('js/imc.js') }}"></script>
    <script src="{{ asset('js/timetto.js') }}"></script>
@endsection

