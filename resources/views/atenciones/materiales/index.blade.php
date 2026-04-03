@extends('layouts.app')

@section('content')
<style>
    .module-card { border-radius: 14px; }
    .section-title { font-size: .8rem; font-weight: 800; text-transform: uppercase; color: #0d6efd; }
    .label-mini { font-size: .7rem; font-weight: 700; text-transform: uppercase; color: #6c757d; margin-bottom: 4px; }
</style>

<div class="container px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="m-0 fw-bold text-primary text-uppercase"><i class="bi bi-box-seam me-2"></i>Materiales Extra por Hemodiálisis</h4>
            <small class="text-muted">Registro por paciente y consolidado mensual de gasto.</small>
        </div>
        <a href="{{ route('extra-materials.report.monthly', ['month' => request('month', $month)]) }}" class="btn btn-success fw-bold">
            <i class="bi bi-filetype-csv me-1"></i> Descargar Reporte Mensual
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card module-card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-bold">Registrar material</div>
                <div class="card-body">
                    <form action="{{ route('extra-materials.store') }}" method="POST" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="label-mini">Paciente</label>
                            <select name="patient_id" class="form-select form-select-sm @error('patient_id') is-invalid @enderror" required>
                                <option value="">-- Seleccionar --</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                                        {{ $patient->surname }} {{ $patient->last_name }}, {{ $patient->first_name }} {{ $patient->other_names }}
                                    </option>
                                @endforeach
                            </select>
                            @error('patient_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="label-mini">Orden (Opcional)</label>
                            <select name="order_id" class="form-select form-select-sm @error('order_id') is-invalid @enderror">
                                <option value="">-- Sin orden específica --</option>
                                @foreach($orders as $order)
                                    <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                                        {{ $order->codigo_unico }} - {{ $order->fecha_orden }}
                                    </option>
                                @endforeach
                            </select>
                            @error('order_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="label-mini">Fecha de uso</label>
                            <input type="date" name="usage_date" class="form-control form-control-sm @error('usage_date') is-invalid @enderror" value="{{ old('usage_date', now()->format('Y-m-d')) }}" required>
                            @error('usage_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="label-mini">Material</label>
                            <input type="text" name="material_name" class="form-control form-control-sm @error('material_name') is-invalid @enderror" value="{{ old('material_name') }}" placeholder="Ej. Dializador extra" required>
                            @error('material_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-6">
                            <label class="label-mini">Cantidad</label>
                            <input type="number" min="0.01" step="0.01" name="quantity" class="form-control form-control-sm @error('quantity') is-invalid @enderror" value="{{ old('quantity', 1) }}" required>
                            @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-6">
                            <label class="label-mini">Costo unitario</label>
                            <input type="number" min="0" step="0.01" name="unit_cost" class="form-control form-control-sm @error('unit_cost') is-invalid @enderror" value="{{ old('unit_cost', 0) }}" required>
                            @error('unit_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="label-mini">Observación</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Opcional">{{ old('notes') }}</textarea>
                        </div>

                        <div class="col-12 mt-2">
                            <button class="btn btn-primary btn-sm fw-bold w-100" type="submit">
                                <i class="bi bi-plus-circle me-1"></i> Guardar material
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card module-card shadow-sm border-0 mb-3">
                <div class="card-body bg-light py-3">
                    <form action="{{ route('extra-materials.index') }}" method="GET" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="label-mini">Mes</label>
                            <input type="month" name="month" class="form-control form-control-sm" value="{{ request('month', $month) }}">
                        </div>
                        <div class="col-md-5">
                            <label class="label-mini">Paciente</label>
                            <select name="patient_id" class="form-select form-select-sm">
                                <option value="">-- Todos --</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}" {{ request('patient_id') == $patient->id ? 'selected' : '' }}>
                                        {{ $patient->surname }} {{ $patient->last_name }}, {{ $patient->first_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-sm btn-outline-primary fw-bold w-100" type="submit">
                                <i class="bi bi-funnel me-1"></i> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

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

            <div class="card module-card shadow-sm border-0 mb-3">
                <div class="card-header bg-white"><span class="section-title">Materiales base por hemodiálisis (consumo automático)</span></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Material</th>
                                    <th class="text-center">Consumo por orden</th>
                                    <th class="text-center">Stock actual</th>
                                    <th class="text-center">Activo</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($hemodialysisMaterials as $baseMaterial)
                                    <tr>
                                        <form action="{{ route('extra-materials.base.update', $baseMaterial) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <td class="ps-3 small">{{ $baseMaterial->name }}</td>
                                            <td class="text-center" style="max-width: 150px;">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" min="0.01" step="0.01" name="quantity_per_order" class="form-control form-control-sm text-center" value="{{ $baseMaterial->quantity_per_order }}" required>
                                                    <span class="input-group-text">{{ $baseMaterial->unit }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center" style="max-width: 140px;">
                                                <input type="number" min="0" step="0.01" name="stock" class="form-control form-control-sm text-center" value="{{ $baseMaterial->stock }}" required>
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $baseMaterial->is_active ? 'checked' : '' }}>
                                            </td>
                                            <td class="text-center">
                                                <button type="submit" class="btn btn-outline-primary btn-sm">Guardar</button>
                                            </td>
                                        </form>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card module-card shadow-sm border-0 mb-3">
                <div class="card-header bg-white"><span class="section-title">Consumo automático mensual por paciente</span></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Paciente</th>
                                    <th class="text-center">Órdenes</th>
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
                <div class="card-header bg-white"><span class="section-title">Detalle de materiales registrados</span></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Fecha</th>
                                    <th>Paciente</th>
                                    <th>Material</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Unit.</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materials as $item)
                                    <tr>
                                        <td class="ps-3">{{ $item->usage_date }}</td>
                                        <td class="small">{{ $item->patient->surname }} {{ $item->patient->last_name }}, {{ $item->patient->first_name }}</td>
                                        <td>{{ $item->material_name }}</td>
                                        <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="text-end">S/ {{ number_format($item->unit_cost, 2) }}</td>
                                        <td class="text-end fw-bold">S/ {{ number_format($item->total_cost, 2) }}</td>
                                        <td class="text-center">
                                            <form action="{{ route('extra-materials.destroy', $item) }}" method="POST" onsubmit="return confirm('¿Eliminar este registro?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center py-4 text-muted">No hay materiales registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white">{{ $materials->links() }}</div>
            </div>

            <div class="card module-card shadow-sm border-0 mt-3">
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
                <div class="card-footer bg-white">{{ $consumptions->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
