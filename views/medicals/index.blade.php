@extends('layouts.panel')

@section('content')

<div class="card shadow">
  <div class="card-header border-0">
    <div class="row align-items-center">
      <div class="col">
        <h3 class="mb-0">Medicina</h3>
      </div>
    </div>
  </div>

  <div class="card-body">
    <form action="{{ url('medicals') }}" method="GET">
      <div class="row">

        <div class="col-lg-4">
          <div class="form-group">
            <label for="patient">Nombres y Apellidos</label>
            <input type="text" name="patient" class="form-control" value="{{ old('patient') }}" autofocus>
          </div>
        </div>

        <div class="form-group col-sm-12 col-lg-2">
          <label for="room">Salas</label>
          <select data-live-search="true" name="room" id="room" class="form-control selectpicker" data-style="btn-info">
            <option></option>
            @foreach ($rooms as $room)
            <option value="{{ $room->name }}">{{ $room->name }}</option>

            @endforeach
          </select>
        </div>

        <div class="form-group col-sm-12 col-lg-3">
          <label for="shift">Turnos</label>
          <select name="shift" id="shift" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option></option>
            @foreach ($shifts as $shift)
            <option value="{{ $shift->name }}">{{ $shift->name }}</option>

            @endforeach
          </select>
        </div>

        <div class="form-group col-lg-2">
            <label for="date_order">Fecha</label>
          <div class="input-group">
            <div class="input-group-prepend">
            <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
            </div>
            <input class="form-control datepicker" placeholder="Seleccionar fecha"
                id="date_order" name="date_order" type="text"
                value="{{ old('date') }}"
                data-date-format="yyyy-mm-dd"
                >
          </div>
        </div>

          <div class="form-group col-sm-12 col-lg-1">
              <label for="hour_hd">HORAS</label>
              <select name="hour_hd" id="hour_hd" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
                  <option value=""></option>
                  <option value="3.0">3.0</option>
                  <option value="3.25">3.25</option>
                  <option value="3.5">3.5</option>
                  <option value="3.75">3.75</option>
              </select>
          </div>


      </div>
      <button class="btn btn-info btn-sm" type="submit">Buscar</button>
    </form>
  </div>

  <div class="table-responsive">
    <!-- Projects table -->
    <table class="table align-items-center table-flush">
      <thead class="thead-light text-center">
        <tr>
          <th scope="col">Nombres y Apellidos DNI</th>
            <th scope="col">I-F HD</th>
          <th scope="col">Sala</th>
          <th scope="col">Turno</th>
            <th scope="col">Epo2</th>
            <th scope="col">Epo4</th>
            <th scope="col">B12</th>
            <th scope="col">Fe</th>
          <th scope="col">HD</th>
          <th scope="col">Fecha</th>
          <th scope="col">Editar</th>
          <th scope="col">P.Ini</th>
            <th scope="col">P.Fin</th>
            <th scope="col">UF</th>
            <th scope="col">P.Sec</th>

        </tr>
      </thead>
      <tbody class="text-center">
        @foreach ($medicals as $medical)
        <tr>
          <td scope="row">
            {{$medical->patient}} {{$medical->order->patient->dni}}
          </td>

            <td>
                {{$medical->order->nurse->hr}} -

                @if($medical->order->nurse->hr8 == '-' || $medical->order->nurse->hr8 == null)
                    {{$medical->order->nurse->hr7}}
                @else
                    {{$medical->order->nurse->hr8}}
                @endif
            </td>

          <td>

             @if($medical->room == 'AMARILLA')
                  <span class="badge badge-lg bg-yellow">A</span>

              @elseif($medical->room == 'VERDE')
                      <span class="badge badge-lg badge-success">V</span>

              @elseif($medical->room == 'AZUL')
                          <span class="badge badge-lg bg-blue text-white">A</span>

              @else($medical->room == 'NARANJA')
                  <span class="badge badge-lg bg-orange text-white">N</span>
              @endif


          </td>
          <td>

              @if($medical->shift == 'TURNO 1')
                  1

              @elseif($medical->shift == 'TURNO 2')
                  2

              @else($medical->shift == 'TURNO 3')
                  3
              @endif
          </td>

            <td>
                @if (!$medical->epo) 0 @else {{ $medical->epo }} @endif
            </td>

            <td>
                @if (!$medical->epo4000) 0 @else {{ $medical->epo4000 }} @endif
            </td>

            <td>
                @if (!$medical->vitb12) 0 @else {{ $medical->vitb12 }} @endif
            </td>

            <td>
                @if (!$medical->iron) 0 @else {{ $medical->iron }} @endif
            </td>

            <td>
                <button class="btn btn-info btn-sm">{{ $medical->hour_hd }}</button>
            </td>
          <td>
            {{$medical->date_order}}
          </td>
          <td>

            <form action="{{ url('/medicals/'.$medical->id) }}" method="POST">
              @csrf

              <a href="{{ url('/medicals/'.$medical->id.'/edit') }}" class="btn btn-sm btn-primary" target="_blank"><i class="fas fa-edit"></i></a>
            </form>
          </td>

            <td>
                {{$medical->start_weight}}
            </td>
            <td>
                {{$medical->order->nurse->end_weight}}
            </td>
            <td>
                {{ preg_match('/(-?\d+)/', $medical->uf, $matches) ? $valor_num = intval($matches[0]) / 1000 : 0 }}
            </td>
            <td>
                {{$medical->dry_weight}}
            </td>

        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-body">
    {{ $medicals->appends($_GET)->links() }}
  </div>
</div>
@endsection
@section('scripts')
<script src="{{ asset('vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
@endsection
