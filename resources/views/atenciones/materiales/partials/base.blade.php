<div class="card module-card shadow-sm border-0 mb-3">
    <div class="card-header bg-secondary text-white fw-bold">Registrar nuevo material base</div>
    <div class="card-body">
        <form action="{{ route('extra-materials.base.store') }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-5">
                <label class="label-mini">Nombre del material</label>
                <input type="text" name="name" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-2">
                <label class="label-mini">Unidad</label>
                <input type="text" name="unit" class="form-control form-control-sm" value="unidad" required>
            </div>
            <div class="col-md-2">
                <label class="label-mini">Stock inicial</label>
                <input type="number" min="0" step="0.01" name="stock" class="form-control form-control-sm" value="0" required>
            </div>
            <div class="col-md-2">
                <label class="label-mini">Consumo por orden</label>
                <input type="number" min="0.01" step="0.01" name="quantity_per_order" class="form-control form-control-sm" value="1" required>
            </div>
            <div class="col-md-1">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="is_active_base">
                    <label class="form-check-label small" for="is_active_base">Activo</label>
                </div>
            </div>
            <div class="col-12">
                <button class="btn btn-secondary btn-sm fw-bold" type="submit">
                    <i class="bi bi-save me-1"></i> Guardar base
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card module-card shadow-sm border-0">
    <div class="card-header bg-white"><span class="section-title">Materiales base configurados</span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Material</th>
                        <th class="text-center">Historial</th>
                        <th class="text-center">Consumo por orden</th>
                        <th class="text-center">Stock actual</th>
                        <th class="text-center">Activo</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hemodialysisMaterials as $baseMaterial)
                        <tr>
                            <td class="ps-3 small">{{ $baseMaterial->name }}</td>
                            <td class="text-center small text-muted">
                                {{ $baseMaterial->consumptions_count }} atenciones
                            </td>
                            <td class="text-center" style="max-width: 150px;">
                                <div class="input-group input-group-sm">
                                    <input type="number" min="0.01" step="0.01" name="quantity_per_order" class="form-control form-control-sm text-center" value="{{ $baseMaterial->quantity_per_order }}" form="update-base-{{ $baseMaterial->id }}" required>
                                    <span class="input-group-text">{{ $baseMaterial->unit }}</span>
                                </div>
                            </td>
                            <td class="text-center" style="max-width: 140px;">
                                <input type="number" min="0" step="0.01" name="stock" class="form-control form-control-sm text-center" value="{{ $baseMaterial->stock }}" form="update-base-{{ $baseMaterial->id }}" required>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" form="update-base-{{ $baseMaterial->id }}" {{ $baseMaterial->is_active ? 'checked' : '' }}>
                            </td>
                            <td class="text-center">
                                <form id="update-base-{{ $baseMaterial->id }}" action="{{ route('extra-materials.base.update', $baseMaterial) }}" method="POST" class="d-inline-flex gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-primary btn-sm">Guardar</button>
                                </form>
                                <form action="{{ route('extra-materials.base.destroy', $baseMaterial) }}" method="POST" class="d-inline-flex">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Si el material tiene atenciones previas solo se desactivará para no afectar el historial. ¿Desea continuar?')">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<p class="small text-muted mt-2 mb-0">
    Los cambios en consumo/estado se aplican a sesiones futuras. Las atenciones ya registradas mantienen su historial.
</p>
