<div class="card module-card shadow-sm border-0 mb-3">
    <div class="card-header bg-primary text-white fw-bold">Registrar material extra</div>
    <div class="card-body">
        <form action="{{ route('extra-materials.store') }}" method="POST" class="row g-2">
            @csrf
            <div class="col-md-6">
                <label class="label-mini">Paciente</label>
                <select name="patient_id" class="form-select form-select-sm js-patient-select @error('patient_id') is-invalid @enderror" required>
                    <option value="">-- Seleccionar --</option>
                    @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                            {{ $patient->surname }} {{ $patient->last_name }}, {{ $patient->first_name }} {{ $patient->other_names }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="label-mini">Orden (Opcional)</label>
                <select name="order_id" class="form-select form-select-sm @error('order_id') is-invalid @enderror">
                    <option value="">-- Sin orden específica --</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                            {{ $order->codigo_unico }} - {{ $order->fecha_orden }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="label-mini">Fecha de uso</label>
                <input type="date" name="usage_date" class="form-control form-control-sm" value="{{ old('usage_date', now()->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-5">
                <label class="label-mini">Material</label>
                <input type="text" name="material_name" class="form-control form-control-sm" value="{{ old('material_name') }}" placeholder="Ej. Dializador extra" required>
            </div>
            <div class="col-md-2">
                <label class="label-mini">Cantidad</label>
                <input type="number" min="0.01" step="0.01" name="quantity" class="form-control form-control-sm" value="{{ old('quantity', 1) }}" required>
            </div>
            <div class="col-md-2">
                <label class="label-mini">Costo unitario</label>
                <input type="number" min="0" step="0.01" name="unit_cost" class="form-control form-control-sm" value="{{ old('unit_cost', 0) }}" required>
            </div>
            <div class="col-12">
                <label class="label-mini">Observación</label>
                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Opcional">{{ old('notes') }}</textarea>
            </div>
            <div class="col-12 mt-2">
                <button class="btn btn-primary btn-sm fw-bold" type="submit"><i class="bi bi-plus-circle me-1"></i> Guardar material</button>
            </div>
        </form>
    </div>
</div>

<div class="card module-card shadow-sm border-0">
    <div class="card-header bg-white"><span class="section-title">Detalle de materiales extra registrados</span></div>
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
    <div class="card-footer bg-white">{{ $materials->appends(request()->all())->links() }}</div>
</div>
