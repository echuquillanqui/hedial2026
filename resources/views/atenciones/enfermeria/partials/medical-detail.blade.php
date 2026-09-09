@php
    $medicalValue = static fn ($content, $suffix = '') => filled($content)
        ? e($content).$suffix
        : '<span class="text-muted">Sin registrar</span>';
    $medicalSections = [
        'Valoración inicial' => [
            'Hora inicial' => [$medical?->hora_inicial], 'Peso inicial' => [$medical?->peso_inicial, ' kg'],
            'PA inicial' => [$medical?->pa_inicial], 'Frecuencia cardíaca' => [$medical?->frecuencia_cardiaca, ' lpm'],
            'SO₂' => [$medical?->so2, ' %'], 'FiO₂' => [$medical?->fio2], 'Temperatura' => [$medical?->temperatura, ' °C'],
        ],
        'Evaluación clínica' => [
            'Problemas clínicos' => [$medical?->problemas_clinicos], 'Evaluación' => [$medical?->evaluacion],
            'Indicaciones' => [$medical?->indicaciones], 'Signos y síntomas' => [$medical?->signos_sintomas],
        ],
        'Medicación' => [
            'EPO 2k' => [$medical?->epo2000], 'EPO 4k' => [$medical?->epo4000], 'Hierro' => [$medical?->hierro],
            'Vitamina B12' => [$medical?->vitamina_b12], 'Calcitriol' => [$medical?->calcitriol], 'Heparina' => [$medical?->heparina],
        ],
        'Prescripción y monitoreo HD' => [
            'Horas HD' => [$medical?->hora_hd, ' h'], 'Peso seco' => [$medical?->peso_seco, ' kg'], 'UF' => [$medical?->uf],
            'QB' => [$medical?->qb], 'QD' => [$medical?->qd], 'Bicarbonato' => [$medical?->bicarbonato],
            'Na inicial' => [$medical?->na_inicial], 'CND' => [$medical?->cnd], 'Na final' => [$medical?->na_final],
            'Perfil Na' => [$medical?->perfil_na], 'Área del filtro' => [$medical?->area_filtro],
            'Membrana' => [$medical?->membrana], 'Perfil UF' => [$medical?->perfil_uf],
        ],
    ];
@endphp

@if(!$medical)
    <div class="alert alert-warning mb-0" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>El médico todavía no ha registrado información para esta atención.
    </div>
@else
    @foreach($medicalSections as $section => $items)
        <h6 class="fw-bold text-info border-bottom pb-2 {{ $loop->first ? '' : 'mt-3' }}">{{ $section }}</h6>
        <div class="row g-2">
            @foreach($items as $label => $item)
                <div class="col-md-3">
                    <div class="border rounded h-100 p-2 bg-light">
                        <div class="small fw-bold text-muted text-uppercase">{{ $label }}</div>
                        <div class="text-break" style="white-space: pre-line">{!! $medicalValue($item[0], $item[1] ?? '') !!}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach

    <h6 class="fw-bold text-info border-bottom pb-2 mt-3">Cierre médico</h6>
    <div class="row g-2">
        <div class="col-md-6"><div class="border rounded h-100 p-2 bg-light"><div class="small fw-bold text-muted text-uppercase">Evaluación final</div><div style="white-space: pre-line">{!! $medicalValue($medical->evaluacion_final) !!}</div></div></div>
        <div class="col-md-2"><div class="border rounded h-100 p-2 bg-light"><div class="small fw-bold text-muted text-uppercase">Hora final</div>{!! $medicalValue($medical->hora_final) !!}</div></div>
        <div class="col-md-2"><div class="border rounded h-100 p-2 bg-light"><div class="small fw-bold text-muted text-uppercase">Médico inicia</div>{!! $medicalValue($medical->usuarioInicia?->name) !!}</div></div>
        <div class="col-md-2"><div class="border rounded h-100 p-2 bg-light"><div class="small fw-bold text-muted text-uppercase">Médico finaliza</div>{!! $medicalValue($medical->usuarioFinaliza?->name) !!}</div></div>
    </div>
@endif
