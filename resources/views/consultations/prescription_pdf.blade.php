<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm 10mm; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #172033; }
        /*
         * Leave room for both copies plus their padding. Dompdf can calculate the
         * declared height as content-box even with box-sizing, which made 2 x
         * 139 mm overflow the printable area and pushed the second copy to a new
         * page.
         */
        .prescription { position: relative; box-sizing: border-box; height: 130mm; padding: 4mm 1mm 3mm; overflow: hidden; page-break-inside: avoid; }
        .prescription + .prescription { page-break-before: avoid; }
        .prescription:first-child { border-bottom: 1px dashed #8a96a3; }
        .prescription:last-child { padding-top: 5mm; }
        .header { text-align: center; border-bottom: 2px solid #183b6b; padding-bottom: 4px; }
        .header h1 { margin: 0; color: #183b6b; font-size: 14px; }
        .header p { margin: 1px; }
        .meta { width: 100%; margin: 5px 0; border-collapse: collapse; }
        .meta td { padding: 2px 4px; border: 1px solid #ccd5df; }
        .label { width: 18%; background: #eef3f8; font-weight: bold; }
        .section { margin-top: 5px; color: #183b6b; font-weight: bold; }
        .meds { width: 100%; margin-top: 3px; border-collapse: collapse; }
        .meds th, .meds td { padding: 2px 4px; border: 1px solid #60758d; }
        .meds th { background: #183b6b; color: white; font-size: 7px; text-transform: uppercase; }
        .num { text-align: center; }
        .box { min-height: 18px; padding: 3px 4px; border: 1px solid #ccd5df; white-space: pre-line; }
        .signatures { position: absolute; right: 1mm; bottom: 9mm; left: 1mm; width: calc(100% - 2mm); text-align: center; }
        .signatures td { width: 50%; padding: 0 14px; vertical-align: top; }
        .line { margin-top: 12px; padding-top: 3px; border-top: 1px solid #222; }
        .footer { position: absolute; right: 1mm; bottom: 3mm; left: 1mm; color: #667; font-size: 6px; text-align: center; }
    </style>
</head>
<body>
@for ($copy = 0; $copy < 2; $copy++)
    <section class="prescription">
        <div class="header">
            <h1>RECETA MÉDICA</h1>
            <p><strong>Consulta nefrológica</strong></p>
            <p>{{ $consultation->sede?->name }}</p>
        </div>

        <table class="meta">
            <tr>
                <td class="label">Paciente</td><td>{{ $consultation->patient->full_name }}</td>
                <td class="label">Fecha</td><td>{{ $consultation->consultation_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">DNI</td><td>{{ $consultation->patient->dni ?: '—' }}</td>
                <td class="label">H. clínica</td><td>{{ $consultation->patient->medical_history_number ?: '—' }}</td>
            </tr>
            <tr><td class="label">Diagnóstico</td><td colspan="3">{{ $consultation->diagnosis ?: '—' }}</td></tr>
        </table>

        <div class="section">Rp.</div>
        <table class="meds">
            <thead><tr><th>Código FUA</th><th>Descripción</th><th>C</th><th>Prescrita</th><th>Cantidad entregada</th></tr></thead>
            <tbody>
            @foreach($consultation->medications as $medication)
                <tr>
                    <td class="num">{{ $medication->fua_code }}</td><td>{{ $medication->description }}</td>
                    <td class="num">{{ $medication->c }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format($medication->prescribed_quantity, 2, '.', ''), '0'), '.') }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format($medication->delivered_quantity, 2, '.', ''), '0'), '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="section">Indicaciones</div>
        <div class="box">{{ $consultation->treatment_plan ?: 'Sin indicaciones adicionales.' }}</div>

        <table class="signatures"><tr>
            <td><div class="line">Firma del paciente / responsable</div></td>
            <td><div class="line"><strong>{{ $consultation->doctor?->name ?: 'Médico tratante' }}</strong><br>CMP: {{ $consultation->doctor?->license_number ?: '________' }} &nbsp; RNE: {{ $consultation->doctor?->specialty_number ?: '________' }}<br>Firma y sello</div></td>
        </tr></table>
        <div class="footer">Receta generada el {{ now()->format('d/m/Y H:i') }} — Consulta N.° {{ $consultation->id }}</div>
    </section>
@endfor
</body>
</html>
