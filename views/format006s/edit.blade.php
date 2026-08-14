@extends('layouts.panel')

@section('content')

<div class="card shadow">
  <div class="card-header border-0">
    <div class="row align-items-center">
      <div class="col">
        <h3 class="mb-0">Seguimiento Paciente: <strong class="ml-3">{{ $format006->tracing->patient->name }}, {{ $format006->tracing->patient->age }} años</strong></h3>
      </div>
      <div class="col text-right">
        <a href="{{ url('format006s/') }}" class="btn btn-sm btn-default">
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

    
                
    <form action="{{ url('format006s/'.$format006->id) }}" method="POST" class="mt--4">
      @csrf
      @method('PUT')
    <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">

      <h4>DATOS DE FILIACION: <strong class="ml-3">SALA: "  {{ $format006->tracing->room->name }}"</strong> <strong class="ml-1">/ {{ $format006->tracing->shift->name }} / HOSPITAL DE REFERENCIA: {{ $format006->tracing->patient->hosp_origin }}</strong></h4>
      <hr class="mt--1">
      <div class="row text-center mt--3">

        <div class="form-group col-sm-12 col-lg-4"> 
        <input type="text" id="unit_dial" name="unit_dial" class="form-control" placeholder="UNIDAD DE DIALISIS" value="{{ $format006->unit_dial }}">
        </div>

        <div class="form-group col-sm-12 col-lg-4">
              <input type="text" id="nefro_treat" name="nefro_treat" class="form-control" placeholder="NEFROLOGO TRATANTE" value="{{ $format006->nefro_treat }}">
        </div>

        <div class="form-group col-sm-12 col-lg-4">
              <input type="text" id="consult_frec" name="consult_frec" class="form-control" placeholder="FRECUENCIA DE CONSULTA" value="{{ $format006->consult_frec }}">
        </div>

        <div class="form-group col-sm-12 col-lg-6">
              <input type="text" id="frec" name="frec" class="form-control" value="3 VECES POR SEMANA" value="{{ $format006->frec }}">
        </div>

        <div class="form-group col-sm-12 col-lg-6">
              <input type="text" id="attorney" name="attorney" class="form-control" placeholder="APODERADO" value="{{ $format006->attorney }}">
        </div>
      </div>

      <h4>ANTECEDENTES CLINICOS</h4>
      <hr class="mt--1">

      <div class="row mt--4">
        <div class="form-group col-sm-12 col-lg-12"> 
          <input type="text" id="cause_erca" name="cause_erca" class="form-control" placeholder="CAUSA DE LA ERCA" value="{{ $format006->cause_erca }}">
        </div>

        <div class="form-group col-sm-12 col-lg-2 ml-5 mr--7"> 
          <span>PREDIALISIS</span>
        </div>

        <div class="form-group col-sm-12 col-lg-5"> 
          <input type="text" id="time_predial" name="time_predial" class="form-control" placeholder="TIEMPO" value="{{ $format006->time_predial }}">
        </div>

        <div class="form-group col-sm-12 col-lg-5"> 
          <input type="text" id="access_vsc" name="access_vsc" class="form-control" placeholder="TPO DE ACCESO VSC." value="{{ $format006->access_vsc }}">
        </div>

        <div class="form-group col-sm-12 col-lg-2 ml-0 mr--4"> 
          <span>PRIMERA DIALISIS</span>
        </div>
        
        <div class="form-group col-lg-2">
          <div class="input-group">
            <div class="input-group-prepend">
            <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
            </div>
            <input class="form-control datepicker" 
                id="date_predl" name="date_predl" type="text" placeholder="Fecha primera dialisis"
                value="{{ old('date_predl', $format006->date_predl )}}"
                data-date-format="yyyy-mm-dd" 
                >
          </div>
        </div>

        <div class="form-group col-sm-12 col-lg-1"> 
          <input type="text" id="dp_predl" name="dp_predl" class="form-control" placeholder="DP" value="{{ $format006->dp_predl }}">
        </div>

        <div class="form-group col-sm-12 col-lg-2"> 
          <input type="text" id="hemod_dl" name="hemod_dl" class="form-control" placeholder="HEMOD" value="{{ $format006->hemod_dl }}">
        </div>

        <div class="form-group col-sm-12 col-lg-2"> 
          <input type="text" id="acc_vasc_dl" name="acc_vasc_dl" class="form-control" placeholder="ACCESO VASC" value="{{ $format006->acc_vasc_dl }}">
        </div>

        <div class="form-group col-sm-12 col-lg-3"> 
          <input type="text" id="establishment" name="establishment" class="form-control" placeholder="ESTABLECIMIENTO DE SALUD" value="{{ $format006->establishment }}">
        </div>
      </div>

      <h4>ACCESO VASCULAR</h4>
      <hr class="mt--1">

      <div class="row mt--4 text-center">

        <div class="col-12">
          <div class="table-responsive">
            <div>
              <table class="table align-items-center">
                <thead class="thead-light">
                  <tr>
                    <th>ITEM</th>
                    <th>CVC</th>
                    <th>FAV</th>
                    <th colspan="2">TRATAMIENTO HIA / FRECUENCIA</th>
                  </tr>
                  
                </thead>
                <tbody>
                  <tr>
                    <td><span>TIEMPO PROM PERMANENCIA</span></td>
                    <td><input type="text" class="form-control" name="tpp_acc_cvc" value="{{ $format006->tpp_acc_cvc }}"></td>
                    <td><input type="text" class="form-control" name="tpp_acc_fav" value="{{ $format006->tpp_acc_fav }}"></td>
                    <td><input type="text" class="form-control" name="hia1" value="{{ $format006->hia1 }}"></td>
                    <td><input type="text" class="form-control" name="frecuency1" value="{{ $format006->frecuency1 }}"></td>
                  </tr>
    
                  <tr>
                    <td><span>NUMERO</span></td>
                    <td><input type="text" class="form-control" name="num_acc_cvc" value="{{ $format006->num_acc_cvc }}"></td>
                    <td><input type="text" class="form-control" name="num_acc_fav" value="{{ $format006->num_acc_fav }}"></td>
                    <td><input type="text" class="form-control" name="hia2" value="{{ $format006->hia2 }}"></td>
                    <td><input type="text" class="form-control" name="frecuency2" value="{{ $format006->frecuency2 }}"></td>
                  </tr>
    
                  <tr>
                    <td><span>CAUSA DE CAMBIO Y/O PÉRDIDA</span></td>
                    <td><input type="text" class="form-control" name="lost_acc_cvc" value="{{ $format006->lost_acc_cvc }}"></td>
                    <td><input type="text" class="form-control" name="lost_acc_fav" value="{{ $format006->lost_acc_fav }}"></td>
                    <td><input type="text" class="form-control" name="hia3" value="{{ $format006->hia3 }}"></td>
                    <td><input type="text" class="form-control" name="frecuency3" value="{{ $format006->frecuency3 }}"></td>
                  </tr>
                  
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="form-group col-sm-12 col-lg-2 ml-0 mt-2">
          <span>Transplante</span>
        </div>

        <div class="col-lg-2 form-group">
          <select class="form-control" name="transplant" data-toggle="select" title="Simple select" data-placeholder="Select a state">
            <option value="SI">SI</option>
            <option value="NO">NO</option>
          </select>
        </div>

        <div class="form-group col-sm-12 col-lg-2 ml-0 mt-2">
          <span>fecha de transplante </span>
        </div>
        
        <div class="form-group col-lg-3">
          <div class="input-group">
            <div class="input-group-prepend">
            <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
            </div>
            <input class="form-control datepicker" 
                id="date_transplant" name="date_transplant" type="text" placeholder="Fecha de transplante"
                value="{{ old('date_transplant', $format006->date_transplant )}}"
                data-date-format="yyyy-mm-dd" 
                >
          </div>
        </div>
      </div>

      <h3>Tabla de datos</h3>
      <hr class="mt--1">
      
      <div class="table-responsive mb-2 text-center">
        <div>
          <caption class="text-center text-dark">PARTE I</caption>
          <table class="table align-items-center table-dark table-bordered">
            
            <thead class="thead-dark">
              <tr>
                <td rowspan="3">N°</td>
                <td rowspan="3">Mes</td>
                <td rowspan="3">Fecha</td>
                <td colspan="3">CONDICION DEL ACCESO VASCULAR ACTUAL</td>
                <td colspan="2">FACTOR CLINICO</td>
              </tr>
              
              <tr>
                <td rowspan="2">TIPO</td>
                <td rowspan="2">TIEMPO.</td>
                <td rowspan="2">UBICAC.</td>
                <td colspan="2">TRILL</td>
              </tr>

              <tr>
                <td>CARAC</td>
                <td>DIST.</td>
              </tr>
            </thead>

            <tbody class="list">
              <th width="7%">
              <input class="form-control text-center" type="text" name="position" id="position" value="{{ $format006->position }}">
              </th>
              <th>
                <select name="month" id="month" class="form-control">
                  <option value="{{ $format006->month }}" disabled selected>{{ $format006->month }}</option>
                  <option value="ENERO">ENERO</option>
                  <option value="FEBRERO">FEBRERO</option>
                  <option value="MARZO">MARZO</option>
                  <option value="ABRIL">ABRIL</option>
                  <option value="MAYO">MAYO</option>
                  <option value="JUNIO">JUNIO</option>
                  <option value="JULIO">JULIO</option>
                  <option value="AGOSTO">AGOSTO</option>
                  <option value="SETIEMBRE">SETIEMBRE</option>
                  <option value="OCTUBRE">OCTUBRE</option>
                  <option value="NOVIEMBRE">NOVIEMBRE</option>
                  <option value="DICIEMBRE">DICIEMBRE</option>
                </select>
              </th>

              <th>
                <input class="form-control datepicker" 
                id="date_register" name="date_register" type="text" placeholder="Fecha"
                value="{{ old('date_register', $format006->date_register )}}"
                data-date-format="yyyy-mm-dd" 
                >
              </th>

              <th>
                <input type="text" name="type" id="type" class="form-control" value="{{ $format006->type }}">
              </th>

              <th>
                <input type="text" name="time" id="time" class="form-control" value="{{ $format006->time }}">
              </th>

              <th>
                <input type="text" name="location" id="location" class="form-control" value="{{ $format006->location }}">
              </th>

              <th>
                <input type="text" name="carac" id="carac" class="form-control" value="{{ $format006->carac }}">
              </th>

              <th>
                <input type="text" name="dist" id="dist" class="form-control" value="{{ $format006->dist }}">
              </th>
            </tbody>

          </table>

          <div class="table-responsive mb-2 text-center mt-2">
            <div>
              <caption class="text-center text-dark">PARTE II</caption>
              <table class="table align-items-center table-dark table-bordered">
                
                <thead class="thead-dark">
                  <tr>
                    <td colspan="5">FACTORES HEMODINAMICOS</td>
                    <td rowspan="3">PROBLEMAS</td>
                    <td rowspan="3">OBSERVACION</td>
                  </tr>
                  
                  <tr>
                    <td colspan="3">PRESION ARTERIAL</td>
                    <td colspan="2">PARAMETROS</td>
                  </tr>
    
                  <tr>
                    <td>INIC.</td>
                    <td>FIN</td>
                    <td>QB</td>
                    <td>RA</td>
                    <td>RV</td>
                  </tr>
                </thead>
    
                <tbody class="list">
    
                  <th width="6%">
                    <input type="text" name="start" id="start" class="form-control" value="{{ $format006->start }}">
                  </th>
    
                  <th width="6%">
                    <input type="text" name="end" id="end" class="form-control" value="{{ $format006->end }}">
                  </th>
    
                  <th width="6%">
                    <input type="text" name="qb" id="qb" class="form-control" value="{{ $format006->qb }}">
                  </th>
    
                  <th width="6%">
                    <input type="text" name="ra" id="ra" class="form-control" value="{{ $format006->ra }}">
                  </th>
    
                  <th width="6%">
                    <input type="text" name="rv" id="rv" class="form-control" value="{{ $format006->rv }}">
                  </th>
    
                  <th width="6%">
                    <textarea type="text" name="trouble" id="trouble" class="form-control" rows="6" cols="10">{{ $format006->trouble }}</textarea>
                  </th>
    
                  <th width="6%">
                    <textarea type="text" name="observation" id="observation" class="form-control" rows="6" cols="10">{{ $format006->observation }}</textarea>
                  </th>
                </tbody>
    
              </table>
            </div>
          </div>
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