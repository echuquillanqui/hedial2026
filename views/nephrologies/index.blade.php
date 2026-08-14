@extends('layouts.panel')

@section('content')

    <div class="card shadow">
        <div class="card-header border-0 mb--4">
            <div class="row align-items-center">
                <div class="col-lg">
                    <h3 class="mb-0">CONSULTAS NEFROLOGICAS</h3>
                </div>
            </div>
        </div>

        <div class="card-body mb--4">
            <form action="{{ url('nephrologies') }}" method="GET">
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
                    <th scope="col">Paciente</th>
                    <th scope="col">N° FUA</th>
                    <th scope="col">Fecha de orden</th>
                    <th scope="col">Fecha y hora de creación</th>
                    <th>Opciones</th>
                </tr>
                </thead>
                <tbody class="text-center">
                @foreach ($nephrologies as $nephrology)
                    <tr>
                        <th scope="row">
                            {{$nephrology->id}}
                        </th>
                        <td>
                            {{$nephrology->patient->name}}, {{$nephrology->patient->dni}}
                        </td>
                        <td>
                            {{$nephrology->order->n_fua}}
                        </td>
                        <td>
                            {{$nephrology->order->date_order}}
                        </td>

                        <td>
                            {{$nephrology->created_at}}
                        </td>

                        <td>

                            <form action="{{ url('/nephrologies/'.$nephrology->id) }}" method="POST">
                                @csrf
                                @method('DELETE')

                                <a href="{{ url('/nephrologies/'.$nephrology->id.'/edit') }}" class="btn btn-sm btn-primary" target="_blank">Editar</a>

                                <a href="{{ url('/nephrologies/'.$nephrology->id.'/consult') }}" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fas fa-file-pdf"></i></a>
                                <a href="{{ url('/nephrologies/'.$nephrology->id.'/fuaconsulta') }}" class="btn btn-sm btn-outline-warning" target="_blank"><i class="fas fa-bookmark"></i></a>

                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $nephrologies->appends($_GET)->links() }}
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
@endsection
