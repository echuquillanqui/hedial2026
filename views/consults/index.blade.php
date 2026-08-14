@extends('layouts.panel')

@section('content')

<div class="card shadow">
  <div class="card-header border-0 mb--6">
    <div class="row align-items-center">
      <div class="col-lg">
        <h3 class="mb-5">LISTADO DE CONSULTAS EXTERNAS</h3>
      </div>

      <div class="col text-right">
        <a href="{{ url('consults/create') }}" class="btn btn-md btn-success">
          Nueva Consulta
        </a>
      </div>

    </div>
  </div>

  <div class="card-body mb--4">
    <form action="{{ url('consults') }}" method="GET">
      <div class="row">

        <div class="form-group col-lg-4">
            <div class="input-group">
            <div class="input-group-prepend">
            <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
            </div>
            <input class="form-control datepicker" placeholder="Seleccionar fecha"
                id="created_at" name="created_at" type="text"
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
            <th scope="col">Fecha y hora de creación</th>
            <th>Opciones</th>
        </tr>
      </thead>
      <tbody class="text-center">
        @foreach ($consults as $consult)
        <tr>
          <th scope="row">
            {{$consult->id}}
          </th>
          <td>
            {{$consult->patient->name}}
          </td>

          <td>
            {{$consult->created_at}}
          </td>

          <td>

            <form action="{{ url('/consult/'.$consult->id) }}" method="POST">
              @csrf
              @method('DELETE')
                <button class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que desea eliminar la orden del paciente {{ $consult->patient->name }}?, ya que al borrarlo eliminara los registros que tenga del día {{ $consult->created_at }}');" type="submit">Eliminar</button>

                <a href="{{ url('/consult/'.$consult->id.'/edit') }}" class="btn btn-sm btn-primary">Editar</a>

            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-body">
    {{ $consults->appends($_GET)->links() }}
  </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
@endsection
