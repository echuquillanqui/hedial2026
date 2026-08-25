<form method="GET" class="card card-body shadow-sm mb-4" data-audit-filters>
    <div class="row g-3 align-items-end">
        <div class="col-lg-3"><label class="form-label">Paciente, DNI u orden</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Buscar..."></div>
        <div class="col-sm-6 col-lg-2"><label class="form-label">Fecha</label><input type="date" class="form-control" name="date" value="{{ request('date', today()->toDateString()) }}"></div>
        <div class="col-sm-6 col-lg-2"><label class="form-label">Módulo</label><select class="form-select" name="modulo"><option value="">Todos</option>@foreach(range(1, 4) as $module)<option value="{{ $module }}" @selected((string) request('modulo') === (string) $module)>MÓDULO {{ $module }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-lg-2"><label class="form-label">Turno</label><select class="form-select" name="turno"><option value="">Todos</option>@foreach(range(1, 4) as $shift)<option value="{{ $shift }}" @selected((string) request('turno') === (string) $shift)>Turno {{ $shift }}</option>@endforeach</select></div>
        @if($showStatus ?? false)<div class="col-sm-6 col-lg-2"><label class="form-label">Estado</label><select class="form-select" name="estado"><option value="">Todos</option><option value="completo" @selected(request('estado') === 'completo')>Completo</option><option value="incompleto" @selected(request('estado') === 'incompleto')>Por corregir</option></select></div>@endif
        <div class="col-lg-auto"><a class="btn btn-outline-secondary" href="{{ url()->current() }}">Limpiar</a></div>
    </div>
</form>

@include('audit._filters-script')
