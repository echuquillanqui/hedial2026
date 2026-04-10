<div class="modal fade" id="createRequestModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form class="modal-content" method="POST" action="{{ route('warehouse.requests.store') }}" id="warehouseRequestForm">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Nueva solicitud de materiales</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Sede destino</label>
            <select name="to_warehouse_id" class="form-select" required>
              @if($availableWarehouses->count() !== 1)
                <option value="">Seleccione una sede...</option>
              @endif
              @foreach($availableWarehouses as $warehouseOption)
                <option value="{{ $warehouseOption->id }}" @selected($warehouseOption->is_principal)>
                  {{ $warehouseOption->sede?->name ?? $warehouseOption->name }} {{ $warehouseOption->is_principal ? '(Principal)' : '' }}
                </option>
              @endforeach
            </select>
            @if($availableWarehouses->count() === 1 && $availableWarehouses->first()?->is_principal)
              <small class="text-muted d-block mt-1">Las solicitudes de esta sede se consolidan automáticamente hacia el almacén principal.</small>
            @endif
        </div>
        <div class="mb-3">
            <label class="form-label">Área operativa solicitante</label>
            <select name="operational_area_id" class="form-select">
              <option value="">Sin área específica...</option>
              @foreach($operationalAreas as $areaOption)
                <option value="{{ $areaOption->id }}">
                  {{ $areaOption->name }} ({{ $areaOption->sede?->name ?? 'Sin sede' }})
                </option>
              @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Observaciones</label>
            <textarea name="observations" class="form-control" rows="2"></textarea>
        </div>

        <div class="table-responsive">
          <table class="table table-sm align-middle" id="itemsTable">
            <thead>
              <tr>
                <th style="width: 90px;">
                  <div class="form-check m-0">
                    <input type="checkbox" class="form-check-input" id="toggleAllItems" checked>
                    <label class="form-check-label" for="toggleAllItems">Incluir</label>
                  </div>
                </th>
                <th>Material</th>
                <th>Cantidad solicitada</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <input type="checkbox" class="form-check-input item-include" checked>
                </td>
                <td>
                  <select name="items[0][warehouse_material_id]" class="form-select" required>
                    <option value="">Seleccione...</option>
                    @foreach($materials as $material)
                      <option value="{{ $material->id }}">{{ $material->category?->name ?? 'Sin categoría' }} - {{ $material->name }} ({{ $material->unit }})</option>
                    @endforeach
                  </select>
                </td>
                <td><input type="number" step="0.01" min="0.01" name="items[0][qty_requested]" class="form-control" required></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('tr').remove(); syncSelectAllCheckbox();"><i class="bi bi-trash"></i></button></td>
              </tr>
            </tbody>
          </table>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addRequestRow()"><i class="bi bi-plus"></i> Agregar línea</button>
          <small class="text-muted d-block mt-2">Antes de enviar, puede desmarcar ítems que no necesita y ajustar cantidades en el consolidado.</small>
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
    const options = `@foreach($materials as $material)<option value="{{ $material->id }}">{{ $material->category?->name ?? 'Sin categoría' }} - {{ $material->name }} ({{ $material->unit }})</option>@endforeach`;

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <input type="checkbox" class="form-check-input item-include" checked>
        </td>
        <td>
            <select name="items[${idx}][warehouse_material_id]" class="form-select" required>
                <option value="">Seleccione...</option>
                ${options}
            </select>
        </td>
        <td><input type="number" step="0.01" min="0.01" name="items[${idx}][qty_requested]" class="form-control" required></td>
        <td><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('tr').remove(); syncSelectAllCheckbox();"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    syncSelectAllCheckbox();
}

function syncSelectAllCheckbox() {
    const items = Array.from(document.querySelectorAll('#itemsTable tbody .item-include'));
    const toggle = document.getElementById('toggleAllItems');

    if (!toggle) {
        return;
    }

    toggle.checked = items.length > 0 && items.every((input) => input.checked);
}

document.addEventListener('change', function (event) {
    if (event.target && event.target.id === 'toggleAllItems') {
        const checked = event.target.checked;
        document.querySelectorAll('#itemsTable tbody .item-include').forEach((input) => {
            input.checked = checked;
        });
    }

    if (event.target && event.target.classList.contains('item-include')) {
        syncSelectAllCheckbox();
    }
});

document.getElementById('warehouseRequestForm')?.addEventListener('submit', function (event) {
    const rows = Array.from(document.querySelectorAll('#itemsTable tbody tr'));
    const selectedRows = rows.filter((row) => row.querySelector('.item-include')?.checked);

    if (selectedRows.length === 0) {
        event.preventDefault();
        alert('Debe seleccionar al menos un ítem para enviar la solicitud.');

        return;
    }

    const tbody = document.querySelector('#itemsTable tbody');

    selectedRows.forEach((row, idx) => {
        row.querySelectorAll('select[name], input[name]').forEach((input) => {
            input.name = input.name.replace(/items\[\d+\]/, `items[${idx}]`);
        });
    });

    rows.filter((row) => !selectedRows.includes(row)).forEach((row) => {
        row.querySelectorAll('select[name], input[name]').forEach((input) => {
            input.removeAttribute('name');
        });
    });

    tbody.innerHTML = '';
    selectedRows.forEach((row) => tbody.appendChild(row));
});
</script>
