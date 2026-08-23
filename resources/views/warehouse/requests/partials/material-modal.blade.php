<div class="modal fade" id="materialModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="{{ route('warehouse.materials.store') }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Registrar material</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Código</label>
          <input type="text" name="code" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Nombre</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Categoría</label>
          <select name="warehouse_material_category_id" class="form-select" required>
            <option value="">Seleccione...</option>
            @foreach($categories as $category)
              <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Unidad</label>
          <input type="text" name="unit" class="form-control" required placeholder="UND, CAJA, BOLSA...">
        </div>
        <div class="row g-2">
          <div class="col-12">
            <label class="form-label">Stock mínimo</label>
            <input type="number" name="min_qty" class="form-control" min="0.01" step="0.01" value="1" required>
            <small class="text-muted">Se mostrará una alerta cuando el stock llegue a este valor.</small>
          </div>
        </div>
        <div class="alert alert-info py-2 mt-3 mb-0"><i class="bi bi-info-circle me-1"></i> El stock inicial se registra después en <strong>Ingresos</strong>, indicando proveedor y fecha de vencimiento.</div>
        <div class="form-check form-switch mt-3">
          <input class="form-check-input" type="checkbox" name="automatic_consumption" value="1" id="newAutomaticConsumption">
          <label class="form-check-label" for="newAutomaticConsumption">Consumo automático por sesión</label>
        </div>
        <div class="mt-2">
          <label class="form-label">Cantidad por sesión <small class="text-muted">(si aplica)</small></label>
          <input type="number" name="quantity_per_session" class="form-control" min="0.01" step="0.01" value="1">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>
