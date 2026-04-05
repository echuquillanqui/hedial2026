<div class="modal fade" id="receiveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <form class="modal-content" method="POST" id="receiveForm" data-action-template="{{ url('almacen/solicitudes/__ID__/recepcion') }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Registrar recepción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        @foreach($requests as $req)
          <div x-show="receiveRequestId === {{ $req->id }}">
            @foreach($req->items as $idx => $item)
              <div class="row g-2 border rounded p-2 mb-2">
                <div class="col-md-4"><strong>{{ $item->material->name }}</strong></div>
                <div class="col-md-2">Enviado: {{ number_format($item->qty_sent,2) }}</div>
                <div class="col-md-3">
                  <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}">
                  <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="items[{{ $idx }}][qty_received]" value="{{ $item->qty_received }}" placeholder="Recibido">
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
