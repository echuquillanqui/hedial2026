<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 8mm 12mm; }
        * { box-sizing: border-box; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.12; margin: 0; }
        .sheet { border: 1px solid #cbd5e1; border-top: 4px solid #2563eb; padding: 3mm 4mm; }
        table { border-collapse: collapse; width: 100%; }
        td { padding: 1.5px 3px; vertical-align: top; }
        .header { background: #eff6ff; border: 0; height: 58px; margin-bottom: 4px; page-break-inside: avoid; }
        .header td { text-align: center; vertical-align: middle; }
        .logo { width: 76px; height: 48px; object-fit: contain; }
        .logo-fallback { color:#2563eb; font-size:22px; font-weight:bold; }
        h1 { color:#172554; font-size: 14px; letter-spacing: .45px; margin: 0; }
        .label, .section-title { font-weight: bold; text-transform: uppercase; }
        .section-title { background: #172554; border-left: 5px solid #38bdf8; color:#fff; font-size: 8px; letter-spacing:.35px; margin: 4px 0 2px; padding: 3px 6px; }
        .data td { padding: 2px 3px; overflow-wrap: break-word; }
        .data { table-layout: fixed; }
        .data .label { line-height: 1.05; }
        .patient-data td { border-right: 1px solid #dbeafe; }
        .patient-data td:last-child { border-right: 0; }
        .patient-data .label { margin-right: 3px; }
        .patient-data .patient-name { white-space: nowrap; }
        .line { min-height: 12px; padding: 1.5px 3px; }
        .vitals td { font-weight: bold; white-space: nowrap; }
        .diagnoses td { padding: 1px 3px; }
        .diagnoses .code { text-align: center; width: 12%; }
        .box { background:#fff; border: 1px solid #2563eb; border-radius:3px; color:#1d4ed8; display: inline-block; font-size: 8px; height: 13px; line-height: 11px; margin: 0 2px; text-align: center; width: 20px; }
        .treatment { background:#f8fafc; border:1px solid #dbeafe; }
        .treatment td { padding: 2px 3px; }
        .detail { padding-left: 18px; }
        .exams { font-size: 8px; }
        .exam-grid { table-layout: fixed; }
        .exam-grid td { padding: 2px 5px; width: 33.333%; }
        .exam-check { color:#1d4ed8; font-weight:bold; white-space:nowrap; }
        .signature { margin: 14px auto 0; text-align: center; width: 46%; page-break-inside: avoid; }
        .signature-line { border-top: 1px solid #222; font-size: 10px; font-weight: bold; padding-top: 2px; }
        .muted { color: #475569; margin-top:3px; }
        .data { border-bottom:1px solid #dbeafe; }
        .data .label { color:#1e40af; }
        .vitals { background:#eff6ff; border:1px solid #bfdbfe; }
        .diagnoses { border:1px solid #dbeafe; }
        .diagnoses tr:nth-child(even) { background:#f8fafc; }
        .exams { background:#f8fafc; border:1px solid #dbeafe; }
    </style>
</head>
<body>
@php
    $patient = $consultation->patient;
    $doctor = $consultation->doctor;
    $birthDate = $patient->birth_date ? \Carbon\Carbon::parse($patient->birth_date) : null;
    $age = $birthDate ? $birthDate->age : $patient->age;
    $diagnoses = collect($consultation->diagnoses ?: [])->filter(fn ($item) => !empty($item['codigo']) || !empty($item['descripcion']))->take(4)->values();
    $exams = collect($consultation->auxiliary_exams ?: [])->map(fn ($exam) => str_contains($exam, '|') ? explode('|', $exam, 2)[1] : $exam)->filter();
    $yesNo = fn ($value, $expected) => $value !== null && (bool) $value === $expected ? 'X' : '';
@endphp
<div class="sheet">
    <table class="header"><tr>
        <td style="width:20%">@if($logoData ?? null)<img class="logo" src="{{ $logoData }}" alt="Logo de la empresa">@else<div class="logo-fallback">SALUD+</div>@endif</td>
        <td><h1>FORMATO DE CONSULTA NEFROLÓGICA</h1><div class="muted">{{ ($configuration ?? null)?->company_name ?: $consultation->sede?->name }}</div></td>
        <td style="width:20%;color:#1d4ed8;font-weight:bold">HISTORIA CLÍNICA<br><span style="font-size:11px">{{ $patient->medical_history_number ?: $patient->dni ?: '—' }}</span></td>
    </tr></table>

    <table class="data patient-data">
        <colgroup>
            <col style="width:42%"><col style="width:20%"><col style="width:26%"><col style="width:12%">
        </colgroup>
        <tr><td class="patient-name"><span class="label">Nombres y apellidos:</span>{{ $patient->full_name ?: '—' }}</td><td><span class="label">DNI:</span>{{ $patient->dni ?: '—' }}</td><td><span class="label">Fecha de nacimiento:</span>{{ $birthDate?->format('d/m/Y') ?: '—' }}</td><td><span class="label">Edad:</span>{{ $age !== null ? $age.' años' : '—' }}</td></tr>
        <tr><td><span class="label">Fecha de atención:</span>{{ $consultation->consultation_date?->format('d/m/Y') ?: '—' }}</td><td><span class="label">Hora:</span>{{ $consultation->consultation_time ?: '—' }}</td><td colspan="2"><span class="label">Motivo de consulta:</span>{{ $consultation->reason ?: '—' }}</td></tr>
        <tr><td colspan="2"><span class="label">Tiempo de enfermedad:</span>{{ $consultation->disease_duration ?: '—' }}</td><td colspan="2"><span class="label">Fecha de inicio de diálisis:</span>{{ $consultation->dialysis_start_date?->format('Y-m-d') ?: '—' }}</td></tr>
    </table>
    <div class="line"><span class="label">Anamnesis:</span> {{ $consultation->current_illness ?: $consultation->history ?: '—' }}</div>
    <div class="line"><span class="label">Etiología:</span> {{ $consultation->etiology ?: '—' }} &nbsp;&nbsp;&nbsp; <span class="label">Acceso vascular actual:</span> {{ $consultation->vascular_access ?: '—' }}</div>
    <div class="line"><span class="label">Signos y síntomas:</span> {{ $consultation->symptoms ?: '—' }}</div>
    <div class="line label">Examen físico:</div>
    <table class="vitals"><tr>
        <td>T°: {{ $consultation->temperature ?? '—' }} °C</td><td>PA: {{ $consultation->blood_pressure ?: '—' }} mmHg</td>
        <td>FC: {{ $consultation->heart_rate ?? '—' }} X'</td><td>FR: {{ $consultation->respiratory_rate ?? '—' }} X'</td>
        <td>PESO: {{ $consultation->weight ?? '—' }} kg</td><td>TALLA: {{ $consultation->height ?? '—' }} m</td><td>IMC: {{ $consultation->bmi ?? '—' }}</td>
    </tr></table>
    <div class="line">{{ $consultation->physical_exam ?: '—' }}</div>
    <div class="line">{{ $consultation->lung_exam ?: '—' }}</div>
    <div class="line">{{ $consultation->cardiac_exam ?: '—' }}</div>
    <div class="line"><strong>Diuresis:</strong> {{ $consultation->diuresis ?? '—' }} ml</div>

    <table class="diagnoses">
        <tr><td class="label">Diagnóstico</td><td class="label code">CIE 10</td></tr>
        @for($i = 0; $i < 4; $i++)
            <tr><td>{{ $i + 1 }}. {{ data_get($diagnoses, $i.'.descripcion', '—') }}</td><td class="code">{{ data_get($diagnoses, $i.'.codigo', '—') }}</td></tr>
        @endfor
    </table>
    <table><tr><td><span class="label">Prescripción de diálisis:</span> {{ $consultation->dialysis_prescription ?: '—' }}</td><td><span class="label">Tiempo:</span> {{ $consultation->dialysis_hours ?? '—' }} horas</td><td><span class="label">Área de filtro:</span> {{ $consultation->filter_area ?? '—' }} m2</td></tr></table>

    <div class="section-title">Tratamiento complementario</div>
    <table class="treatment">
        <tr><td style="width:31%"><strong>a) Anemia</strong> &nbsp; SÍ <span class="box">{{ $yesNo($consultation->anemia_treatment, true) }}</span> NO <span class="box">{{ $yesNo($consultation->anemia_treatment, false) }}</span></td><td><strong>Especificar:</strong></td><td>Hemoglobina:</td><td>{{ $consultation->hemoglobin ?? '—' }} mg/dl</td></tr>
        <tr><td></td><td></td><td>Epoetina alfa 2000 UI:</td><td>{{ $consultation->epoetin_dose ?: '—' }}</td></tr>
        <tr><td></td><td></td><td>Hidroxocobalamina 1 mg:</td><td>{{ $consultation->hydroxocobalamin_dose ?: '—' }}</td></tr>
        <tr><td></td><td></td><td>Hierro 100 mg:</td><td>{{ $consultation->iron_dose ?: '—' }}</td></tr>
        <tr><td colspan="4"><strong>Observación:</strong> {{ $consultation->observations ?: '—' }}</td></tr>
        <tr><td colspan="2"><strong>b) Alteración metabolismo óseo mineral</strong> &nbsp; SÍ <span class="box">{{ $yesNo($consultation->bone_mineral_treatment, true) }}</span> NO <span class="box">{{ $yesNo($consultation->bone_mineral_treatment, false) }}</span></td><td colspan="2"><strong>Especificar:</strong> {{ $consultation->treatment_plan ?: '—' }}</td></tr>
        <tr><td colspan="2"><strong>c) Antihipertensivos</strong> &nbsp; SÍ <span class="box">{{ $yesNo($consultation->antihypertensive_treatment, true) }}</span> NO <span class="box">{{ $yesNo($consultation->antihypertensive_treatment, false) }}</span></td><td colspan="2"></td></tr>
        <tr><td colspan="4"><strong>d) Otros:</strong> {{ $consultation->other_treatment ?: '—' }}</td></tr>
    </table>

    <div class="section-title">Indicaciones de exámenes auxiliares</div>
    <table class="exams">
        <tr><td class="label">Se solicita:</td></tr>
        <tr><td>
            <table class="exam-grid">
                @forelse($exams->chunk(3) as $examRow)
                    <tr>
                        @foreach($examRow as $exam)
                            <td><span class="exam-check">( X )</span> {{ $exam }}</td>
                        @endforeach
                        @for($i = $examRow->count(); $i < 3; $i++)
                            <td>&nbsp;</td>
                        @endfor
                    </tr>
                @empty
                    <tr><td colspan="3">—</td></tr>
                @endforelse
            </table>
        </td></tr>
        <tr><td class="label">Fecha de toma de muestra:</td><td>{{ $consultation->next_laboratory_date?->format('Y-m-d') ?: '—' }}</td></tr>
        <tr><td class="label">Próxima cita:</td><td>{{ $consultation->next_appointment_date?->format('Y-m-d') ?: '—' }}</td></tr>
    </table>
    <div style="margin-top:10px"><strong>Atendido por:</strong></div>
    <table style="width:55%"><tr><td class="label" style="width:26%">Nombre y apellido:</td><td>{{ $doctor?->name ?: '—' }}</td></tr><tr><td class="label">Profesión:</td><td>{{ $doctor?->profession ?: 'Médico Cirujano' }}</td></tr><tr><td class="label">Especialidad:</td><td>Nefrología</td></tr><tr><td class="label">N° C.M.P. / R.N.E.:</td><td>{{ $doctor?->license_number ?: '—' }} / {{ $doctor?->specialty_number ?: '—' }}</td></tr></table>
    <div class="signature"><div class="signature-line">{{ $doctor?->name ?: 'Médico tratante' }}</div><div>Médico Nefrólogo</div><div>C.M.P. {{ $doctor?->license_number ?: '—' }} - R.N.E. {{ $doctor?->specialty_number ?: '—' }}</div></div>
</div>
</body>
</html>
