<div class="modal fade" id="createRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content" method="POST" action="{{ route('warehouse.requests.store') }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Nueva solicitud al almacén principal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Observaciones</label>
            <textarea name="observations" class="form-control" rows="2"></textarea>
        </div>

        <div class="table-responsive">
          <table class="table table-sm align-middle" id="itemsTable">
            <thead>
              <tr>
                <th>Material</th>
                <th>Cantidad solicitada</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <select name="items[0][warehouse_material_id]" class="form-select" required>
                    <option value="">Seleccione...</option>
                    @foreach($materials as $material)
                      <option value="{{ $material->id }}">{{ $material->name }} ({{ $material->unit }})</option>
                    @endforeach
                  </select>
                </td>
                <td><input type="number" step="0.01" min="0.01" name="items[0][qty_requested]" class="form-control" required></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
              </tr>
            </tbody>
          </table>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addRequestRow()"><i class="bi bi-plus"></i> Agregar línea</button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Guardar solicitud</button>
      </div>
    </form>
  </div>
</div>

<script>
function addRequestRow() {
    const tbody = document.querySelector('#itemsTable tbody');
    const idx = tbody.querySelectorAll('tr').length;
    const options = `@foreach($materials as $material)<option value="{{ $material->id }}">{{ $material->name }} ({{ $material->unit }})</option>@endforeach`;

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="items[${idx}][warehouse_material_id]" class="form-select" required>
                <option value="">Seleccione...</option>
                ${options}
            </select>
        </td>
        <td><input type="number" step="0.01" min="0.01" name="items[${idx}][qty_requested]" class="form-control" required></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}
</script>
