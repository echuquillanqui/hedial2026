
<h6 class="navbar-heading text-muted">
@if (auth()->user()->role == 'administrador')
  Gestión de datos
@else
  Menú
@endif

</h6>
<ul class="navbar-nav">

  <li class="nav-item">
      <a class="nav-link" href="{{ route ('home') }}">
        <i class="ni ni-tv-2 text-default"></i> Dashboard
      </a>
  </li>

  @if (auth()->user()->role == 'administrador' || auth()->user()->role == 'doctor')
    <li class="nav-item">
      <a class="nav-link" href="/users">
        <i class="ni ni-single-02 text-green"></i> Médicos
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="/patients">
        <i class="ni ni-satisfied text-info"></i> Pacientes
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="/rooms">
        <i class="ni ni-building text-danger"></i> Salas
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link" href="/shifts">
        <i class="ni ni-time-alarm text-primary"></i> Turnos
      </a>
    </li>

    <li class="nav-item acordion" id="atenciones">

      <div class="nav-link card-header" id="headingOne" data-toggle="collapse" data-target="#procedimientos" aria-expanded="true" aria-controls="collapseOne">
        <i class="ni ni-archive-2 text-red"></i> Procedimientos
      </div>

      <div id="procedimientos" class="collapse hide" aria-labelledby="headingOne" data-parent="#atenciones">

        <a class="nav-link" href="/orders">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Generar Historia
          </ul>
        </a>

        <a class="nav-link" href="/consults">
          <ul class="text-gray">
              <i class="ni ni-bold-right text-Secondary"></i> Consulta externa
          </ul>
        </a>

          <a class="nav-link" href="/corrections">
              <ul class="text-gray">
                  <i class="ni ni-pin-3 text-Secondary"></i> FUA Subsan.
              </ul>
          </a>

<!--        <a class="nav-link" href="/tracings">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Generar seguimiento
          </ul>
        </a>-->

        <a class="nav-link" href="/medicals">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Medicina
          </ul>
        </a>

        <a class="nav-link" href="/nurses">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Enfermeria
          </ul>
        </a>
      </div>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="/format006s">
            <i class="ni ni-curved-next text-dark"></i> Formato006
        </a>
    </li>

        <li class="nav-item">
            <a class="nav-link" href="/numerations">
                <i class="ni ni-curved-next text-dark"></i> Numeracion FUA
            </a>
        </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('medical.fissalweb') }}">
                    <i class="ni ni-curved-next text-dark"></i> FISSAL
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/nephrologies">
                    <i class="ni ni-curved-next text-dark"></i> CONSULTAS
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/recipes">
                    <i class="ni ni-curved-next text-dark"></i> RECETAS
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="/laboratories">
                    <i class="ni ni-curved-next text-dark"></i> LABORATORIOS
                </a>
            </li>



    <!--<li class="nav-item acordion" id="almacen">

      <div class="nav-link card-header" id="headingOne" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
        <i class="ni ni-shop text-primary"></i> Almacen
      </div>

      <div id="collapseOne" class="collapse hide" aria-labelledby="headingOne" data-parent="#almacen">

        <a class="nav-link" href="/categories">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Categoria
          </ul>
        </a>

        <a class="nav-link" href="/articles">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Articulos
          </ul>
        </a>

        <a class="nav-link" href="/tickets">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Entradas
          </ul>
        </a>

        <a class="nav-link" href="/departures">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Salidas
          </ul>
        </a>
      </div>
    </li>-->


  @elseif (auth()->user()->role == 'doctor' || auth()->user()->role == 'enfermera')

    <li class="nav-item acordion" id="atenciones">

      <div class="nav-link card-header" id="headingOne" data-toggle="collapse" data-target="#procedimientos" aria-expanded="true" aria-controls="collapseOne">
        <i class="ni ni-archive-2 text-red"></i> Procedimientos
      </div>

      <div id="procedimientos" class="collapse hide" aria-labelledby="headingOne" data-parent="#atenciones">

        <a class="nav-link" href="/orders">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Generar Historia
          </ul>
        </a>

        <a class="nav-link" href="/medicals">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Medicina
          </ul>
        </a>

        <a class="nav-link" href="/nurses">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Enfermeria
          </ul>
        </a>
      </div>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="/format006s">
            <i class="ni ni-curved-next text-dark"></i> Formato006
        </a>
    </li>
    </li>
  @elseif (auth()->user()->role == 'logistica')

  <!--ALMACEN -->
    <li class="nav-item acordion" id="almacen">

      <div class="nav-link card-header" id="headingOne" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
        <i class="ni ni-shop text-primary"></i> Almacen
      </div>

      <div id="collapseOne" class="collapse hide" aria-labelledby="headingOne" data-parent="#almacen">

        <a class="nav-link" href="/categories">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Categoria
          </ul>
        </a>

        <a class="nav-link" href="/articles">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Articulos
          </ul>
        </a>

        <a class="nav-link" href="/tickets">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Entradas
          </ul>
        </a>

        <a class="nav-link" href="/departures">
          <ul class="text-gray">
            <i class="ni ni-bold-right text-Secondary"></i> Salidas
          </ul>
        </a>
      </div>
    </li>
  @endif

  <li class="nav-item">
    <a class="nav-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('formLogout').submit();">
      <i class="ni ni-key-25"></i> Cerrar sesión
    </a>
    <form action="{{ route('logout') }}" method="POST" style="display: none;" id="formLogout">
      @csrf
    </form>
  </li>
</ul>

@if (auth()->user()->role == 'administrador')
<!-- Divider -->
<hr class="my-3">
<!-- Heading -->
<h6 class="navbar-heading text-muted">Reportes</h6>
<!-- Navigation -->
<ul class="navbar-nav mb-md-3">

  <li class="nav-item">
    <a class="nav-link" href="{{ url('/charts/orders/line') }}">
      <i class="ni ni-sound-wave text-yellow"></i>Atenciones por mes
    </a>
  </li>

  <!--<li class="nav-item">
    <a class="nav-link" href="{{ url('/charts/room/bar') }}">
      <i class="ni ni-spaceship text-orange"></i>Pacientes por mes
    </a>
  </li>-->
</ul>
@endif
