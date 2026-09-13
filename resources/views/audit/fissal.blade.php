@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="mb-3"><h3 class="mb-1"><i class="bi bi-table me-2"></i>Auditoría FISSAL</h3><p class="text-muted mb-0">Vista consolidada de las sesiones de hemodiálisis.</p></div>
    @include('audit._filters', ['showSequence' => true, 'showSessionStatus' => true])
    <div class="d-flex justify-content-end mb-2">
        <span class="badge rounded-pill text-bg-light border px-3 py-2">
            <strong>{{ $orders->total() }}</strong> {{ $orders->total() === 1 ? 'registro encontrado' : 'registros encontrados' }}
        </span>
    </div>
    <div class="card shadow-sm overflow-hidden"><div class="table-responsive"><table class="table table-sm table-striped align-middle mb-0 fissal-audit-table">
        <thead class="table-dark"><tr><th>Apellidos y nombres del paciente</th><th>Secuencia</th><th>Inicio</th><th>Final</th><th>N.° FUA</th><th>EPO 2000</th><th>EPO 4000</th><th>Vit. B12</th><th>Hierro</th><th>Calcitriol</th><th>Fecha</th><th>Lic. inicia</th><th>Lic. finaliza</th><th>Nefrólogo</th><th>Módulo</th></tr></thead>
        <tbody>@forelse($orders as $order)@php($nurse = $order->nurse)@php($medical = $order->medical)@php($moduleClass = match (true) {
            str_contains(strtoupper($order->sala ?? ''), 'MODULO 1') => 'fissal-module-1',
            str_contains(strtoupper($order->sala ?? ''), 'MODULO 2') => 'fissal-module-2',
            str_contains(strtoupper($order->sala ?? ''), 'MODULO 3') => 'fissal-module-3',
            default => '',
        })<tr class="{{ $moduleClass }}">
            <td class="text-nowrap"><strong>{{ $order->patient?->full_name }}</strong> <span class="text-muted">— DNI: {{ $order->patient?->dni ?: '—' }}</span></td><td class="text-nowrap">{{ $order->patient?->secuencia ?: '—' }}</td><td>{{ optional($order->treatments->first())->hora ? substr($order->treatments->first()->hora, 0, 5) : '—' }}</td><td>{{ optional($order->treatments->last())->hora ? substr($order->treatments->last()->hora, 0, 5) : '—' }}</td>
            <td class="text-center">{{ $order->fua?->correlative ?? '—' }}</td><td>{{ $nurse?->epo2000 ?: '0' }}</td><td>{{ $nurse?->epo4000 ?: '0' }}</td><td>{{ $nurse?->vitamina_b12 ?: '0' }}</td><td>{{ $nurse?->hierro ?: '0' }}</td><td>{{ $nurse?->calcitriol ?: '0' }}</td>
            <td class="text-nowrap">{{ optional($order->fecha_orden)->format('d/m/Y') }}</td><td>{{ $nurse?->enfermeroInicia?->name ?: '—' }}</td><td>{{ $nurse?->enfermeroFinaliza?->name ?: '—' }}</td><td>{{ $medical?->usuarioInicia?->name ?: $medical?->usuarioFinaliza?->name ?: '—' }}</td><td class="text-nowrap">{{ $order->sala }}</td>
        </tr>@empty<tr><td colspan="15" class="text-center text-muted py-5">No hay datos para los filtros seleccionados.</td></tr>@endforelse</tbody>
    </table></div></div><div class="mt-3">{{ $orders->links() }}</div>
</div>
<style>
    .fissal-audit-table { font-size: .82rem; }
    .fissal-audit-table tbody tr { height: 58px; }
    .fissal-audit-table tbody td { padding-top: .75rem; padding-bottom: .75rem; vertical-align: middle; }
    .fissal-audit-table tbody tr.fissal-module-1 > td { background-color: #fce8e8 !important; }
    .fissal-audit-table tbody tr.fissal-module-2 > td { background-color: #fff4cc !important; }
    .fissal-audit-table tbody tr.fissal-module-3 > td { background-color: #e3f5e8 !important; }
</style>
@endsection
