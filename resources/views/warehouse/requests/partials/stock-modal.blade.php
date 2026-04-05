<div class="modal fade" id="stockModal" tabindex="-1" aria-hidden="true" x-cloak>
  <div class="modal-dialog">
    <form class="modal-content" method="POST" :action="`{{ url('almacen/stocks') }}/${stockId}`">
      @csrf
      @method('PATCH')
      <div class="modal-header">
        <h5 class="modal-title">Ajustar stock local</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Stock actual</label>
          <input type="number" step="0.01" min="0" name="current_qty" class="form-control" x-model="stockCurrent" required>
        </div>
        <div>
          <label class="form-label">Stock mínimo</label>
          <input type="number" step="0.01" min="0" name="min_qty" class="form-control" x-model="stockMin" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>
