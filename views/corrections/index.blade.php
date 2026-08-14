@extends('layouts.panel')

@section('content')

    <div class="card shadow">
        <div class="card-header border-0 mb--4">
            <div class="row align-items-center">
                <div class="col-lg">
                    <h3 class="mb-0">SUBSANACIONES</h3>
                </div>
            </div>
        </div>

        <div class="col text-right">
            <a href="{{ url('corrections/create') }}" class="btn btn-md btn-success">
                Nueva Subsanacion
            </a>
        </div>

        <div class="card-body mb--4">
            <form action="{{ url('corrections') }}" method="GET">
                <div class="row">

                    <div class="form-group col-lg-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
                            </div>
                            <input class="form-control datepicker" placeholder="Seleccionar fecha"
                                   id="date_order" name="date_order" type="text"
                                   value="{{ old('date', date('Y-m-d')) }}"
                                   data-date-format="yyyy-mm-dd"
                            >
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <button class="btn btn-info btn-md" type="submit">Buscar</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <!-- Projects table -->
            <table class="table align-items-center table-flush table-hover">
                <thead class="thead-light text-center">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">PACIENTE</th>
                    <th scope="col">FUA SUBSANACION</th>
                    <th scope="col">FUA A SUBSANAR</th>
                    <th scope="col">FECHA FUA A SUBSANAR</th>
                    <th scope="col">FECHA CREACION</th>
                    <th>Opciones</th>
                </tr>
                </thead>
                <tbody class="text-center">
                @foreach ($corrections as $correction)
                    <tr>
                        <th scope="row">
                            {{$correction->id}}
                        </th>

                        <td>
                            {{$correction->order->patient->name}}
                        </td>

                        <th scope="row">
                            {{$correction->serie_fua}}
                        </th>

                        <td>
                            {{$correction->order->n_fua }}
                        </td>

                        <td>
                            {{$correction->order->date_order}}
                        </td>

                        <td>
                            {{$correction->date_order}}
                        </td>

                        <td>

                            <form action="{{ url('/corrections/'.$correction->id) }}" method="POST">
                                @csrf
                                @method('DELETE')

                                <a href="{{ url('/corrections/'.$correction->id.'/edit') }}" class="btn btn-sm btn-primary" target="_blank">Editar</a>

                                <a href="{{ url('/corrections/'.$correction->id.'/subsanacionfua') }}" class="btn btn-sm btn-outline-default" target="_blank"><i class="fas fa-syringe"></i></a>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $corrections->appends($_GET)->links() }}
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
@endsection
