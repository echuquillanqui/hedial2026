@extends('layouts.panel')

@section('content')

<div class="card shadow">
  <div class="card-header border-0 mb--4">
    <div class="row align-items-center">
      <div class="col-lg">
        <h3 class="mb-0">Ordenes de Atención</h3>
      </div>

      <div class="col text-right">
        <a href="{{ url('orders/create') }}" class="btn btn-sm btn-success">
          Nueva orden
        </a>
      </div>

      <a href="{{ route('orders.excel') }}" class="btn btn-sm btn-info">
        Excel
      </a>
    </div>
  </div>

  <div class="card-body mb--4">
    <form action="{{ url('orders') }}" method="GET">
      <div class="row">

        <div class="form-group col-lg-4">
            <div class="input-group">
            <div class="input-group-prepend">
            <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
            </div>
            <input class="form-control datepicker" placeholder="Seleccionar fecha"
                id="date_order" name="date_order" type="text"
                value="{{ old('date', date('Y-m-d')) }}"
                data-date-format="yyyy-mm-dd"
                >
          </div>
        </div>
        <div class="col-lg-3">
          <button class="btn btn-info btn-md" type="submit">Buscar</button>
        </div>
      </div>

    </form>
  </div>

  <div class="table-responsive">
    <!-- Projects table -->
    <table class="table align-items-center table-flush table-hover">
      <thead class="thead-light text-center">
        <tr>
            <th scope="col">ID</th>
            <th scope="col">Paciente</th>
            <th scope="col">N° FUA</th>
            <th scope="col">Sala</th>
            <th scope="col">Turno</th>
            <th scope="col">Paciente con COVID</th>
            <th scope="col">Fecha de orden</th>
            <th scope="col">Fecha y hora de creación</th>
            <th>Opciones</th>
        </tr>
      </thead>
      <tbody class="text-center">
        @foreach ($orders as $order)
        <tr>
          <th scope="row">
            {{$order->id}}
          </th>
          <td>
            {{$order->patient->name}}
          </td>
            <td>
                {{$order->n_fua}}
            </td>
          <td>
            {{$order->room->name}}
          </td>
          <td>
            {{$order->shift->name}}
          </td>
          <td>
            {{$order->covid}}
          </td>
          <td>
            {{$order->date_order}}
          </td>

            <td>
                {{$order->created_at}}
            </td>

          <td>

            <form action="{{ url('/orders/'.$order->id) }}" method="POST">
              @csrf
              @method('DELETE')
                <button class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que desea eliminar la orden del paciente {{ $order->patient->name }}?, ya que al borrarlo eliminara los registros que tenga del día {{ $order->created_at }}');" type="submit">Eliminar</button>

                <a href="{{ url('/orders/'.$order->id.'/edit') }}" class="btn btn-sm btn-primary">Editar</a>

                @if(\Illuminate\Support\Facades\Auth::user()->id == 1)
                    <a href="{{ url('/orders/'.$order->id.'/fua') }}" class="btn btn-sm btn-outline-success" target="_blank"><i class="fas fa-file-alt fa-lg"></i></a>
                @endif

            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-body">
    {{ $orders->appends($_GET)->links() }}
  </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
@endsection
