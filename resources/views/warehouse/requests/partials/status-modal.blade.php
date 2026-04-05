<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true" x-cloak>
  <div class="modal-dialog">
    <form class="modal-content" method="POST" :action="`{{ url('almacen/solicitudes') }}/${statusRequestId}/estado`">
      @csrf
      @method('PATCH')
      <div class="modal-header">
        <h5 class="modal-title">Actualizar estado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Estado</label>
          <select name="status" class="form-select" x-model="statusValue" required>
            <option value="draft">BORRADOR</option>
            <option value="submitted">ENVIADO</option>
            <option value="approved">APROBADO</option>
            <option value="rejected">RECHAZADO</option>
            <option value="cancelled">CANCELADO</option>
          </select>
        </div>
        <div>
          <label class="form-label">Comentario</label>
          <textarea name="comment" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>
