@extends('layouts.app')
@section('content')
@php
    $testColumns = [
        'hb' => 'Hemoglobina', 'hto' => 'Hematocrito', 'calcio' => 'Calcio total',
        'fosforo' => 'Fósforo inorgánico (fosfato)', 'pth' => 'Parathormona (PTH)',
        'albumina' => 'Albúmina', 'fosfatasa' => 'Fosfatasa alcalina',
        'tgo' => 'Aspartato aminotransferasa (AST/TGO)', 'tgp' => 'Alanina aminotransferasa (ALT/TGP)',
        'hierro' => 'Hierro', 'ferritina' => 'Ferritina', 'transferrina' => 'Transferrina',
    ];
@endphp
<div class="container-fluid ktv-audit">
    <div class="mb-3"><h3 class="mb-1"><i class="bi bi-calculator me-2"></i>Auditoría KTV</h3><p class="text-muted mb-0">Datos del laboratorio y de la sesión de hemodiálisis realizada en la fecha de la muestra.</p></div>
    <form method="GET" class="card card-body shadow-sm mb-4" data-audit-filters><div class="row g-3 align-items-end">
        <div class="col-lg-4"><label class="form-label">Paciente, DNI o historia</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Buscar..."></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label">Fecha de muestra</label><input type="date" class="form-control" name="date" value="{{ request('date', today()->toDateString()) }}"></div>
        <div class="col-lg-auto"><a class="btn btn-outline-secondary" href="{{ route('audit.ktv') }}">Limpiar</a></div>
    </div></form>
    @include('audit._filters-script')
    <div class="card shadow-sm"><div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0">
        <thead><tr class="group-head"><th colspan="2"></th><th colspan="9">Datos clínicos para el cálculo del KTV</th><th colspan="2">TRR previa</th><th colspan="10">Otros resultados</th></tr><tr>
            <th>Paciente</th><th>Documento</th><th>Fecha de muestra</th><th>UPOST<br><small>mg/dL</small></th><th>UPRE<br><small>mg/dL</small></th><th>Tiempo HD<br><small>h</small></th><th>Peso inicial</th><th>Peso final</th><th>Ultrafiltración</th><th>Peso seco</th><th>KTV</th>
            @foreach($testColumns as $key => $name)<th>{{ ['hb'=>'HB','hto'=>'HTO','calcio'=>'Calcio sérico','fosforo'=>'Fósforo sérico','pth'=>'PTH','albumina'=>'Albúmina sérica','fosfatasa'=>'Fosfatasa','tgo'=>'TGO','tgp'=>'TGP','hierro'=>'Hierro sérico','ferritina'=>'Ferritina','transferrina'=>'Transferrina'][$key] }}</th>@endforeach
        </tr></thead><tbody>
        @forelse($laboratories as $laboratory)
            @php
                $session = ($laboratory->order?->attention_type === \App\Support\ClinicalService::HEMODIALYSIS && $laboratory->order?->fecha_orden?->isSameDay($laboratory->sampled_at)) ? $laboratory->order : $laboratory->patient?->orders?->first();
                $medical = $session?->medical; $nurse = $session?->nurse;
                $results = $laboratory->items->filter(fn($item) => filled($item->result_value))->mapWithKeys(fn($item) => [$item->test?->name => $item->result_value]);
                $upost = $results['Urea post diálisis'] ?? null; $upre = $results['Urea pre diálisis'] ?? null;
                $time = $medical?->hora_hd ?: $session?->horas_dialisis; $initial = $nurse?->peso_inicial ?: $medical?->peso_inicial; $final = $nurse?->peso_final;
                $uf = $nurse?->uf ?: $medical?->uf; $dry = $medical?->peso_seco ?: $laboratory->patient?->peso_seco;
                $ratio = is_numeric($upost) && is_numeric($upre) && (float)$upre > 0 ? (float)$upost / (float)$upre : null;
                $logArgument = $ratio !== null && is_numeric($time) ? $ratio - (0.008 * (float)$time) : null;
                $weight = is_numeric($final) && (float)$final > 0 ? (float)$final : (is_numeric($dry) && (float)$dry > 0 ? (float)$dry : null);
                $ktv = $logArgument !== null && $logArgument > 0 && $weight !== null
                    ? -log($logArgument) + (4 - (3.5 * $ratio)) * ((is_numeric($uf) ? (float)$uf : 0) / $weight)
                    : null;
            @endphp
            <tr><td class="text-nowrap fw-semibold">{{ $laboratory->patient?->full_name ?: $laboratory->patient_name }}</td><td>{{ $laboratory->patient?->dni ?: '—' }}</td><td class="text-nowrap">{{ $laboratory->sampled_at?->format('d/m/Y') }}</td><td>{{ $upost ?? '—' }}</td><td>{{ $upre ?? '—' }}</td><td>{{ $time ?: '—' }}</td><td>{{ $initial ?: '—' }}</td><td>{{ $final ?: '—' }}</td><td>{{ $uf ?: '—' }}</td><td>{{ $dry ?: '—' }}</td><td class="fw-bold text-success">{{ is_finite($ktv ?? NAN) ? number_format($ktv, 2) : '—' }}</td>@foreach($testColumns as $name)<td>{{ $results[$name] ?? '—' }}</td>@endforeach</tr>
        @empty<tr><td colspan="23" class="text-center text-muted py-5">No hay laboratorios para la fecha seleccionada.</td></tr>@endforelse
        </tbody></table></div></div><div class="mt-3">{{ $laboratories->links() }}</div>
</div>
<style>.ktv-audit table{font-size:.72rem;white-space:nowrap}.ktv-audit th{text-align:center;vertical-align:bottom;background:#d6d8da;color:#5f6670}.ktv-audit .group-head th{background:#c7c9cb;text-transform:uppercase;font-size:.65rem;letter-spacing:.02em;border-bottom:2px solid #333}.ktv-audit td{text-align:center}.ktv-audit td:first-child{text-align:left}</style>
@endsection
