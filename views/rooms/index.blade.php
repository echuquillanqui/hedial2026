@extends('layouts.panel')

@section('content')

<div class="card shadow">
  <div class="card-header border-0">
    <div class="row align-items-center">
      <div class="col">
        <h3 class="mb-0">Salas</h3>
      </div>
      <div class="col text-right">
        <a href="{{ url('rooms/create') }}" class="btn btn-sm btn-success">
          Nueva Sala
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
  

  <div class="table-responsive">
    <!-- Projects table -->
    <table class="table align-items-center table-flush">
      <thead class="thead-light">
        <tr>
          <th scope="col">Nombres</th>
          <th scope="col">Descripción</th>
          <th scope="col">Opciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($rooms as $room)
        <tr>
          <th scope="row">
            {{$room->name}}
          </th>
          <td>
            {{$room->description}}
          </td>
          <td>
            
            <form action="{{ url('/rooms/'.$room->id) }}" method="POST">
              @csrf
              @method('DELETE')

              <a href="{{ url('/rooms/'.$room->id.'/edit') }}" class="btn btn-sm btn-primary">Editar</a>

              <button class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que desea eliminar el {{ $room->name }}?');" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
