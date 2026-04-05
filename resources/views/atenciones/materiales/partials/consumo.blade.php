<div class="alert alert-info shadow-sm" role="alert">
    El consumo automático se procesa solo cuando la orden tiene <strong>medicina finalizada</strong>, <strong>enfermería finalizada</strong> y al menos un <strong>registro de treatment</strong> con hora.
</div>

<div class="card module-card shadow-sm border-0 mb-3">
    <div class="card-header bg-white"><span class="section-title">Consumo automático mensual por paciente</span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Paciente</th>
                        <th class="text-center">Órdenes finalizadas</th>
                        <th class="text-end pe-3">Total unidades consumidas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consumptionSummary as $summary)
                        <tr>
                            <td class="ps-3 small">{{ $summary->patient->surname }} {{ $summary->patient->last_name }}, {{ $summary->patient->first_name }}</td>
                            <td class="text-center">{{ $summary->records }}</td>
                            <td class="text-end pe-3 fw-bold">{{ number_format($summary->total_quantity, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-3 text-muted">Sin consumo automático para el mes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card module-card shadow-sm border-0">
    <div class="card-header bg-white"><span class="section-title">Detalle de consumo automático por orden</span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Fecha</th>
                        <th>Paciente</th>
                        <th>Orden</th>
                        <th>Material</th>
                        <th class="text-end pe-3">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consumptions as $consumption)
                        <tr>
                            <td class="ps-3">{{ $consumption->consumed_at->format('Y-m-d') }}</td>
                            <td class="small">{{ $consumption->patient->surname }} {{ $consumption->patient->last_name }}, {{ $consumption->patient->first_name }}</td>
                            <td>{{ $consumption->order->codigo_unico ?? '-' }}</td>
                            <td>{{ $consumption->material->name ?? '-' }}</td>
                            <td class="text-end pe-3 fw-bold">{{ number_format($consumption->quantity, 2) }} {{ $consumption->material->unit ?? '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No hay consumos automáticos en este mes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">{{ $consumptions->appends(request()->all())->links() }}</div>
</div>
