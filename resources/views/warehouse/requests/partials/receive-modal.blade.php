<div class="modal fade" id="receiveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <form class="modal-content" method="POST" id="receiveForm" data-action-template="{{ url('almacen/solicitudes/__ID__/recepcion') }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Registrar recepción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small">Seleccione el estado de recepción por material. Si marca "Completo" o "No recepcionado", la cantidad se ajusta automáticamente.</p>
        @foreach($requests as $req)
          <div x-show="receiveRequestId === {{ $req->id }}">
            @foreach($req->items as $idx => $item)
              @php($isLocked = ($item->receive_status ?? 'pending') === 'complete')
              <div class="row g-2 border rounded p-2 mb-2">
                <div class="col-md-3"><strong>{{ $item->material->name }}</strong></div>
                <div class="col-md-2">Enviado: {{ number_format($item->qty_sent,2) }}</div>
                <div class="col-md-2">
                  <select class="form-select form-select-sm" name="items[{{ $idx }}][receive_status]" required @disabled($isLocked)>
                    <option value="pending" @selected(($item->receive_status ?? 'pending') === 'pending')>Pendiente</option>
                    <option value="not_received" @selected(($item->receive_status ?? 'pending') === 'not_received')>No recepcionado</option>
                    <option value="partial" @selected(($item->receive_status ?? 'pending') === 'partial')>Parcial</option>
                    <option value="complete" @selected(($item->receive_status ?? 'pending') === 'complete')>Completo</option>
                  </select>
                  @if($isLocked)
                  <input type="hidden" name="items[{{ $idx }}][receive_status]" value="complete">
                  @endif
                </div>
                <div class="col-md-2">
                  <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}">
                  <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="items[{{ $idx }}][qty_received]" value="{{ $item->qty_received }}" placeholder="Recibido" @readonly($isLocked)>
                </div>
                <div class="col-md-3">
                  <input type="text" class="form-control form-control-sm" name="items[{{ $idx }}][not_received_reason]" value="{{ $item->not_received_reason }}" placeholder="Observación de recepción" @readonly($isLocked)>
                  @if($isLocked)
                  <small class="text-success">Material bloqueado por recepción completa.</small>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        @endforeach
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-success">Guardar recepción</button>
      </div>
    </form>
  </div>
</div>
