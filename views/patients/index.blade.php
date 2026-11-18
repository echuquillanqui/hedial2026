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
  <div class="card-body border-bottom">
    <div class="d-flex flex-wrap align-items-center">
      <a href="{{ route('patients.import-template') }}" class="btn btn-primary mr-2 mb-2">
        <i class="ni ni-cloud-download-95 mr-1"></i> Descargar plantilla
      </a>
      <button type="button" class="btn btn-info mb-2" data-toggle="modal" data-target="#importPatientsModal">
        <i class="ni ni-cloud-upload-96 mr-1"></i> Importar pacientes
      </button>
      <span class="text-muted ml-md-3 mb-2">Carga masiva desde archivo CSV</span>
    </div>
  </div>
  <div class="card-body">
    @if (session('notification'))
      <div class="alert alert-success" role="alert">
        <span class="alert-icon"><i class="ni ni-curved-next"></i></span>
        {{ session('notification') }}
      </div>
    @endif
    @if (session('success'))
      <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if (session('error'))
      <div class="alert alert-warning" role="alert">{{ session('error') }}</div>
    @endif
    @if (session('import_issues'))
      <div class="alert alert-light" role="alert">
        <strong>Detalle de filas omitidas (máximo 20):</strong>
        <ul class="mb-0 mt-2">
          @foreach (session('import_issues') as $issue)<li>{{ $issue }}</li>@endforeach
        </ul>
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

<div class="modal fade" id="importPatientsModal" tabindex="-1" role="dialog" aria-labelledby="importPatientsModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="{{ route('patients.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="importPatientsModalLabel">Importar pacientes</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <p>Utilice la plantilla CSV descargada. Mantenga los encabezados y guarde el archivo delimitado por punto y coma.</p>
          <div class="form-group">
            <label for="patientsImportFile">Archivo CSV</label>
            <input class="form-control-file" type="file" name="file" id="patientsImportFile" accept=".csv,.txt,text/csv" required>
            <small class="form-text text-muted">Los pacientes se registran en la sede activa. Los DNI ya registrados se omiten.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-info">Importar archivo</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
