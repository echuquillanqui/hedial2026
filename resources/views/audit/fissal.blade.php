@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="mb-3"><h3 class="mb-1"><i class="bi bi-table me-2"></i>Auditoría FISSAL</h3><p class="text-muted mb-0">Vista consolidada de las sesiones de hemodiálisis.</p></div>
    @include('audit._filters')
    <div class="card shadow-sm overflow-hidden"><div class="table-responsive"><table class="table table-sm table-striped align-middle mb-0" style="font-size:.82rem">
        <thead class="table-dark"><tr><th>Apellidos y nombres del paciente</th><th>Inicio</th><th>Final</th><th>N.° FUA</th><th>EPO 2000</th><th>EPO 4000</th><th>Vit. B12</th><th>Hierro</th><th>Calcitriol</th><th>Fecha</th><th>Lic. inicia</th><th>Lic. finaliza</th><th>Nefrólogo</th><th>Módulo</th></tr></thead>
        <tbody>@forelse($orders as $order)@php($nurse = $order->nurse)@php($medical = $order->medical)<tr>
            <td class="text-nowrap"><strong>{{ $order->patient?->full_name }}</strong></td><td>{{ optional($order->treatments->first())->hora ? substr($order->treatments->first()->hora, 0, 5) : '—' }}</td><td>{{ optional($order->treatments->last())->hora ? substr($order->treatments->last()->hora, 0, 5) : '—' }}</td>
            <td class="text-center">{{ $order->fua?->correlative ?? '—' }}</td><td>{{ $nurse?->epo2000 ?: '0' }}</td><td>{{ $nurse?->epo4000 ?: '0' }}</td><td>{{ $nurse?->vitamina_b12 ?: '0' }}</td><td>{{ $nurse?->hierro ?: '0' }}</td><td>{{ $nurse?->calcitriol ?: '0' }}</td>
            <td class="text-nowrap">{{ optional($order->fecha_orden)->format('d/m/Y') }}</td><td>{{ $nurse?->enfermeroInicia?->name ?: '—' }}</td><td>{{ $nurse?->enfermeroFinaliza?->name ?: '—' }}</td><td>{{ $medical?->usuarioInicia?->name ?: $medical?->usuarioFinaliza?->name ?: '—' }}</td><td class="text-nowrap">{{ $order->sala }}</td>
        </tr>@empty<tr><td colspan="14" class="text-center text-muted py-5">No hay datos para los filtros seleccionados.</td></tr>@endforelse</tbody>
    </table></div></div><div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection
