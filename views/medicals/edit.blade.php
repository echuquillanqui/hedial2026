@extends('layouts.panel')

@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">
@endsection

@section('content')

<div class="card shadow">
  @if (session('notification'))
    <div class="alert alert-success" role="alert">
      <span class="alert-icon"><i class="ni ni-curved-next"></i></span>
      {{ session('notification') }}
    </div>
  @endif
  <div class="card-header border-0">
    <div class="row align-items-center">
      <div class="col">
        <h3 class="mb-0">Medicina</h3>
        <h3>Paciente: {{ $medical->patient }}</h3>
      </div>
      <div class="col text-right">
        <a href="{{ url('medicals') }}" class="btn btn-sm btn-default">
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


  <form action="{{ url('medicals/'.$medical->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row">

        <div class="form-group col-sm-12 col-lg-2">
            <label for="start_hour">Hora Inicial</label>
            <input type="time" name="start_hour" class="form-control" value="{{ old('start_hour', $medical->start_hour) }}">
        </div>

      <div class="form-group col-sm-12 col-lg-1">
        <label for="start_weight">Peso Inicial</label>
        <input type="text" name="start_weight" class="form-control" value="{{ old('start_weight', $medical->start_weight) }}">
      </div>

      <div class="form-group col-sm-12 col-lg-2">
        <label for="start_pa">PA Inicial</label>
        <input type="text" name="start_pa" class="form-control" value="{{ old('start_pa', $medical->start_pa) }}">
      </div>

      <div class="form-group col-sm-12 col-lg-2">
        <label for="fc">Frecuencia Cardiaca</label>
        <input type="text" name="fc" class="form-control" value="{{ old('fc', $medical->fc) }}">
      </div>

        <div class="form-group col-sm-12 col-lg-2">
            <label for="so2">Saturacion SO2</label>
            <input type="text" name="so2" class="form-control" value="{{ old('so2', $medical->so2) }}">
        </div>

        <div class="form-group col-sm-12 col-lg-2">
            <label for="fio">FIO2</label>
            <input type="text" name="fio" class="form-control" value="{{ !$medical->fio ? '0.21' : $medical->fio }}">
        </div>

        <div class="form-group col-sm-12 col-lg-1">
            <label for="temp">TEMPERATURA</label>
            <input type="text" name="temp" class="form-control" value="{{ old('temp', $medical->temp) }}">
        </div>
    </div>

    <div class="row">

      @if ($medical->clinical_trouble == null)

        <div class="form-group col-sm-12 col-lg-3">
          <label for="clinical_trouble">Problemas Clínicos:</label>
          <textarea class="form-control" id="" name="clinical_trouble" rows="2">{{ old('clinical_trouble', 'INSUFICIENCIA RENAL TERMINAL (N18.0), ANEMIA CRONICA (D63.8)') }}</textarea>
        </div>
      @else
        <div class="form-group col-sm-12 col-lg-3">
          <label for="clinical_trouble">Problemas Clínicos</label>
          <textarea class="form-control" id="" name="clinical_trouble" rows="2">{{ $medical->clinical_trouble }}</textarea>
        </div>
      @endif

      <div class="form-group col-sm-12 col-lg-3">
        <label for="evaluation">Evaluación</label>
        <textarea class="form-control" id="" name="evaluation" rows="2">{{ old('evaluation' ,$medical->evaluation) }}</textarea>
      </div>

      <div class="form-group col-sm-12 col-lg-3">
        <label for="indications">Indicaciones</label>
        <textarea class="form-control" id="" name="indications" rows="2">{{ old('indications', $medical->indications) }}</textarea>
      </div>
        @if($medical->signal == null)
              <div class="form-group col-sm-12 col-lg-3">
                  <label for="signal">Signos y Sintomas</label>
                  <textarea class="form-control" id="" name="signal" rows="2">{{ old('signal', 'NIEGA') }}</textarea>
              </div>
          @else
              <div class="form-group col-sm-12 col-lg-3">
                  <label for="signal">Signos y Sintomas</label>
                  <textarea class="form-control" id="" name="signal" rows="2">{{ old('signal', $medical->signal) }}</textarea>
              </div>
        @endif


    </div>

      <div class="row">
          <div class="form-group col-sm-12 col-lg-2">
              <label for="epo">Epoteina alfa 2000 Ul/mL:</label>
              <input type="number" name="epo" class="form-control" value="{{ old('epo', !$medical->epo ? 0 : $medical->epo) }}" placeholder="COLOCAR SOLO CANTIDAD">
          </div>

          <div class="form-group col-sm-12 col-lg-2">
              <label for="epo4000">Epoteina alfa 4000 Ul/mL:</label>
              <input type="number" name="epo4000" class="form-control" value="{{ old('epo4000', !$medical->epo4000 ? 0 : $medical->epo4000) }}" placeholder="COLOCAR SOLO CANTIDAD">
          </div>

          <div class="form-group col-sm-12 col-lg-2">
              <label for="iron">Hierro 20 mg Fe/mL INY 5 mL:</label>
              <input type="number" name="iron" class="form-control" value="{{ old('iron', !$medical->iron ? 0 : $medical->iron) }}" placeholder="COLOCAR SOLO CANTIDAD">
          </div>

          <div class="form-group col-sm-12 col-lg-3">
              <label for="vitb12">Hidroxicobalamina 1mg/mL INY 1mL: </label>
              <input type="number" name="vitb12" class="form-control" value="{{ old('vitb12', !$medical->vitb12 ? 0 : $medical->vitb12) }}" placeholder="COLOCAR SOLO CANTIDAD">
          </div>

          <div class="form-group col-sm-12 col-lg-3">
              <label for="calci">Calcitriol 1 mcg/mL INY:</label>
              <input type="number" name="calci" class="form-control" value="{{ old('calci', !$medical->calci ? 0 : $medical->calci) }}" placeholder="COLOCAR SOLO CANTIDAD">
          </div>
      </div>

    <div class="row">
      <div class="form-group col-sm-12 col-lg-1">
        <label for="hour_hd">HORA HD</label>
        <input type="text" name="hour_hd" class="form-control" value="{{ old('hour_hd', $medical->hour_hd) }}">
      </div>

      <div class="form-group col-sm-12 col-lg-1">
        <label for="heparin">Heparina</label>
          @if(!$medical->heparin)
              <input type="text" name="heparin" class="form-control border border-danger" value="{{ old('heparin', $medical->heparin) }}">
          @else
              <input type="text" name="heparin" class="form-control" value="{{ old('heparin', $medical->heparin) }}">
          @endif

      </div>

      <div class="form-group col-sm-12 col-lg-2">
        <label for="dry_weight">Peso Seco</label>
        <input type="text" name="dry_weight" class="form-control" value="{{ old('dry_weight', $medical->dry_weight) }}">
      </div>

      <div class="form-group col-sm-12 col-lg-2">
        <label for="uf">UF</label>
        <input type="text" name="uf" class="form-control" value="{{ old('uf', $medical->uf) }}">
      </div>

      <div class="form-group col-sm-12 col-lg-2">
        <label for="qb">QB</label>
        <input type="text" name="qb" class="form-control" value="{{ old('qb', $medical->qb) }}">
      </div>

        @if($medical->qd == null)
            <div class="form-group col-sm-12 col-lg-2">
                <label for="qd">QD</label>
                <input type="text" name="qd" class="form-control" value="{{ old('qd', '500') }}">
            </div>
        @else
            <div class="form-group col-sm-12 col-lg-2">
                <label for="qd">QD</label>
                <input type="text" name="qd" class="form-control" value="{{ old('qd', $medical->qd) }}">
            </div>
        @endif

        @if($medical->bicarbonat == null)
            <div class="form-group col-sm-12 col-lg-2">
                <label for="bicarbonat">Bicarbonato</label>
                <input type="text" name="bicarbonat" class="form-control" value="{{ old('bicarbonat', '5.6') }}">
            </div>
        @else
            <div class="form-group col-sm-12 col-lg-2">
                <label for="bicarbonat">Bicarbonato</label>
                <input type="text" name="bicarbonat" class="form-control" value="{{ old('bicarbonat', $medical->bicarbonat) }}">
            </div>
        @endif

    </div>

    <div class="row">

        <div class="form-group col-sm-12 col-lg-2">
            <label for="start_na">NA INICIAL</label>
            <input type="text" name="start_na" class="form-control" value="{{ old('start_na', $medical->start_na) }}">
        </div>

      <div class="form-group col-sm-12 col-lg-1">
        <label for="cnd">CND</label>
        <input type="text" name="cnd" class="form-control" value="{{ old('cnd', $medical->cnd) }}">
      </div>

      <div class="form-group col-sm-12 col-lg-2">
        <label for="end_na">NA FINAL</label>
        <input type="text" name="end_na" class="form-control" value="{{ old('end_na', $medical->end_na) }}">
      </div>

        @if($medical->profile_na == null)
            <div class="form-group col-sm-12 col-lg-1">
                <label for="profile_na">Perfil Na</label>
                <input type="text" name="profile_na" class="form-control" value="{{ old('profile_na', 'PERFIL L') }}">
            </div>
        @else
            <div class="form-group col-sm-12 col-lg-1">
                <label for="profile_na">Perfil Na</label>
                <input type="text" name="profile_na" class="form-control" value="{{ old('profile_na', $medical->profile_na) }}">
            </div>
        @endif


      <div class="form-group col-sm-12 col-lg-2">
        <label for="area_filter">ÁREA/FILTRO</label>
        <input type="text" name="area_filter" class="form-control" value="{{ old('area_filter', $medical->area_filter) }}">
      </div>

        @if($medical->membrane == null)
            <div class="form-group col-sm-12 col-lg-2">
                <label for="membrane">MEMBRANA</label>
                <input type="text" name="membrane" class="form-control" value="{{ old('membrane', 'PSF') }}">
            </div>
        @else
            <div class="form-group col-sm-12 col-lg-2">
                <label for="membrane">MEMBRANA</label>
                <input type="text" name="membrane" class="form-control" value="{{ old('membrane', $medical->membrane) }}">
            </div>
        @endif


        @if($medical->profile_uf == null)
            <div class="form-group col-sm-12 col-lg-2">
                <label for="profile_uf">Perfil UF:</label>
                <input type="text" name="profile_uf" class="form-control" value="{{ old('profile_uf', 'PERFIL L') }}">
            </div>
        @else
            <div class="form-group col-sm-12 col-lg-2">
                <label for="profile_uf">Perfil UF:</label>
                <input type="text" name="profile_uf" class="form-control" value="{{ old('profile_uf', $medical->profile_uf) }}">
            </div>
        @endif



