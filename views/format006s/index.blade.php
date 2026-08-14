@extends('layouts.panel')

@section('content')

<div class="card shadow">
  <div class="card-header border-0">
    <div class="row align-items-center">
      <div class="col">
        <h3 class="mb-0">Formato 006 Seguimiento</h3>
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
  
  <div class="table-responsive">
    <!-- Projects table -->
    <table class="table align-items-center table-flush">
      <thead class="thead-light">
        <tr>
          <th scope="col">Seguimiento N°</th>
          <th scope="col">Nombre de Paciente</th>
          <th scope="col">Sala</th>
          <th scope="col">Turno</th>
          <th scope="col">Fecha de Creación</th>
          <th scope="col">Opciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($format006s as $format006)
        <tr>
          <th scope="row">
            {{$format006->tracing_id}}
          </th>

          <td>
            {{$format006->tracing->patient->name}}
          </td>

          <td>
            {{$format006->tracing->room->name}}
          </td>

          <td>
            {{$format006->tracing->shift->name}}
          </td>

          <td>
            {{$format006->created_at}}
          </td>
          <td>
            
            <form action="{{ url('/format006s/'.$format006->id) }}" method="POST">
              @csrf
              @method('DELETE')

              <a href="{{ url('/format006s/'.$format006->id.'/edit') }}" class="btn btn-sm btn-primary">Editar</a>

              <button class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que desea eliminar el tratamiento N° {{ $format006->id }}?');" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-body">
    {{ $format006s->appends($_GET)->links() }}
  </div>
</div>
@endsection
