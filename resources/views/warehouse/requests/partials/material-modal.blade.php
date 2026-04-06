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
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>