<!--      <div class="form-group col-sm-12 col-lg-2">
        <label for="serological">Cond. Serologica</label>
        <select class="form-control" name="serological" data-toggle="select" title="Simple select" data-placeholder="Select a state">
          <option value="{{ $medical->serological }}" disabled="">{{ $medical->serological }}</option>
          <option value="NEGATIVO">NEGATIVO</option>
          <option value="POSITIVO">POSITIVO</option>
        </select>
      </div>-->
    </div>

    <div class="row">


<!--      <div class="form-group col-sm-12 col-lg-3">
        <label for="dializer">Dializador</label>
        @if ( $medical->dializar != null )
          <input type="text" name="dializer" class="form-control" value="{{ $medical->dializer }}">
        @else
          <input type="text" name="dializer" class="form-control" value="{{ old('dializer', 'ELISIO') }}">
        @endif

      </div>-->
        @if($medical->end_evaluation == null)
            <div class="form-group col-sm-12 col-lg-4">
                <label for="end_evaluation">Evaluación Final</label>
                <textarea class="form-control" id="" name="end_evaluation" rows="2">{{ old('end_evaluation' ,'SIN COMPLICACIONES') }}</textarea>
            </div>
        @else
            <div class="form-group col-sm-12 col-lg-4">
                <label for="end_evaluation">Evaluación Final</label>
                <textarea class="form-control" id="" name="end_evaluation" rows="2">{{ old('end_evaluation' ,$medical->end_evaluation) }}</textarea>
            </div>
        @endif


        <div class="form-group col-sm-12 col-lg-2">
            <label for="end_hour">Hora final</label>
            <input type="time" name="end_hour" class="form-control" value="{{ old('end_hour', $medical->end_hour) }}">
        </div>

        <div class="form-group col-sm-12 col-lg-4">
            <label for="fua_observacion">SI EL PACIENTE NO PUEDE FIRMAR SELECCIONE UNA OPCION</label>
            <select name="fua_observacion" id="fua_observacion" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                <option value="{{ !$medical->fua_observacion ? '' : $medical->fua_observacion }}" disabled selected>{{ !$medical->fua_observacion ? '[SELECCIONE UNA OPCION]' : $medical->fua_observacion }}</option>
                <option value="PACIENTE PRESENTA MOVIMIENTOS INVOLUNTARIOS EN MIEMBROS SUPERIORES, IMPOSIBILITADO PARA FIRMAR">PACIENTE PRESENTA MOVIMIENTOS INVOLUNTARIOS EN MIEMBROS SUPERIORES, IMPOSIBILITADO PARA FIRMAR</option>
                <option value="PACIENTE REFIERE DISMINUCION DE LA AGUDEZA VISUAL, IMPOSIBILITADO PARA FIRMAR">PACIENTE REFIERE DISMINUCION DE LA AGUDEZA VISUAL, IMPOSIBILITADO PARA FIRMAR</option>
                <option value="PACIENTE PRESENTA DISTENIA POST DIALISIS, IMPOSIBILITADO AL MOMENTO DE FIRMAR">PACIENTE PRESENTA DISTENIA POST DIALISIS, IMPOSIBILITADO AL MOMENTO DE FIRMAR</option>
                <option value="NO FIRMA, SOLO PONE HUELLA POR RETINOPATIA HIPERTENSIVA">NO FIRMA, SOLO PONE HUELLA POR RETINOPATIA HIPERTENSIVA</option>
                <option value="NO FIRMA, SOLO PONE HUELLA POR RETINOPATIA DIABETICA">NO FIRMA, SOLO PONE HUELLA POR RETINOPATIA DIABETICA</option>
                <option value="NO FIRMA, SOLO PONE HUELLA POR NEUROPATIA PERIFERICA">NO FIRMA, SOLO PONE HUELLA POR NEUROPATIA PERIFERICA</option>
                <option value="NO FIRMA SOLO PONE HUELLA POR ARTRITIS REUMATOIDE ">NO FIRMA SOLO PONE HUELLA POR ARTRITIS REUMATOIDE </option>
                <option value="NO FIRMA, POR DISFUNCION MOTORA ">NO FIRMA, POR DISFUNCION MOTORA </option>
                <option value="NO FIRMA, SOLO PONE HUELLA POR ENFERMEDAD DE PARKINSON">NO FIRMA, SOLO PONE HUELLA POR ENFERMEDAD DE PARKINSON</option>
                <option value="NO FIRMA, SOLO PONE HUELLA POR RETINOPATIA HIPERTENSIVA Y DIABTETES.">NO FIRMA, SOLO PONE HUELLA POR RETINOPATIA HIPERTENSIVA Y DIABTETES.</option>
            </select>
        </div>

        <div class="form-group col-sm-12 col-lg-2">
            <label for="user_id">USUARIO DE LA ATENCION</label>
            <select class="form-control selectpicker" name="user_id" data-live-search="true" data-style="btn-info">
                <option value="{{ !$medical->user_id ? auth()->user()->id : $medical->user_id }}">{{ !$medical->user_id ? auth()->user()->name : $medical->user->name }}</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach

            </select>
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
