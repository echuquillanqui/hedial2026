@extends('layouts.panel')

@section('content')

    <form action="{{ url('numerations') }}" method="POST">
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
                        <h3 class="mb-0">Nueva Numeracion FUA</h3>
                    </div>
                    <div class="col text-right">
                        <a href="{{ url('numerations') }}" class="btn btn-sm btn-default">
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
                <div class="row mt--4">

                    <div class="form-group col-sm-12 col-lg-6">
                        <label for="fua">SERIE DE INICIO</label>
                        <input type="text" name="fua" class="form-control" value="">
                    </div>

                </div>


                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </form>
@endsection

