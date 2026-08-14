@php
    $order = $fua->order;
    $patient = $order?->patient;
    $medical = $order?->medical;
    $attentionDate = $order?->fecha_orden ? \Carbon\Carbon::parse($order->fecha_orden) : $fua->created_at;
    $birthDate = $patient?->birth_date ? \Carbon\Carbon::parse($patient->birth_date) : null;
    $age = $birthDate ? $birthDate->age : null;
    $doctorName = $responsible?->name ?: $configuration->responsible_name;
    $doctorLicense = $responsible?->license_number ?: $configuration->responsible_college_number;
    $doctorSpecialty = $responsible?->profession ?: $configuration->responsible_specialty;
    $diagnoses = collect(preg_split('/\r\n|\r|\n/', (string) $medical?->problemas_clinicos))->filter()->values();
    $labItems = $order?->laboratoryOrder?->items ?? collect();
    $requestedTests = $labItems->map(fn ($item) => $item->test?->name)->filter()->join(', ');
    $mark = fn ($value) => filled($value) && !in_array(mb_strtolower(trim((string) $value)), ['0', 'no', 'ninguno', 'ninguna', '-']);
@endphp
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Consulta nefrológica {{ $fua->number }}</title>
<style>
    @page { margin: 8mm 10mm; }
    * { box-sizing: border-box; }
    body { margin: 0; color: #17212b; font-family: DejaVu Sans, sans-serif; font-size: 8.2px; line-height: 1.28; }
    .sheet { border: 1.4px solid #263238; padding: 5mm; min-height: 272mm; position: relative; }
    .header { width: 100%; border-bottom: 2px solid #15706b; padding-bottom: 7px; margin-bottom: 7px; }
    .header td { vertical-align: middle; }
    .logo { width: 74px; height: 52px; object-fit: contain; }
    .title { text-align: center; font-size: 15px; font-weight: 700; color: #114d4a; letter-spacing: .3px; }
    .subtitle { text-align: center; font-size: 8px; color: #607d8b; margin-top: 3px; }
    .doc-number { text-align: right; font-weight: bold; color: #114d4a; }
    table { width: 100%; border-collapse: collapse; }
    .data td { padding: 2px 4px; vertical-align: top; }
    .label { font-size: 6.8px; font-weight: bold; color: #52636b; text-transform: uppercase; }
    .value { font-weight: 600; border-bottom: .5px solid #aebbc0; min-height: 13px; }
    .section { margin-top: 7px; }
    .section-title { background: #dcecea; color: #123f3d; border-left: 4px solid #15706b; padding: 3px 6px; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .25px; }
    .content { padding: 4px 5px; min-height: 19px; border: .6px solid #cad4d8; border-top: 0; white-space: pre-line; }
    .vitals { margin-top: 4px; border: .6px solid #9babad; }
    .vitals td { padding: 4px 3px; text-align: center; border-right: .6px solid #c6d0d2; }
    .vitals td:last-child { border-right: 0; }
    .vital-value { font-size: 9px; font-weight: bold; margin-top: 2px; }
    .diagnosis td, .treatment td { border-bottom: .6px solid #d7dfe1; padding: 3px 5px; }
    .code { text-align: right; width: 16%; font-weight: bold; color: #114d4a; }
    .check { display: inline-block; width: 14px; height: 12px; border: 1px solid #405057; text-align: center; line-height: 10px; font-weight: bold; margin: 0 3px; }
    .med-grid td { width: 33.33%; padding: 3px 5px; vertical-align: top; }
    .signature { width: 48%; margin: 28px auto 0; text-align: center; border-top: 1px solid #35454b; padding-top: 4px; font-weight: bold; }
    .signature small { display: block; font-weight: normal; }
    .footer { position: absolute; bottom: 4mm; left: 5mm; right: 5mm; border-top: .7px solid #a8b5b9; padding-top: 3px; font-size: 6.5px; color: #64747a; text-align: center; }
    .muted { color: #60747b; }
</style>
</head>
<body>
<div class="sheet">
    <table class="header"><tr>
        <td width="18%">@if($logoData)<img src="{{ $logoData }}" class="logo" alt="Logo">@endif</td>
        <td width="64%"><div class="title">FORMATO DE CONSULTA NEFROLÓGICA</div><div class="subtitle">{{ $configuration->company_name ?: $configuration->ipress_name }}</div></td>
        <td width="18%" class="doc-number">N.º {{ $fua->number }}</td>
    </tr></table>

    <table class="data">
        <tr><td width="45%"><span class="label">Nombres y apellidos</span><div class="value">{{ mb_strtoupper($patient?->full_name ?? '—') }}</div></td><td width="18%"><span class="label">DNI</span><div class="value">{{ $patient?->dni ?? '—' }}</div></td><td width="23%"><span class="label">Fecha de nacimiento</span><div class="value">{{ $birthDate?->format('d/m/Y') ?? '—' }}</div></td><td><span class="label">Edad</span><div class="value">{{ $age !== null ? $age.' años' : '—' }}</div></td></tr>
        <tr><td><span class="label">Fecha de atención</span><div class="value">{{ $attentionDate->format('d/m/Y') }}</div></td><td colspan="3"><span class="label">Motivo de consulta</span><div class="value">{{ $configuration->consultation_reason }}</div></td></tr>
    </table>

    <div class="section"><div class="section-title">Historia clínica</div>
        <table class="data"><tr><td width="62%"><span class="label">Anamnesis</span><div class="value">{{ $medical?->evaluacion ?: ($configuration->default_anamnesis ?: 'Paciente con enfermedad renal crónica en programa de hemodiálisis.') }}</div></td><td><span class="label">Fecha de inicio</span><div class="value">{{ $patient?->created_at?->format('d/m/Y') ?? '—' }}</div></td></tr>
        <tr><td><span class="label">Etiología</span><div class="value">{{ $configuration->default_etiology ?: '—' }}</div></td><td><span class="label">Acceso vascular actual</span><div class="value">{{ $configuration->default_vascular_access ?: '—' }}</div></td></tr>
        <tr><td colspan="2"><span class="label">Signos y síntomas</span><div class="value">{{ $medical?->signos_sintomas ?: 'Niega síntomas de alarma.' }}</div></td></tr></table>
        <table class="vitals"><tr>
            <td><span class="label">T°</span><div class="vital-value">{{ $medical?->temperatura ?? '—' }} °C</div></td><td><span class="label">PA</span><div class="vital-value">{{ $medical?->pa_inicial ?? '—' }} mmHg</div></td><td><span class="label">FC</span><div class="vital-value">{{ $medical?->frecuencia_cardiaca ?? '—' }} x'</div></td><td><span class="label">SatO₂</span><div class="vital-value">{{ $medical?->so2 ?? '—' }} %</div></td><td><span class="label">Peso</span><div class="vital-value">{{ $medical?->peso_inicial ?? '—' }} kg</div></td><td><span class="label">Filtro</span><div class="vital-value">{{ $medical?->area_filtro ?? '—' }} m²</div></td>
        </tr></table>
        <div class="content"><b>Examen físico:</b> {{ $medical?->problemas_clinicos ?: 'Paciente lúcido, orientado, en aparente regular estado general. Sin hallazgos agudos consignados.' }}</div>
    </div>

    <div class="section"><div class="section-title">Diagnósticos</div><table class="diagnosis">
        <tr><td>1. {{ $diagnoses->get(0) ?: $configuration->diagnosis_name }}</td><td class="code">{{ $configuration->diagnosis_code }}</td></tr>
        <tr><td>2. {{ $diagnoses->get(1) ?: $configuration->secondary_diagnosis_name }}</td><td class="code">{{ $configuration->secondary_diagnosis_code }}</td></tr>
    </table></div>

    <div class="section"><div class="section-title">Prescripción y tratamiento complementario</div>
        <table class="med-grid"><tr>
            <td><b>Anemia</b><br>Sí <span class="check">{{ $mark($medical?->epo2000) || $mark($medical?->epo4000) || $mark($medical?->hierro) ? 'X' : '' }}</span> No <span class="check">{{ !($mark($medical?->epo2000) || $mark($medical?->epo4000) || $mark($medical?->hierro)) ? 'X' : '' }}</span></td>
            <td><b>Metabolismo óseo mineral</b><br>Sí <span class="check">{{ $mark($medical?->calcitriol) ? 'X' : '' }}</span> No <span class="check">{{ !$mark($medical?->calcitriol) ? 'X' : '' }}</span></td>
            <td><b>Diálisis</b><br>Tiempo: {{ $medical?->hora_hd ?? $order?->horas_dialisis ?? '—' }} h &nbsp; Área filtro: {{ $medical?->area_filtro ?? '—' }} m²</td>
        </tr><tr>
            <td>Eritropoyetina 2,000 UI: <b>{{ $medical?->epo2000 ?: '—' }}</b></td><td>Eritropoyetina 4,000 UI: <b>{{ $medical?->epo4000 ?: '—' }}</b></td><td>Hierro: <b>{{ $medical?->hierro ?: '—' }}</b> · Calcitriol: <b>{{ $medical?->calcitriol ?: '—' }}</b></td>
        </tr></table>
        @if($medical?->indicaciones)<div class="content"><b>Observaciones / indicaciones:</b> {{ $medical->indicaciones }}</div>@endif
    </div>

    <div class="section"><div class="section-title">Indicaciones de exámenes auxiliares</div>
        <div class="content"><b>Se solicita:</b> {{ $requestedTests ?: 'Sin exámenes auxiliares registrados.' }}<br><b>Fecha de toma de muestra:</b> {{ $order?->laboratoryOrder?->created_at?->format('d/m/Y') ?? '—' }} &nbsp;&nbsp;&nbsp; <b>Próxima cita:</b> {{ $attentionDate->copy()->addMonth()->format('d/m/Y') }}</div>
    </div>

    <div class="signature">{{ $doctorName ?: 'Médico responsable' }}<small>{{ $doctorSpecialty ?: 'Nefrología' }}</small><small>C.M.P. {{ $doctorLicense ?: '—' }}</small></div>
    <div class="footer">{{ $configuration->company_name ?: $configuration->ipress_name }}@if($configuration->company_address) · {{ $configuration->company_address }}@endif @if($configuration->company_phone) · Tel. {{ $configuration->company_phone }}@endif</div>
</div>
</body></html>
