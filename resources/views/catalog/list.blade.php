@extends('layouts.app')

@section('content')
<div class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1">Listado de catálogos</h4>
            <p class="text-muted mb-0">Visualiza áreas, exámenes y perfiles creados.</p>
        </div>
        <a href="{{ route('catalog.index') }}" class="btn btn-primary rounded-pill px-4">+ Nuevo catálogo</a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th class="ps-3">Área</th>
                        <th>Exámenes</th>
                        <th class="text-end pe-3">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($areas as $area)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $area->name }}</td>
                            <td>
                                @foreach($area->tests as $test)
                                    <span class="badge text-bg-light border me-1 mb-1">{{ $test->name }}</span>
                                @endforeach
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('catalog.areas.edit', $area) }}" class="btn btn-sm btn-outline-primary rounded-pill">Editar área</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No hay áreas registradas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Perfil</th>
                            <th>Exámenes vinculados</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($profiles as $profile)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $profile->name }}</td>
                            <td>
                                @foreach($profile->tests as $test)
                                    <span class="badge text-bg-light border me-1 mb-1">{{ $test->name }}</span>
                                @endforeach
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('catalog.profiles.edit', $profile) }}" class="btn btn-sm btn-outline-primary rounded-pill">Editar perfil</a>
                                    <form method="POST" action="{{ route('catalog.profiles.destroy', $profile) }}" onsubmit="return confirm('¿Seguro que deseas eliminar este perfil?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No hay perfiles registrados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <div class="d-flex justify-content-end mt-3">
        {{ $profiles->links() }}
    </div>
</div>
@endsection
