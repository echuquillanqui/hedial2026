<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
@page { size: A4 portrait; margin: 22px 28px; }
body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 9px; margin: 0; }
.sheet { page-break-after: always; }
.sheet:last-child { page-break-after: auto; }
.report-header { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 14px; }
.report-header td { width: 33.33%; height: 70px; padding: 0; vertical-align: middle; }
.report-header .logo-left { text-align: left; }
.report-header .logo-right { text-align: right; }
.report-header img { width: 125px; height: 64px; object-fit: contain; }
.title { text-align: center; font-size: 17px; font-weight: bold; text-decoration: underline; white-space: nowrap; margin: 0; }
.metadata { width: 100%; margin-bottom: 13px; }
.metadata td { padding: 4px 3px; }
.label { font-weight: bold; }
.line { border-bottom: 1px solid #333; padding: 0 6px; }
.area { text-align: center; text-decoration: underline; font-size: 11px; margin: 10px 0 5px; page-break-after: avoid; }
table.results { width: 100%; border-collapse: collapse; page-break-inside: auto; }
table.results tr { page-break-inside: avoid; }
table.results th { background: #aeb5b2; font-size: 8px; padding: 5px 4px; border: 1px solid #444; }
table.results td { border: 1px solid #555; padding: 5px 4px; line-height: 1.25; }
.analysis { width: 43%; }
.value { text-align: center; width: 13%; font-weight: bold; }
.unit { text-align: center; width: 12%; }
.reference { text-align: center; font-size: 7px; }
.validation { text-align: center; margin: 18px 0 0; page-break-inside: avoid; }
.validation img { max-width: 200px; max-height: 90px; }
.sheet.sparse table.results th,
.sheet.sparse table.results td { padding-top: 7px; padding-bottom: 7px; }
.sheet.sparse .area { margin-top: 13px; margin-bottom: 7px; }
.sheet.dense { font-size: 8px; }
.sheet.dense .report-header { margin-bottom: 8px; }
.sheet.dense .report-header td { height: 55px; }
.sheet.dense .report-header img { width: 110px; height: 50px; }
.sheet.dense .title { font-size: 15px; }
.sheet.dense .metadata { margin-bottom: 6px; }
.sheet.dense .metadata td { padding: 2px; }
.sheet.dense .area { font-size: 10px; margin: 5px 0 3px; }
.sheet.dense table.results th { padding: 3px; }
.sheet.dense table.results td { padding: 2.5px 3px; line-height: 1.1; }
.sheet.dense .validation { margin-top: 7px; }
.sheet.dense .validation img { max-height: 60px; }
</style>
</head>
<body>
@foreach($orders as $order)<section class="sheet {{ $order->items->count() <= 10 ? 'sparse' : ($order->items->count() > 24 ? 'dense' : '') }}">
<table class="report-header" role="presentation"><tr><td class="logo-left"><img src="{{ public_path('logo/logo_medicos.jpeg') }}" alt="Médicos Salud"></td><td><h1 class="title">RESULTADOS DE LABORATORIO</h1></td><td class="logo-right"><img src="{{ public_path('logo/logo_santafe.jpeg') }}" alt="Clínica Santa Fe"></td></tr></table>
<table class="metadata"><tr><td><span class="label">PACIENTE:</span> <span class="line">{{ strtoupper($order->patient_name) }}</span></td><td><span class="label">DNI:</span> <span class="line">{{ $order->patient?->dni ?: '—' }}</span></td><td><span class="label">PROCEDENCIA:</span> <span class="line">{{ $order->provenance }}</span></td></tr><tr><td><span class="label">FECHA:</span> <span class="line">{{ ($order->sampled_at ?? $order->created_at)->format('d/m/Y H:i') }}</span></td><td colspan="2"><span class="label">H.C.:</span> <span class="line">{{ $order->patient?->medical_history_number ?: '—' }}</span> &nbsp; <span class="label">CONTROL:</span> {{ $order->period }}</td></tr></table>
@foreach($order->items->groupBy(fn($item)=>$item->test->area->name) as $area=>$items)<h2 class="area">ÁREA DE {{ strtoupper($area) }}</h2><table class="results"><thead><tr><th class="analysis">ANÁLISIS</th><th>RESULTADOS</th><th>UNIDAD</th><th>VALORES REFERENCIALES</th></tr></thead><tbody>@foreach($items as $item)<tr><td>{{ $item->test->name }}</td><td class="value">{{ $item->result_value ?: '—' }}</td><td class="unit">{{ $item->test->unit ?: '—' }}</td><td class="reference">{{ $item->test->reference_value ?: '—' }}</td></tr>@endforeach</tbody></table>@endforeach
@if($order->validator?->digital_seal_path && file_exists(storage_path('app/public/'.$order->validator->digital_seal_path)))<div class="validation"><img src="{{ storage_path('app/public/'.$order->validator->digital_seal_path) }}" alt="Sello digital"></div>@endif
</section>@endforeach
</body></html>
