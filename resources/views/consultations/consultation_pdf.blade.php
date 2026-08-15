<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px 34px; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        .header { border-bottom: 2px solid #183b6b; padding-bottom: 9px; text-align: center; }
        .header h1 { color: #183b6b; font-size: 18px; margin: 0; }
        .header p { margin: 3px; }
        table { border-collapse: collapse; width: 100%; }
        .meta { margin: 12px 0; }
        .meta td, .clinical td, .medications th, .medications td { border: 1px solid #aebdcd; padding: 5px; }
        .label { background: #eef3f8; font-weight: bold; width: 15%; }
        .section-title { background: #183b6b; color: #fff; font-size: 10px; font-weight: bold; margin-top: 10px; padding: 5px 7px; text-transform: uppercase; }
        .clinical td { height: 42px; vertical-align: top; width: 50%; }
        .field-label { color: #183b6b; display: block; font-weight: bold; margin-bottom: 4px; }
        .vitals td { border: 1px solid #aebdcd; padding: 5px; text-align: center; }
        .vitals .label { width: auto; }
        .diagnosis, .exams { border: 1px solid #aebdcd; min-height: 28px; padding: 6px; }
        .medications th { background: #dce7f2; color: #183b6b; font-size: 8px; text-transform: uppercase; }
        .center { text-align: center; }
        .signatures { margin-top: 34px; text-align: center; }
        .signatures td { padding: 0 25px; width: 50%; }
        .line { border-top: 1px solid #222; padding-top: 4px; }
        .footer { bottom: 0; color: #667; font-size: 7px; position: fixed; text-align: center; width: 100%; }
    </style>
</head>
<body>
<div class="header">
    <h1>CONSULTA NEFROLÓGICA</h1>
    <p><strong>{{ $consultation->sede?->name }}</strong></p>
    <p>Registro de atención clínica</p>
</div>

<table class="meta">
    <tr><td class="label">Paciente</td><td>{{ $consultation->patient->full_name }}</td><td class="label">Fecha</td><td>{{ $consultation->consultation_date?->format('d/m/Y') }}</td></tr>
    <tr><td class="label">DNI</td><td>{{ $consultation->patient->dni ?: '—' }}</td><td class="label">H. clínica</td><td>{{ $consultation->patient->medical_history_number ?: '—' }}</td></tr>
    <tr><td class="label">Médico</td><td colspan="3">{{ $consultation->doctor?->name ?: '—' }}</td></tr>
</table>

<div class="section-title">Signos vitales</div>
<table class="vitals"><tr>
    <td><span class="field-label">Presión arterial</span>{{ $consultation->blood_pressure ?: '—' }}</td>
    <td><span class="field-label">Peso</span>{{ $consultation->weight !== null ? $consultation->weight.' kg' : '—' }}</td>
    <td><span class="field-label">Temperatura</span>{{ $consultation->temperature !== null ? $consultation->temperature.' °C' : '—' }}</td>
    <td><span class="field-label">Frec. cardíaca</span>{{ $consultation->heart_rate ?: '—' }}</td>
    <td><span class="field-label">Sat. O₂</span>{{ $consultation->oxygen_saturation !== null ? $consultation->oxygen_saturation.' %' : '—' }}</td>
</tr></table>

<div class="section-title">Evaluación clínica</div>
<table class="clinical">
    <tr><td><span class="field-label">Motivo de consulta</span>{{ $consultation->reason ?: '—' }}</td><td><span class="field-label">Enfermedad actual</span>{{ $consultation->current_illness ?: '—' }}</td></tr>
    <tr><td><span class="field-label">Antecedentes</span>{{ $consultation->history ?: '—' }}</td><td><span class="field-label">Examen físico</span>{{ $consultation->physical_exam ?: '—' }}</td></tr>
    <tr><td><span class="field-label">Plan / indicaciones</span>{{ $consultation->treatment_plan ?: '—' }}</td><td><span class="field-label">Observaciones</span>{{ $consultation->observations ?: '—' }}</td></tr>
</table>

<div class="section-title">Diagnósticos</div>
<div class="diagnosis">{{ $consultation->diagnosis ?: '—' }}</div>

<div class="section-title">Exámenes auxiliares solicitados</div>
<div class="exams">
    @forelse($consultation->auxiliary_exams ?: [] as $exam)
        {{ str_replace('|', ' — ', $exam) }}@unless($loop->last)<br>@endunless
    @empty
        —
    @endforelse
    <br><strong>Próximo laboratorio:</strong> {{ $consultation->next_laboratory_date?->format('d/m/Y') ?: '—' }}
    &nbsp;&nbsp; <strong>Próxima cita:</strong> {{ $consultation->next_appointment_date?->format('d/m/Y') ?: '—' }}
</div>

@if($consultation->medications->isNotEmpty())
<div class="section-title">Tratamiento prescrito</div>
<table class="medications"><thead><tr><th>Código</th><th>Medicamento</th><th>Prescripción</th><th>Cant. prescrita</th></tr></thead><tbody>
@foreach($consultation->medications as $medication)
    <tr><td class="center">{{ $medication->fua_code ?: '—' }}</td><td>{{ $medication->description }}</td><td>{{ $medication->c ?: '—' }}</td><td class="center">{{ rtrim(rtrim(number_format($medication->prescribed_quantity, 2, '.', ''), '0'), '.') }}</td></tr>
@endforeach
</tbody></table>
@endif

<table class="signatures"><tr><td><div class="line">Firma del paciente / responsable</div></td><td><div class="line"><strong>{{ $consultation->doctor?->name ?: 'Médico tratante' }}</strong><br>CMP: {{ $consultation->doctor?->license_number ?: '________' }} &nbsp; RNE: {{ $consultation->doctor?->specialty_number ?: '________' }}<br>Firma y sello</div></td></tr></table>
<div class="footer">Consulta generada el {{ now()->format('d/m/Y H:i') }} — Consulta N.° {{ $consultation->id }}</div>
</body>
</html>
