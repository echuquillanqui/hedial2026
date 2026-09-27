<div class="col-lg-6">
 <form method="POST" action="{{ route('nursing-annexes.discards.store', $order) }}" class="border rounded bg-white p-3 h-100">
  @csrf
  <input type="hidden" name="category" value="{{ $category }}">
  <input type="hidden" name="discarded_at" value="{{ $order->fecha_orden->format('Y-m-d') }} 23:59">
  <input type="hidden" name="discard_reason" value="{{ $defaultReason }}">
  <h6>{{ $title }}</h6>
  <label class="form-label fw-semibold" for="lot-{{ $category }}-{{ $order->id }}">{{ $lotLabel }}</label>
  <input id="lot-{{ $category }}-{{ $order->id }}" name="lot_number" class="form-control mb-2" maxlength="80" required placeholder="Ej.: LOTE-2026-001">
  <div class="row g-2 mb-2">
   <div class="col-sm-6"><label class="form-label fw-semibold" for="valid-from-{{ $category }}-{{ $order->id }}">Validez desde</label><input id="valid-from-{{ $category }}-{{ $order->id }}" name="valid_from" type="date" class="form-control" required></div>
   <div class="col-sm-6"><label class="form-label fw-semibold" for="valid-until-{{ $category }}-{{ $order->id }}">Validez hasta</label><input id="valid-until-{{ $category }}-{{ $order->id }}" name="valid_until" type="date" class="form-control" required></div>
  </div>
  <label class="form-label" for="observations-{{ $category }}-{{ $order->id }}">Observaciones (opcional)</label>
  <textarea id="observations-{{ $category }}-{{ $order->id }}" name="observations" class="form-control mb-2" rows="2" maxlength="1000"></textarea>
  <button class="btn btn-success btn-sm"><i class="bi bi-floppy me-1"></i>Guardar {{ mb_strtolower($title) }}</button>
 </form>
</div>
