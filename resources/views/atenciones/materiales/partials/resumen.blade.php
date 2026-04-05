<div class="card module-card shadow-sm border-0 mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="section-title">Resumen mensual por paciente</span>
        <span class="badge bg-primary">Total: S/ {{ number_format($totalMonth, 2) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Paciente</th>
                        <th class="text-center">Registros</th>
                        <th class="text-end pe-3">Gasto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaryByPatient as $summary)
                        <tr>
                            <td class="small">{{ $summary->patient->surname }} {{ $summary->patient->last_name }}, {{ $summary->patient->first_name }} {{ $summary->patient->other_names }}</td>
                            <td class="text-center">{{ $summary->records }}</td>
                            <td class="text-end pe-3 fw-bold">S/ {{ number_format($summary->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-3 text-muted">Sin registros para el mes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
