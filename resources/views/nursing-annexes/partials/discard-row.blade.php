<tr>
 <td><span class="fw-semibold">{{ $order->codigo_unico }}</span><br><small class="text-muted">{{ $order->fecha_orden->format('d/m/Y') }}</small></td>
 <td>{{ $order->patient->full_name }}<br><small class="text-muted">HC {{ $order->patient->medical_history_number ?: '—' }}</small></td>
 <td><span class="badge bg-light text-dark border">T{{ $order->turno }}</span> <span class="badge bg-light text-dark border">M{{ $order->patient->modulo }}</span><br><small class="text-muted">{{ $order->patient->secuencia }}</small></td>
 <td><strong>{{ $order->medical?->area_filtro ? $order->medical->area_filtro.' m²' : ($order->nurse?->filtro ?: 'Sin medida') }}</strong><br><small class="text-muted">{{ $dialyzerLot ? 'Lote '.$dialyzerLot->lot_number : 'Sin lote global vigente' }}</small></td>
 <td>
  @if($lines)
   <strong>{{ (float) $lines->quantity }} {{ $lines->material?->unit }}</strong><br><small class="text-muted">{{ $lines->material?->name }}</small>
  @else
   <span class="text-muted">Sin consumo configurado</span>
  @endif
 </td>
 <td class="text-center">
  @if($dialyzerDiscard)
   <span class="badge text-bg-success d-block mb-1">Dializador: {{ $dialyzerDiscard->lot_number }}</span><small class="text-muted d-block mb-1">Vigencia: {{ $dialyzerDiscard->valid_from?->format('d/m/Y') ?? '—' }} al {{ $dialyzerDiscard->valid_until?->format('d/m/Y') ?? '—' }}</small>
  @endif
  @if($linesDiscard)
   <span class="badge text-bg-success d-block mb-1">Líneas: {{ $linesDiscard->lot_number }}</span><small class="text-muted d-block mb-1">Vigencia: {{ $linesDiscard->valid_from?->format('d/m/Y') ?? '—' }} al {{ $linesDiscard->valid_until?->format('d/m/Y') ?? '—' }}</small>
  @endif
  @if(!$dialyzerDiscard && $dialyzerLot)<span class="badge text-bg-success d-block mb-1">Dializador: {{ $dialyzerLot->lot_number }} (global)</span>@endif
  @if(!$linesDiscard && $linesLot)<span class="badge text-bg-success d-block mb-1">Líneas: {{ $linesLot->lot_number }} (global)</span>@endif
  @can('annexes.nursing.record')
   @if((!$dialyzerDiscard && !$dialyzerLot) || (!$linesDiscard && !$linesLot))
    <button class="btn btn-sm btn-primary mt-1" type="button" data-bs-toggle="collapse" data-bs-target="#discard-{{ $order->id }}" aria-expanded="false"><i class="bi bi-pencil-square me-1"></i>Ingresar datos del cuadro</button>
   @else
    <small class="text-success"><i class="bi bi-check2-circle"></i> Datos completos</small>
   @endif
  @endcan
 </td>
</tr>

@can('annexes.nursing.record')
 @if((!$dialyzerDiscard && !$dialyzerLot) || (!$linesDiscard && !$linesLot))
  <tr class="collapse bg-light" id="discard-{{ $order->id }}">
   <td colspan="6">
    <div class="row g-3 p-2">
     @if(!$dialyzerDiscard && !$dialyzerLot)
      @include('nursing-annexes.partials.discard-form', ['category' => \App\Models\DisposableDiscard::DIALYZER, 'title' => 'Dializador', 'lotLabel' => 'Lote del dializador', 'defaultReason' => 'Coagulación'])
     @endif
     @if(!$linesDiscard && !$linesLot)
      @include('nursing-annexes.partials.discard-form', ['category' => \App\Models\DisposableDiscard::BLOOD_LINES, 'title' => 'Set de líneas', 'lotLabel' => 'Lote del set de líneas', 'defaultReason' => 'Descarte posterior a sesión'])
     @endif
    </div>
   </td>
  </tr>
 @endif
@endcan
