<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 10px 12px; }
        * { box-sizing: border-box; }
        body { color: #000; font-family: DejaVu Sans, sans-serif; font-size: 8.2px; line-height: 1.18; margin: 0; }
        .sheet { border: 2px solid #111; min-height: 1030px; padding: 7px; }
        table { border-collapse: collapse; width: 100%; }
        td { padding: 2px 3px; vertical-align: top; }
        .header { border: 1px solid #222; height: 105px; margin-bottom: 5px; }
        .header td { text-align: center; vertical-align: middle; }
        .logo { width: 67px; height: 67px; object-fit: contain; }
        h1 { font-size: 14px; margin: 0; }
        .label, .section-title { font-weight: bold; text-transform: uppercase; }
        .section-title { background: #c9c9c9; border: 2px solid #111; font-size: 9px; margin: 5px 0 3px; padding: 3px; }
        .data td { padding: 2px 3px; }
        .line { min-height: 14px; padding: 2px 3px; }
        .vitals td { font-weight: bold; white-space: nowrap; }
        .diagnoses td { padding: 1px 3px; }
        .diagnoses .code { text-align: center; width: 12%; }
        .box { border: 2px solid #111; display: inline-block; font-size: 9px; height: 17px; line-height: 13px; margin: 0 3px; text-align: center; width: 30px; }
        .treatment td { padding: 3px; }
        .detail { padding-left: 18px; }
        .exams { font-size: 8px; }
        .signature { margin: 55px auto 0; text-align: center; width: 46%; }
        .signature-line { border-top: 1px solid #222; font-size: 10px; font-weight: bold; padding-top: 2px; }
        .muted { color: #333; }
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
        <td style="width:18%"><img class="logo" src="{{ public_path('logo/logo_03.jpeg') }}" alt="Logo"></td>
        <td><h1>FORMATO DE CONSULTA NEFROLÓGICA</h1><div class="muted">{{ $consultation->sede?->name }}</div></td>
        <td style="width:18%"><img class="logo" src="{{ public_path('logo/logo_03.jpeg') }}" alt="Logo"></td>
    </tr></table>

    <table class="data">
        <tr><td class="label" style="width:19%">Nombres y apellidos:</td><td style="width:31%">{{ $patient->full_name ?: '—' }}</td><td class="label" style="width:7%">DNI:</td><td style="width:12%">{{ $patient->dni ?: '—' }}</td><td class="label" style="width:17%">Fecha de nacimiento:</td><td>{{ $birthDate?->format('Y-m-d') ?: '—' }}</td><td class="label">Edad:</td><td>{{ $age !== null ? $age.' años' : '—' }}</td></tr>
        <tr><td class="label">Fecha de atención:</td><td>{{ $consultation->consultation_date?->format('Y-m-d') ?: '—' }}</td><td class="label">Hora:</td><td>{{ $consultation->consultation_time ?: '—' }}</td><td class="label">Motivo de consulta:</td><td colspan="3">{{ $consultation->reason ?: '—' }}</td></tr>
        <tr><td class="label">Tiempo de enfermedad:</td><td>{{ $consultation->disease_duration ?: '—' }}</td><td class="label" colspan="2">Fecha de inicio de diálisis:</td><td colspan="4">{{ $consultation->dialysis_start_date?->format('Y-m-d') ?: '—' }}</td></tr>
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
        <tr><td class="label" style="width:20%">Se solicita:</td><td>{{ $exams->isNotEmpty() ? $exams->implode('; ') : '—' }}</td></tr>
        <tr><td class="label">Fecha de toma de muestra:</td><td>{{ $consultation->next_laboratory_date?->format('Y-m-d') ?: '—' }}</td></tr>
        <tr><td class="label">Próxima cita:</td><td>{{ $consultation->next_appointment_date?->format('Y-m-d') ?: '—' }}</td></tr>
    </table>
    <div style="margin-top:10px"><strong>Atendido por:</strong></div>
    <table style="width:55%"><tr><td class="label" style="width:26%">Nombre y apellido:</td><td>{{ $doctor?->name ?: '—' }}</td></tr><tr><td class="label">Profesión:</td><td>{{ $doctor?->profession ?: 'Médico Cirujano' }}</td></tr><tr><td class="label">Especialidad:</td><td>Nefrología</td></tr><tr><td class="label">N° C.M.P. / R.N.E.:</td><td>{{ $doctor?->license_number ?: '—' }} / {{ $doctor?->specialty_number ?: '—' }}</td></tr></table>
    <div class="signature"><div class="signature-line">{{ $doctor?->name ?: 'Médico tratante' }}</div><div>Médico Nefrólogo</div><div>C.M.P. {{ $doctor?->license_number ?: '—' }} - R.N.E. {{ $doctor?->specialty_number ?: '—' }}</div></div>
</div>
</body>
</html>
