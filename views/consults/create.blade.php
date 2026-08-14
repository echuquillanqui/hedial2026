@extends('layouts.panel')

@section('styles')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">

@endsection

@section('content')

<form action="{{ url('consults') }}" method="POST">
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
          <a href="{{ url('consults') }}" class="btn btn-sm btn-default">
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
      <div class="row mt--4 text-center">
        <div class="form-group col-sm-12 col-lg-4">
          <label for="patient_id">Pacientes</label>
            <select name="patient_id" id="patient_id" class="form-control selectpicker" data-live-search="true" data-style="btn-warning">
            @foreach ($patients as $patient)
                <option value="{{ $patient->id }}">{{ $patient->name }}</option>
            @endforeach
          </select>
        </div>

          <div class="form-group col-sm-12 col-lg-2">
              <label for="type_consult">Tipo de Consulta</label>
              <select name="type_consult" id="type_consult" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                  <option value="1" selected>NEFROLOGIA</option>
                  <option value="7">TRABAJO SOCIAL</option>
                  <option value="8">PSICOLOGIA</option>
                  <option value="10">NUTRICION</option>
              </select>
          </div>


          <div class="form-group col-sm-12 col-lg-2">
              <label for="registration_date">FECHA DE REGISTRO</label>
              <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
                  </div>
                  <input class="form-control datepicker" placeholder="Seleccionar fecha"
                         id="registration_date" name="registration_date" type="text"
                         value="{{ old('date', date('Y-m-d')) }}"
                         data-date-format="yyyy-mm-dd"
                  >
              </div>
          </div>

          <div class="form-group col-sm-12 col-lg-3">
              <label for="user_id">Usuario que firma la Consulta Externa</label>
              <select name="user_id" id="user_id" class="form-control selectpicker" data-live-search="true" data-style="btn-danger">
                  <option value="{{ auth()->user()->id }}" selected>{{ auth()->user()->name }}</option>
                  @foreach ($users as $user)
                      <option value="{{ $user->id }}">{{ $user->name }}</option>
                  @endforeach
              </select>
          </div>

          <div class="form-group col-sm-12 col-lg-1">
              <label for="n_fua">N° FUA</label>
              <input type="text" name="n_fua" class="form-control" value="{{ $sig_fua }}" readonly>
          </div>

      </div>

          <hr class="dropdown-divider">
          <h3 class="text-center bg-primary text-white">DIAGNOSTICOS</h3>
          <hr class="dropdown-divider">

          <div class="row">
              <div class="form-group col-sm-12 col-lg-1">
                  <label for="cie1">CIE 10</label>
                  <input type="text" name="cie1" class="form-control">
              </div>

              <div class="form-group col-sm-12 col-lg-4">
                  <label for="dx1">DESCRIPCION</label>
                  <input type="text" name="dx1" class="form-control">
              </div>

              <div class="form-group col-sm-12 col-lg-1">
                  <label for="type_dx1">TIPO DX</label>
                  <select name="type_dx1" id="type_dx1" class="form-control selectpicker" data-live-search="true" data-style="btn-success">
                      <option value="R" selected>R</option>
                      <option value="P">P</option>
                      <option value="D">D</option>
                  </select>
              </div>

              <div class="form-group col-sm-12 col-lg-1">
                  <label for="cie2">CIE 10</label>
                  <input type="text" name="cie2" class="form-control">
              </div>

              <div class="form-group col-sm-12 col-lg-4">
                  <label for="dx2">DESCRIPCION</label>
                  <input type="text" name="dx2" class="form-control">
              </div>

              <div class="form-group col-sm-12 col-lg-1">
                  <label for="type_dx2">TIPO DX</label>
                  <select name="type_dx2" id="type_dx2" class="form-control selectpicker" data-live-search="true" data-style="btn-success">
                      <option value="">Dx</option>
                      <option value="R">R</option>
                      <option value="P">P</option>
                      <option value="D">D</option>
                  </select>
              </div>

              <div class="form-group col-sm-12 col-lg-1">
                  <label for="cie3">CIE 10</label>
                  <input type="text" name="cie3" class="form-control">
              </div>

              <div class="form-group col-sm-12 col-lg-4">
                  <label for="dx3">DESCRIPCION</label>
                  <input type="text" name="dx3" class="form-control">
              </div>

              <div class="form-group col-sm-12 col-lg-1">
                  <label for="type_dx3">TIPO DX</label>
                  <select name="type_dx3" id="type_dx3" class="form-control selectpicker" data-live-search="true" data-style="btn-success">
                      <option value="">Dx</option>
                      <option value="R">R</option>
                      <option value="P">P</option>
                      <option value="D">D</option>
                  </select>
              </div>

              <div class="form-group col-sm-12 col-lg-1">
                  <label for="cie4">CIE 10</label>
                  <input type="text" name="cie4" class="form-control">
              </div>

              <div class="form-group col-sm-12 col-lg-4">
                  <label for="dx4">DESCRIPCION</label>
                  <input type="text" name="dx4" class="form-control">
              </div>

              <div class="form-group col-sm-12 col-lg-1">
                  <label for="type_dx4">TIPO DX</label>
                  <select name="type_dx4" id="type_dx4" class="form-control selectpicker" data-live-search="true" data-style="btn-success">
                      <option value="">Dx</option>
                      <option value="R">R</option>
                      <option value="P">P</option>
                      <option value="D">D</option>
                  </select>
              </div>
          </div>

          <hr class="dropdown-divider">
          <h3 class="text-center bg-gradient-danger text-white">MEDICAMENTOS</h3>
          <hr class="dropdown-divider">
        <div class="row text-center">

            <div class="form-group col-sm-12 col-lg-3">
                <label for="code1">Medicamento 1</label>
                <select name="code1" id="code1" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                    <option value="">Seleccione</option>
                    @foreach($medicaments as $medicament)
                        <option value="{{ $medicament->id }}">{{ $medicament->codigo }} - {{ $medicament->descripcion }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-1">
                <label for="cant1">Cant.</label>
                <select name="cant1" id="cant1" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                    <option value="0" selected>0</option>
                    <option value="30">30</option>
                    <option value="60">60</option>
                    <option value="90">90</option>
                    <option value="120">120</option>
                    <option value="150">150</option>
                    <option value="180">180</option>
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-3">
                <label for="code2">Medicamento 2</label>
                <select name="code2" id="code2" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                    <option value="">Seleccione</option>
                    @foreach($medicaments as $medicament)
                        <option value="{{ $medicament->id }}">{{ $medicament->codigo }} - {{ $medicament->descripcion }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-1">
                <label for="cant2">Cant.</label>
                <select name="cant2" id="cant2" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                    <option value="0" selected>0</option>
                    <option value="30">30</option>
                    <option value="60">60</option>
                    <option value="90">90</option>
                    <option value="120">120</option>
                    <option value="150">150</option>
                    <option value="180">180</option>
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-3">
                <label for="code3">Medicamento 3</label>
                <select name="code3" id="code3" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                    <option value="">Seleccione</option>
                    @foreach($medicaments as $medicament)
                        <option value="{{ $medicament->id }}">{{ $medicament->codigo }} - {{ $medicament->descripcion }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-1">
                <label for="cant3">Cant.</label>
                <select name="cant3" id="cant3" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                    <option value="0" selected>0</option>
                    <option value="30">30</option>
                    <option value="60">60</option>
                    <option value="90">90</option>
                    <option value="120">120</option>
                    <option value="150">150</option>
                    <option value="180">180</option>
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-3">
                <label for="code4">Medicamento 4</label>
                <select name="code4" id="code4" class="form-control selectpicker" data-live-search="true" data-style="btn-primary">
                    <option value="">Seleccione</option>
                    @foreach($medicaments as $medicament)
                        <option value="{{ $medicament->id }}">{{ $medicament->codigo }} - {{ $medicament->descripcion }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-1">
                <label for="cant4">Cant.</label>
                <select name="cant4" id="cant4" class="form-control selectpicker" data-live-search="true" data-style="btn-primary">
                    <option value="0" selected>0</option>
                    <option value="30">30</option>
                    <option value="60">60</option>
                    <option value="90">90</option>
                    <option value="120">120</option>
                    <option value="150">150</option>
                    <option value="180">180</option>
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-3">
                <label for="code5">Medicamento 5</label>
                <select name="code5" id="code5" class="form-control selectpicker" data-live-search="true" data-style="btn-primary">
                    <option value="">Seleccione</option>
                    @foreach($medicaments as $medicament)
                        <option value="{{ $medicament->id }}">{{ $medicament->codigo }} - {{ $medicament->descripcion }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-1">
                <label for="cant5">Cant.</label>
                <select name="cant5" id="cant5" class="form-control selectpicker" data-live-search="true" data-style="btn-primary">
                    <option value="0" selected>0</option>
                    <option value="30">30</option>
                    <option value="60">60</option>
                    <option value="90">90</option>
                    <option value="120">120</option>
                    <option value="150">150</option>
                    <option value="180">180</option>
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-3">
                <label for="code6">Medicamento 6</label>
                <select name="code6" id="code6" class="form-control selectpicker" data-live-search="true" data-style="btn-primary">
                    <option value="">Seleccione</option>
                    @foreach($medicaments as $medicament)
                        <option value="{{ $medicament->id }}">{{ $medicament->codigo }} - {{ $medicament->descripcion }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-1">
                <label for="cant6">Cant.</label>
                <select name="cant6" id="cant6" class="form-control selectpicker" data-live-search="true" data-style="btn-primary">
                    <option value="0" selected>0</option>
                    <option value="30">30</option>
                    <option value="60">60</option>
                    <option value="90">90</option>
                    <option value="120">120</option>
                    <option value="150">150</option>
                    <option value="180">180</option>
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-3">
                <label for="code7">Medicamento 7</label>
                <select name="code7" id="code7" class="form-control selectpicker" data-live-search="true" data-style="btn-darker">
                    <option value="">Seleccione</option>
                    @foreach($medicaments as $medicament)
                        <option value="{{ $medicament->id }}">{{ $medicament->codigo }} - {{ $medicament->descripcion }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-1">
                <label for="cant7">Cant.</label>
                <select name="cant7" id="cant7" class="form-control selectpicker" data-live-search="true" data-style="btn-darker">
                    <option value="0" selected>0</option>
                    <option value="30">30</option>
                    <option value="60">60</option>
                    <option value="90">90</option>
                    <option value="120">120</option>
                    <option value="150">150</option>
                    <option value="180">180</option>
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-3">
                <label for="code8">Medicamento 8</label>
                <select name="code8" id="code8" class="form-control selectpicker" data-live-search="true" data-style="btn-darker">
                    <option value="">Seleccione</option>
                    @foreach($medicaments as $medicament)
                        <option value="{{ $medicament->id }}">{{ $medicament->codigo }} - {{ $medicament->descripcion }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-1">
                <label for="cant8">Cant.</label>
                <select name="cant8" id="cant8" class="form-control selectpicker" data-live-search="true" data-style="btn-darker">
                    <option value="0" selected>0</option>
                    <option value="30">30</option>
                    <option value="60">60</option>
                    <option value="90">90</option>
                    <option value="120">120</option>
                    <option value="150">150</option>
                    <option value="180">180</option>
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-3">
                <label for="code9">Medicamento 9</label>
                <select name="code9" id="code9" class="form-control selectpicker" data-live-search="true" data-style="btn-darker">
                    <option value="">Seleccione</option>
                    @foreach($medicaments as $medicament)
                        <option value="{{ $medicament->id }}">{{ $medicament->codigo }} - {{ $medicament->descripcion }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-sm-12 col-lg-1">
                <label for="cant9">Cant.</label>
                <select name="cant9" id="cant9" class="form-control selectpicker" data-live-search="true" data-style="btn-darker">
                    <option value="0" selected>0</option>
                    <option value="30">30</option>
                    <option value="60">60</option>
                    <option value="90">90</option>
                    <option value="120">120</option>
                    <option value="150">150</option>
                    <option value="180">180</option>
                </select>
            </div>

        </div>
          <hr class="dropdown-divider">
          <h3 class="text-center bg-gradient-purple text-white">MEDICAMENTOS INTRADIALISIS MENSUAL</h3>
          <hr class="dropdown-divider">
          <div class="row text-center">
              <div class="form-group col-sm-12 col-lg-2">
                  <label for="code10">INTRA 1</label>
                  <select name="code10" id="code10" class="form-control selectpicker" data-live-search="true" data-style="btn-warning">
                      <option value="">Seleccione</option>
                      @foreach($intradialisis as $intradialisi)
                          <option value="{{ $intradialisi->id }}">{{ $intradialisi->codigo }} - {{ $intradialisi->descripcion }}</option>
                      @endforeach
                  </select>
              </div>

              <div class="form-group col-sm-12 col-lg-1">
                  <label for="cant10">Cant.</label>
                  <input type="number" name="cant10" class="form-control" value="0">
              </div>

              <div class="form-group col-sm-12 col-lg-2">
                  <label for="code11">INTRA 2</label>
                  <select name="code11" id="code11" class="form-control selectpicker" data-live-search="true" data-style="btn-warning">
                      <option value="">Seleccione</option>
                      @foreach($intradialisis as $intradialisi)
                          <option value="{{ $intradialisi->id }}">{{ $intradialisi->codigo }} - {{ $intradialisi->descripcion }}</option>
                      @endforeach
                  </select>
              </div>

              <div class="form-group col-sm-12 col-lg-1">
                  <label for="cant11">Cant.</label>
                  <input type="number" name="cant11" class="form-control" value="0">
              </div>

              <div class="form-group col-sm-12 col-lg-2">
                  <label for="code12">INTRA 3</label>
                  <select name="code12" id="code12" class="form-control selectpicker" data-live-search="true" data-style="btn-warning">
                      <option value="">Seleccione</option>
                      @foreach($intradialisis as $intradialisi)
                          <option value="{{ $intradialisi->id }}">{{ $intradialisi->codigo }} - {{ $intradialisi->descripcion }}</option>
                      @endforeach
                  </select>
              </div>

              <div class="form-group col-sm-12 col-lg-1">
                  <label for="cant12">Cant.</label>
                  <input type="number" name="cant12" class="form-control" value="0">
              </div>

              <div class="form-group col-sm-12 col-lg-2">
                  <label for="code13">INTRA 4</label>
                  <select name="code13" id="code13" class="form-control selectpicker" data-live-search="true" data-style="btn-warning">
                      <option value="">Seleccione</option>
                      @foreach($intradialisis as $intradialisi)
                          <option value="{{ $intradialisi->id }}">{{ $intradialisi->codigo }} - {{ $intradialisi->descripcion }}</option>
                      @endforeach
                  </select>
              </div>

              <div class="form-group col-sm-12 col-lg-1">
                  <label for="cant13">Cant.</label>
                  <input type="number" name="cant13" class="form-control" value="0">
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
