<!doctype html>
<html lang="es">
<head>
 <meta charset="utf-8">
 <style>
  @page{margin:9mm 8mm}body{font-family:DejaVu Sans;font-size:7px;color:#111}h2,h3,p{margin:0}.title{text-align:center;margin-bottom:14px}.title h2{font-size:12px;margin:5px}.meta{width:68%;margin:0 auto 10px;font-size:8px}.meta td{border:0;padding:2px}.line{border-bottom:1px solid #111}.matrix,.summary{width:100%;border-collapse:collapse}.matrix th,.matrix td,.summary th,.summary td{border:1px solid #111;padding:2px;text-align:center}.matrix th{font-size:6px}.matrix .patient{text-align:left;width:130px}.matrix .number{width:17px}.matrix .sequence{width:38px}.matrix .day{width:14px}.matrix .code{width:30px}.green{background:#91d34f}.red{background:#ff8f93}.total{font-weight:bold}.summary{width:270px;margin-top:10px;font-size:7px}.summary th{font-weight:bold}.summary td:nth-child(2){text-align:left}.notes{margin:10px 0 0 300px;line-height:1.45}.footer{text-align:center;margin-top:18px;font-weight:bold}.muted{color:#555}
 </style>
</head>
<body>
@php($dialyzer=$category===\App\Models\DisposableDiscard::DIALYZER)
<div class="title">
 <h3>ANEXO N.° {{ $dialyzer ? '11-A' : '11-B' }}</h3>
 <h2>CONTROL DIARIO DE DESCARTE DE {{ $dialyzer ? 'DIALIZADORES' : 'SET DE LÍNEAS ARTERIALES Y VENOSAS' }}</h2>
 <strong>(llenar por turno y por día)</strong>
</div>
<table class="meta"><tr><td><strong>IPRESS:</strong></td><td class="line">{{ $configuration->ipress_name ?? '' }}</td></tr><tr><td><strong>MES:</strong></td><td class="line">{{ mb_strtoupper($month->locale('es')->translatedFormat('F')) }}</td><td><strong>AÑO:</strong></td><td class="line">{{ $month->year }}</td></tr><tr><td><strong>FILTROS:</strong></td><td colspan="3">Turno {{ $filters['shift'] ?: 'todos' }} · Módulo {{ $filters['module'] ?: 'todos' }} · Secuencia {{ $filters['sequence'] ?: 'todas' }}</td></tr></table>
<table class="matrix">
 <thead><tr><th rowspan="2" class="number">N.°</th><th rowspan="2" class="patient">PACIENTE</th><th rowspan="2" class="sequence">SECUENCIA</th><th colspan="{{ $month->daysInMonth }}">DÍA DEL MES</th>@for($code=1;$code<=5;$code++)<th rowspan="2" class="code {{ $dialyzer?'green':'red' }}">TOTAL<br>CÓDIGO<br>N.° {{ $code }}</th>@endfor</tr><tr>@for($day=1;$day<=$month->daysInMonth;$day++)<th class="day">{{ $day }}</th>@endfor</tr></thead>
 <tbody>
 @forelse($rows as $row)<tr><td>{{ $loop->iteration }}</td><td class="patient">{{ $row['patient']->full_name }}</td><td>{{ $row['sequence'] }}</td>@for($day=1;$day<=$month->daysInMonth;$day++)<td>{{ $row['days']->get($day) }}</td>@endfor @for($code=1;$code<=5;$code++)<td>{{ $row['totals']->get($code) ?: '' }}</td>@endfor</tr>@empty<tr><td colspan="{{ $month->daysInMonth+8 }}" style="padding:12px">No hay sesiones finalizadas para los filtros seleccionados.</td></tr>@endforelse
 <tr class="total"><td colspan="3">TOTAL</td>@for($day=1;$day<=$month->daysInMonth;$day++)<td>{{ $rows->sum(fn($row)=>(int)filled($row['days']->get($day))) ?: '' }}</td>@endfor @for($code=1;$code<=5;$code++)<td>{{ $rows->sum(fn($row)=>(int)$row['totals']->get($code,0)) ?: '' }}</td>@endfor</tr>
 </tbody>
</table>
<table class="summary"><thead><tr class="{{ $dialyzer?'green':'red' }}"><th>N.° DE CÓDIGO</th><th>{{ $dialyzer?'DIALIZADOR / LOTE':'LOTE DE SET DE LÍNEAS' }}</th><th>CANTIDAD</th></tr></thead><tbody>@for($code=1;$code<=5;$code++)@php($name=$codes->search($code))<tr><td>{{ $code }}</td><td>{{ $name ?: '' }}</td><td>{{ $name ? $orders->filter(fn($order)=>$dialyzer ? trim((string)$order->nurse?->filtro)===$name : $order->hemodialysisMaterialConsumptions->contains(fn($consumption)=>$consumption->material?->name===$name))->count() : '' }}</td></tr>@endfor<tr class="total"><td colspan="2">TOTAL</td><td>{{ $orders->count() }}</td></tr></tbody></table>
<div class="notes"><strong>Instrucciones:</strong><br>1) Se muestran únicamente sesiones de hemodiálisis finalizadas.<br>2) El código identifica el dializador o set de líneas consumido en cada sesión.<br>3) Cada casilla corresponde al día de atención del paciente.<br>4) Los totales se calculan automáticamente con los filtros seleccionados.</div>
<div class="footer">Generado el {{ now()->format('d/m/Y H:i') }}</div>
</body></html>
