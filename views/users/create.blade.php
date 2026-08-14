@extends('layouts.panel')

@section('content')

<div class="card shadow">
  <div class="card-header border-0">
    <div class="row align-items-center">
      <div class="col">
        <h3 class="mb-0">Nuevo usuario</h3>
      </div>
      <div class="col text-right">
        <a href="{{ url('users') }}" class="btn btn-sm btn-default">
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

  	<form action="{{ url('users') }}" method="POST" enctype="multipart/form-data">
  		@csrf

	  	<div class="form-group">
	  		<label for="name">Nombres y Apellidos</label>
	  		<input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
	  	</div>

      <div class="row">
        <div class="form-group col-sm-12 col-lg-9">
          <label for="email">Correo electronico</label>
          <input type="email" name="email" class="form-control" value="{{ old('email') }}">
        </div>

        <div class="form-group col-sm-12 col-lg-3">
          <label for="role">Role</label>
          <select class="form-control" name="role" data-toggle="select" title="Simple select" data-placeholder="Select a state">
              <option value="enfermera">Enfermera(o)</option>
              <option value="doctor">Doctor</option>
              <option value="administrador">Administrador</option>
              <option value="farmacia">Farmacia</option>
              <option value="laboratorio">Laboratorio</option>
              <option value="logistica">Logistica</option>
          </select>
        </div>
        </div>

      <div class="row">
        <div class="form-group col-sm-12 col-lg-3">
          <label for="dni">DNI</label>
          <input type="text" name="dni" class="form-control" value="{{ old('dni') }}">
        </div>

        <div class="form-group col-sm-12 col-lg-3">
          <label for="code_specialty">COLEGIATURA</label>
          <input type="text" name="code_specialty" class="form-control" value="{{ old('code_specialty') }}">
        </div>

        <div class="form-group col-sm-12 col-lg-3">
          <label for="rne">RNE</label>
          <input type="text" name="rne" class="form-control" value="{{ old('rne') }}">
        </div>

        <div class="form-group col-sm-12 col-lg-3">
          <label for="password">Contraseña</label>
          <input type="text" name="password" class="form-control" value="{{ str_random(8) }}" >
        </div>

          <div class="form-group col-sm-12 col-lg-4">
              <label for="image">SUBIR FIRMA</label>
              <input type="file" name="image" class="form-control" value="{{ old('image') }}" accept=".jpg, .jpeg, .png">
          </div>

      </div>
	  	<button type="submit" class="btn btn-primary">Guardar</button>
	  </form>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
@endsection
