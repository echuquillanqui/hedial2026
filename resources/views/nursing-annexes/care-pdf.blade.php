<!doctype html>
<html lang="es"><head><meta charset="utf-8"><style>
@page{margin:.7cm .8cm}body{font-family:DejaVu Sans,sans-serif;font-size:8px;color:#111}.header{position:relative;text-align:center;margin-bottom:8px}.logo{position:absolute;left:0;top:0;width:125px;max-height:55px;object-fit:contain}.header h1{font-size:12px;margin:0;line-height:1.25}.meta{font-size:10px;font-weight:bold;margin:12px 0 8px;text-align:center}.meta span{display:inline-block;margin:0 18px;border-bottom:1px dotted #222;min-width:110px;padding-bottom:2px}table{width:100%;border-collapse:collapse;table-layout:fixed}th,td{border:1px solid #111;padding:2px 4px;line-height:1.1}th{font-size:9px}.procedure{font-weight:bold;width:31%}.detail{width:22%}.quantity{width:10%;text-align:center;font-weight:bold;font-size:9px}.observations{width:37%}.foot{font-size:9px;font-weight:bold;margin-top:10px}.code{font-size:7px;text-align:right;color:#555;margin-top:4px}
</style></head><body>
<div class="header">@if($logoData)<img class="logo" src="{{ $logoData }}">@endif<h1>ANEXO N.° 12</h1><h1>REGISTRO DIARIO DE ATENCIONES DE ENFERMERÍA Y COMPLICACIONES DURANTE LA<br>SESIÓN DE HEMODIÁLISIS</h1><div>(llenar por módulo y por día)</div></div>
<div class="meta"><span>Fecha: {{ $annex->work_date->format('d/m/Y') }}</span><span>Frecuencia: {{ $annex->frequency }}</span><span>Módulo: {{ $annex->module }}</span></div>
<table><thead><tr><th colspan="2">Procedimientos</th><th class="quantity">Cantidad<br>(*)</th><th class="observations">Observaciones<br><small>(De presentarse complicaciones, consignar nombre de paciente)</small></th></tr></thead><tbody>
@php($previous = null)
@foreach($rows as $key => [$procedure, $detail])
<tr><td class="procedure">{{ $procedure === $previous ? '' : $procedure }}</td><td class="detail">{{ $detail }}</td><td class="quantity">@foreach(['1','2','3','4'] as $shift)@if(data_get($annex->values,"$key.shifts.$shift",0)>0)T{{ $shift }}: {{ data_get($annex->values,"$key.shifts.$shift") }}@if(!$loop->last)<br>@endif @endif @endforeach @if(data_get($annex->values,"$key.quantity",0)===0)0@endif</td><td>{{ data_get($annex->values, "$key.observations") }}</td></tr>
@php($previous = $procedure)
@endforeach
</tbody></table>
<div class="foot">(*) La cantidad debe estar diferenciada por turnos de atención.</div><div class="code">ID: {{ $annex->code }} · Generado: {{ $annex->updated_at->format('d/m/Y H:i') }}</div>
</body></html>
