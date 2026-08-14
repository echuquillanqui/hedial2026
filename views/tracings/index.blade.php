@extends('layouts.panel')

@section('content')

<div class="card shadow">
  <div class="card-header border-0">
    <div class="row align-items-center">
      <div class="col">
        <h3 class="mb-0">Pacientes</h3>
      </div>
      <div class="col text-right">
        <a href="{{ url('tracings/create') }}" class="btn btn-sm btn-success">
          Nuevo seguimiento
        </a>
      </div>
    </div>
  </div>
  <div class="card-body">
    @if (session('notification'))
      <div class="alert alert-success" role="alert">
        <span class="alert-icon"><i class="ni ni-curved-next"></i></span>
        {{ session('notification') }}
      </div>
    @endif
  </div>  
  
  <div class="table-responsive mt--4">
    <!-- Projects table -->
    <table class="table align-items-center table-flush">
      <thead class="thead-light">
        <tr>
          <th scope="col">Nombres</th>
          <th scope="col">Sala</th>
          <th scope="col">Turno</th>
          <th scope="col">Creación</th>
          <th scope="col">DNI</th>
          <th scope="col">Hospital de referncia</th>
          <th scope="col">Opciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($tracings as $tracing)
        <tr>
          <th scope="row">
            {{$tracing->patient->name}}
          </th>
          <td>
            {{$tracing->room->name}}
          </td>
          <td>
            {{$tracing->shift->name}}
          </td>

          <td>
            {{$tracing->created_at}}
          </td>

          <td>
            {{$tracing->patient->dni}}
          </td>

          <td>
            {{ $tracing->patient->hosp_origin }}
          </td>

          <td>
            
            <form action="{{ url('/tracings/'.$tracing->id) }}" method="POST">
              @csrf
              @method('DELETE')

              <a href="{{ url('/tracings/'.$tracing->id.'/edit') }}" class="btn btn-sm btn-primary">Editar</a>

              <button class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que desea eliminar el seguimiento del paciente {{ $tracing->patient->name }}?, ya que al borrarlo eliminara los registros que tenga del día {{ $tracing->created_at }}');" type="submit">Eliminar</button>

              <a href="{{ url('/tracings/'.$tracing->id.'/impresion') }}" class="btn btn-sm btn-info" target="_blank">Impresión</a>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-body">
    {{ $tracings->links() }}
  </div>
</div>
@endsection
