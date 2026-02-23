<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="bg-light">
                <tr class="text-uppercase text-muted" style="font-size: 0.75rem;">
                    <th class="ps-3">Sala / Turno</th>
                    <th>Paciente / DNI</th>
                    <th class="text-center"># de Sesión</th>
                    <th class="text-center">Puesto</th>
                    <th class="text-center">Estado</th>
                    <th>Responsable</th>
                    <th class="text-end pe-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($nurses as $nurse)
                <tr>
                    <td class="ps-3">
                        <div class="fw-bold text-primary">{{ $nurse->order->sala }}</div>
                        <div class="badge bg-secondary" style="font-size: 0.65rem;">{{ $nurse->order->turno }}º TURNO</div>
                    </td>
                    <td>
                        <div class="fw-bold text-dark">{{ $nurse->order->patient->surname }} {{ $nurse->order->patient->last_name }}, {{ $nurse->order->patient->first_name }} {{ $nurse->order->patient->other_names }}</div>
                        <div class="text-muted small"><i class="bi bi-person-badge"></i> {{ $nurse->order->patient->dni }}</div>
                    </td>
                    <td class="text-center"><span class="badge bg-primary px-3 py-2" style="font-size: 0.9rem;">
                            {{ $nurse->numero_hd ?? 'S/P' }}
                        </span></td>
                    <td class="text-center">
                        <span class="badge bg-dark px-3 py-2" style="font-size: 0.9rem;">
                            {{ $nurse->puesto ?? 'S/P' }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($nurse->enfermero_que_finaliza_id)
                            <span class="badge bg-success-subtle text-success border border-success px-3">
                                <i class="bi bi-check-circle-fill me-1"></i> FINALIZADO
                            </span>
                        @else
                            <span class="badge bg-warning-subtle text-warning border border-warning px-3">
                                <i class="bi bi-clock-history me-1"></i> EN CURSO
                            </span>
                        @endif
                    </td>
                    <td>
                        <div class="small fw-bold">{{ $nurse->enfermeroInicia->name ?? '---' }}</div>
                        <div class="text-muted" style="font-size: 0.7rem;">Sesión #{{ $nurse->numero_hd }}</div>
                    </td>
                    <td class="text-end pe-3">
                        <a href="{{ route('nurses.edit', $nurse->id) }}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                            <i class="bi bi-pencil-square me-1"></i> Atender
                        </a>

                        <a href="{{ route('enfermeria.print.single', $nurse->order->id) }}" target="_blank" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-printer"></i> Imprimir
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-1 d-block opacity-25"></i>
                        No se encontraron registros de enfermería para esta fecha o filtros.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($nurses->hasPages())
    <div class="card-footer bg-white py-3">
        {{ $nurses->links() }}
    </div>
    @endif
</div>