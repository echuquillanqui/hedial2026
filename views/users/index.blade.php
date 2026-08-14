@extends('layouts.panel')

@section('content')

<div class="card shadow">
  <div class="card-header border-0">
    <div class="row align-items-center">
      <div class="col">
        <h3 class="mb-0">Usuarios</h3>
      </div>
      <div class="col text-right">
        <a href="{{ url('users/create') }}" class="btn btn-sm btn-success">
          Nuevo usuario
        </a>
      </div>
      <a href="{{ route('users.excel') }}" class="btn btn-sm btn-info">
        Excel
      </a>

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

  <div class="card-body">
    <form action="{{ url('users') }}" method="GET">
      <div class="row">
        <div class="col-lg-4">
          <div class="form-group">
            <label for="name">Nombres y Apellidos</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" autofocus>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="form-group">
            <label for="email">Correo</label>
            <input type="text" name="email" class="form-control" value="{{ old('email') }}">
          </div>
        </div>

        <div class="col-lg-4">
          <div class="form-group">
            <label for="dni">DNI</label>
            <input type="text" name="dni" class="form-control" value="{{ old('dni') }}">
          </div>
        </div>

      </div>
      <button class="btn btn-info btn-sm" type="submit">Buscar</button>
    </form>
  </div>


  <div class="table-responsive">
    <!-- Projects table -->
    <table class="table align-items-center table-flush">
      <thead class="thead-light">
        <tr>
          <th scope="col">Nombres</th>
          <th scope="col">Correo</th>
          <th scope="col">DNI</th>
            <th scope="col">FIRMA</th>
          <th scope="col">Opciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($users as $user)
        <tr>
          <th scope="row">
            {{$user->name}}
          </th>
          <td>
            {{$user->email}}
          </td>
          <td>
            {{$user->dni}}
          </td>

        <td>
                @if($user->image)
                    <img src="{{ asset($user->image) }}" class="img-fluid" height=120" width="250"></img>
                @else
                    <p>No hay imgen que mostrar</p>
                @endif

            </td>

          <td>

            <form action="{{ url('/users/'.$user->id) }}" method="POST">
              @csrf
              @method('DELETE')

              <a href="{{ url('/users/'.$user->id.'/edit') }}" class="btn btn-sm btn-primary">Editar</a>

              <button class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que desea eliminar el {{ $user->name }}?');" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-body">
    {{ $users->links() }}
  </div>
</div>

@endsection
