@extends('layouts.panel')

@section('styles')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">

@endsection

@section('content')

<form action="{{ url('orders') }}" method="POST">
  <div class="card shadow">
    <div class="card-header border-0">
      @if (session('notification'))
        <div class="alert alert-success" role="alert">
          <span class="alert-icon"><i class="ni ni-curved-next"></i></span>
          {{ session('notification') }}
        </div>
        @endif
      <div class="row align-items-center">
        <div class="col">
          <h3 class="mb-0">Nueva Orden</h3>
        </div>
        <div class="col text-right">
          <a href="{{ url('orders') }}" class="btn btn-sm btn-default">
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
      <div class="row mt--4">
        <div class="form-group col-sm-12 col-lg-3">
          <label for="patient_id">Pacientes</label
>          <select name="patient_id" id="patient_id" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            @foreach ($patients as $patient)
            <option value="{{ $patient->id }}">{{ $patient->name }}</option>

            @endforeach
          </select>
        </div>

        <div class="form-group col-sm-12 col-lg-2">
          <label for="room_id">Salas</label>
          <select data-live-search="true" name="room_id" id="room_id" class="form-control selectpicker" data-style="btn-info">
            @foreach ($rooms as $room)
            <option value="{{ $room->id }}">{{ $room->name }}</option>

            @endforeach
          </select>
        </div>

        <div class="form-group col-sm-12 col-lg-2">
          <label for="shift_id">Turnos</label>
          <select name="shift_id" id="shift_id" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            @foreach ($shifts as $shift)
            <option value="{{ $shift->id }}">{{ $shift->name }}</option>

            @endforeach
          </select>
        </div>

          <div class="form-group col-sm-12 col-lg-1">
              <label for="covid">COVID?</label>
              <select name="covid" id="covid" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                  <option value="NO">NO</option>
                  <option value="SI">SI</option>
              </select>
          </div>

          <div class="form-group col-sm-12 col-lg-1">
              <label for="covid">HD</label>
              <select name="hour_hd" id="hour_hd" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                  <option value="3.5" selected>3.5</option>
                  <option value="3">3</option>
                  <option value="3.15">3.15</option>
                  <option value="3.75">3.75</option>
              </select>
          </div>

          <div class="form-group col-sm-12 col-lg-1">
              <label for="n_fua">FUA</label>
              <input type="text" name="n_fua" class="form-control" value="{{ $sig_fua }}">
          </div>

          <div class="form-group col-lg-2">
              <label for="">FECHA DE CREACION</label>
              <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
                  </div>
                  <input class="form-control datepicker" placeholder="Seleccionar fecha"
                         id="date_order" name="date_order" type="text"
                         value="{{ old('date_order', date('Y-m-d')) }}"
                         data-date-format="yyyy-mm-dd"
                  >
              </div>
          </div>

          <div class="form-group col-sm-12 col-lg-3">
              <label for="type">TIPO PROCEDIMIENTO</label>
              <select name="type" id="type" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                  <option value="1" selected>HEMODIALISIS</option>
                  <option value="2">CONSULTA NEFROLOGICA</option>
              </select>
          </div>

          @if(\Carbon\Carbon::now()->format('Y-m-d') == '2023-09-20' || \Carbon\Carbon::now()->format('Y-m-d') == '2023-09-21')
              <div class="form-group col-sm-12 col-lg-2">
                  <label for="lab">¿INCLUYE LABORATORIO?</label>
                  <select name="lab" id="lab" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                      <option value="SI" selected>SI</option>
                      <option value="NO">NO</option>
                  </select>
              </div>
          @else
              <div class="form-group col-sm-12 col-lg-2">
                  <label for="lab">¿INCLUYE LABORATORIO?</label>
                  <select name="lab" id="lab" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                      <option value="NO">NO</option>
                      <option value="SI">SI</option>
                  </select>
              </div>
          @endif

        <div class="form-group">
          <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
        </div>
      </div>


      <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
  </div>
</form>
@endsection

@section('scripts')
<script src="{{ asset('vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
@endsection
