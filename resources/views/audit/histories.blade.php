@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="mb-3"><h3 class="mb-1"><i class="bi bi-journal-check me-2"></i>Auditoría de historias</h3><p class="text-muted mb-0">Atenciones diarias y accesos directos para corregir medicina, enfermería y tratamiento.</p></div>
    @include('audit._filters', ['showStatus' => true])
    <div class="card shadow-sm overflow-hidden"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr><th>Fecha</th><th>Paciente</th><th>Módulo / turno</th><th>Orden</th><th>Estado</th><th class="text-end">Corrección</th></tr></thead>
        <tbody>@forelse($orders as $order)<tr>
            <td>{{ optional($order->fecha_orden)->format('d/m/Y') }}</td><td><strong>{{ $order->patient?->full_name }}</strong><div class="small text-muted">DNI {{ $order->patient?->dni }}</div></td>
            <td>{{ $order->sala }}<div class="small text-muted">Turno {{ $order->turno }}</div></td><td>{{ $order->codigo_unico }}</td>
            <td>@if($order->medical && $order->nurse && $order->treatments->isNotEmpty())<span class="badge bg-success">Completo</span>@else<span class="badge bg-warning text-dark">Por corregir</span>@endif</td>
            <td class="text-end"><div class="btn-group btn-group-sm">
                @if($order->medical)<a class="btn btn-outline-primary" href="{{ route('medicals.edit', $order->medical) }}">Medicina</a>@else<button class="btn btn-outline-secondary" disabled>Sin medicina</button>@endif
                @if($order->nurse)<a class="btn btn-outline-success" href="{{ route('nurses.edit', $order->nurse) }}">Enfermería y tratamiento</a>@else<button class="btn btn-outline-secondary" disabled>Sin enfermería</button>@endif
            </div></td>
        </tr>@empty<tr><td colspan="6" class="text-center text-muted py-5">No hay atenciones para los filtros seleccionados.</td></tr>@endforelse</tbody>
    </table></div></div><div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection
