<div class="modal fade" id="dispatchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <form class="modal-content" method="POST" id="dispatchForm" data-action-template="{{ url('almacen/solicitudes/__ID__/despacho') }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Registrar despacho</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small">Indique cantidad enviada y motivo si no se envió.</p>
        @foreach($requests as $req)
          <div x-show="dispatchRequestId === {{ $req->id }}">
            @foreach($req->items as $idx => $item)
              <div class="row g-2 border rounded p-2 mb-2">
                <div class="col-md-3"><strong>{{ $item->material->name }}</strong></div>
                <div class="col-md-2">Solic: {{ number_format($item->qty_requested,2) }}</div>
                <div class="col-md-2">Aprob: {{ number_format($item->qty_approved,2) }}</div>
                <div class="col-md-2">
                  <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}">
                  <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="items[{{ $idx }}][qty_sent]" value="{{ $item->qty_sent }}" placeholder="Enviado">
                </div>
                <div class="col-md-3"><input type="text" class="form-control form-control-sm" name="items[{{ $idx }}][not_sent_reason]" value="{{ $item->not_sent_reason }}" placeholder="Motivo no enviado"></div>
              </div>
            @endforeach
          </div>
        @endforeach
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
        <button class="btn btn-warning">Guardar despacho</button>
      </div>
    </form>
  </div>
</div>
