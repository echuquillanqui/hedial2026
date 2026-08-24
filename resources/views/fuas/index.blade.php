@extends('layouts.app')

@section('content')
<div class="container py-3" x-data="fuaPdfViewer()">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h3 class="mb-1"><i class="bi bi-file-earmark-medical text-primary me-2"></i>Formatos Únicos de Atención</h3>
            <p class="text-muted mb-0">Consulta los formatos generados y revisa el documento antes de descargarlo.</p>
        </div>
        @can('fua.configuration.manage')<a href="{{ route('fuas.configuration.edit') }}" class="btn btn-outline-primary"><i class="bi bi-gear me-2"></i>Configurar FUA</a>@endcan
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form class="row g-2">
                <div class="col-md-7"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por FUA, paciente o DNI"></div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">Todos los tipos</option>
                        <option value="HEMODIALYSIS" @selected(request('type') === 'HEMODIALYSIS')>Hemodiálisis</option>
                        <option value="NEPHROLOGY" @selected(request('type') === 'NEPHROLOGY')>Consulta nefrológica</option>
                        <option value="NUTRITION" @selected(request('type') === 'NUTRITION')>Nutrición</option>
                        <option value="PSYCHOLOGY" @selected(request('type') === 'PSYCHOLOGY')>Psicología</option>
                        <option value="SOCIAL_WORK" @selected(request('type') === 'SOCIAL_WORK')>Trabajo Social</option>
                        <option value="CORRECTION" @selected(request('type') === 'CORRECTION')>Subsanación</option>
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Buscar</button></div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>FUA</th><th>Paciente</th><th>Atención</th><th>Fecha</th><th>Estado</th><th class="text-end">Documento</th></tr></thead>
                <tbody>
                @forelse($fuas as $fua)
                    <tr>
                        <td><strong class="text-primary">{{ $fua->number }}</strong><div class="small text-muted">Serie {{ $fua->series }}</div></td>
                        <td><strong>{{ $fua->order?->patient?->full_name ?? 'Sin paciente' }}</strong><div class="small text-muted">DNI {{ $fua->order?->patient?->dni ?? '—' }}</div></td>
                        <td>{{ ['HEMODIALYSIS' => 'Hemodiálisis', 'NEPHROLOGY' => 'Consulta nefrológica', 'NUTRITION' => 'Nutrición', 'PSYCHOLOGY' => 'Psicología', 'SOCIAL_WORK' => 'Trabajo Social', 'CORRECTION' => 'Subsanación'][$fua->type] ?? $fua->type }}</td>
                        <td>{{ $fua->order?->fecha_orden ? \Carbon\Carbon::parse($fua->order->fecha_orden)->format('d/m/Y') : $fua->created_at->format('d/m/Y') }}</td>
                        <td><span class="badge bg-success-subtle text-success-emphasis">{{ $fua->status }}</span></td>
                        <td class="text-end text-nowrap">
                            <button type="button" @click="openPdf('{{ route('fuas.pdf', $fua) }}', 'FUA {{ $fua->number }}')" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>Vista previa</button>
                            <a href="{{ route('fuas.pdf', [$fua, 'download' => 1]) }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-download"></i></a>
                            @can('fua.correction.create')
                                @if($fua->type !== \App\Models\Fua::CORRECTION && $fua->corrections->isEmpty())<form class="d-inline" method="POST" action="{{ route('fuas.corrections.store', $fua) }}">@csrf<button class="btn btn-sm btn-outline-warning" title="Generar subsanación"><i class="bi bi-arrow-counterclockwise"></i></button></form>@endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Todavía no hay FUA generadas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($fuas->hasPages())<div class="card-footer bg-white">{{ $fuas->links() }}</div>@endif
    </div>
    @include('fuas.partials.pdf-modal')
</div>

@include('fuas.partials.pdf-modal-script')
@endsection
