@extends('layouts.panel')

@section('content')

    <div class="card shadow">
        <div class="card-header border-0 mb--4">
            <div class="row align-items-center">
                <div class="col-lg">
                    <h3 class="mb-0">NUMERACION PARA FUAS</h3>
                </div>

                <div class="col text-right">
                    <a href="{{ url('numerations/create') }}" class="btn btn-sm btn-success">
                        Nueva numeracion
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body mb--4">
            <form action="{{ url('numerations') }}" method="GET">
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
                    <th scope="col">Serie FUA</th>
                    <th scope="col">Fecha fua</th>
                    <th scope="col">Opciones</th>

                </tr>
                </thead>
                <tbody class="text-center">
                @foreach ($numerations as $numeration)
                    <tr>
                        <th scope="row">
                            {{$numeration->id}}
                        </th>
                        <td>
                            {{$numeration->fua}}
                        </td>

                        <td>
                            {{$numeration->created_at->format('Y-m-d')}}
                        </td>
                        <td>

                            <form action="{{ url('/numerations/'.$numeration->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que desea eliminar la orden del paciente {{ $numeration->fua }}?, ya que al borrarlo eliminara los registros que tenga del día {{ $numeration->created_at }}');" type="submit">Eliminar</button>

                                <a href="{{ url('/numerations/'.$numeration->id.'/edit') }}" class="btn btn-sm btn-primary">Editar</a>

                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $numerations->appends($_GET)->links() }}
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
@endsection
