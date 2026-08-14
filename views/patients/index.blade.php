@extends('layouts.panel')

@section('content')

<div class="card shadow">
  <div class="card-header border-0">
    <div class="row align-items-center">
      <div class="col">
        <h3 class="mb-0">Pacientes</h3>
      </div>
      <div class="col text-right">
        <a href="{{ url('patients/create') }}" class="btn btn-sm btn-success">
          Nuevo paciente
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

  <div class="card-body">
    <form action="{{ url('patients') }}" method="GET">
      <div class="row">
        <div class="col-lg-4">
          <div class="form-group">
            <label for="name">Nombres y Apellidos</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" autofocus>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="form-group">
            <label for="dni">DNI</label>
            <input type="text" name="dni" class="form-control" value="{{ old('dni') }}">
          </div>
        </div>

        <div class="col-lg-4">
          <div class="form-group">
            <label for="code">Autogenerado</label>
            <input type="text" name="code" class="form-control" value="{{ old('code') }}">
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
          <th scope="col">DNI</th>
          <th scope="col">Fecha de Nacimiento</th>
          <th scope="col">Autogenerado</th>
          <th scope="col">ESTADO</th>
          <th scope="col">Opciones</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($patients as $patient)
        <tr>
          <th scope="row">
            {{$patient->name}}
          </th>
          <td>
            {{$patient->dni}}
          </td>
            <td>
                {{$patient->date_of_birth}}
            </td>
          <td>
            {{$patient->code}}
          </td>
            <td>
                {{$patient->state}}
            </td>

          <td>

            <form action="{{ url('/patients/'.$patient->id) }}" method="POST">
              @csrf
              @method('DELETE')

              <a href="{{ url('/patients/'.$patient->id.'/edit') }}" class="btn btn-sm btn-primary">Editar</a>

              <button class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que desea eliminar el {{ $patient->name }}?');" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-body">
    {{ $patients->links() }}
  </div>
</div>
@endsection
