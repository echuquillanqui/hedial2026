@extends('layouts.panel')

@section('styles')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">

@endsection

@section('content')
<form action="{{ url('tracings') }}" method="POST">
  <div class="card shadow">
    <div class="card-header border-0">
      <div class="row align-items-center">
        <div class="col">
          <h3 class="mb-0">Generar Seguimeinto Mensual</h3>
        </div>
        <div class="col text-right">
          <a href="{{ url('tracings') }}" class="btn btn-sm btn-default">
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

      @csrf
      <div class="row">
        <div class="form-group col-sm-12 col-lg-6">
          <label for="patient_id">Pacientes</label
>          <select name="patient_id" id="patient_id" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            @foreach ($patients as $patient)
            <option value="{{ $patient->id }}">{{ $patient->name }}</option>

            @endforeach
          </select>
        </div>

        <div class="form-group col-sm-12 col-lg-3">
          <label for="room_id">Salas</label>
          <select data-live-search="true" name="room_id" id="room_id" class="form-control selectpicker" data-style="btn-info">
            @foreach ($rooms as $room)
            <option value="{{ $room->id }}">{{ $room->name }}</option>

            @endforeach
          </select>
        </div>

        <div class="form-group col-sm-12 col-lg-3">
          <label for="shift_id">Turnos</label>
          <select name="shift_id" id="shift_id" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            @foreach ($shifts as $shift)
            <option value="{{ $shift->id }}">{{ $shift->name }}</option>

            @endforeach
          </select>
        </div>

        <div class="form-group">
          <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
        </div>
      </div>


      <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
@endsection