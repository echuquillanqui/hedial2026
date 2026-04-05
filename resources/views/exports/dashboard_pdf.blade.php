<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Hemodiálisis</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { margin: 0 0 4px; font-size: 18px; }
        .subtitle { margin-bottom: 12px; color: #4b5563; }
        .kpi-grid { width: 100%; margin-bottom: 14px; }
        .kpi-grid td { width: 25%; padding: 8px; border: 1px solid #d1d5db; text-align: center; }
        .kpi-title { font-size: 10px; color: #6b7280; text-transform: uppercase; }
        .kpi-value { font-size: 18px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; }
        th { background: #eef2ff; font-size: 10px; text-transform: uppercase; }
        .small { font-size: 10px; }
    </style>
</head>
<body>
    <h1>Dashboard Integral de Hemodiálisis</h1>
    <div class="subtitle">Fecha del corte: {{ $fechaActual }}</div>

    <table class="kpi-grid">
        <tr>
            <td><div class="kpi-title">Sesiones</div><div class="kpi-value">{{ $kpis['totalSesiones'] }}</div></td>
            <td><div class="kpi-title">Completas</div><div class="kpi-value">{{ $kpis['sesionesCompletas'] }}</div></td>
            <td><div class="kpi-title">Atenciones pendientes</div><div class="kpi-value">{{ $kpis['sesionesPendientes'] }}</div></td>
            <td><div class="kpi-title">Materiales base consumidos</div><div class="kpi-value">{{ $kpis['materialesDialisisConsumidos'] }}</div></td>
        </tr>
    </table>

    <table class="kpi-grid">
        <tr>
            <td><div class="kpi-title">Materiales indirectos consumidos</div><div class="kpi-value">{{ $kpis['materialesIndirectosConsumidos'] }}</div></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Paciente</th>
                <th>Código</th>
                <th>Mod</th>
                <th>Turno</th>
                <th>Dial.</th>
                <th>Hep.</th>
                <th>EPO</th>
                <th>Hierro</th>
                <th>Vit B12</th>
                <th>Calc.</th>
                <th>Bicar.</th>
                <th>QB</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($resumenInsumos as $fila)
            <tr>
                <td class="small">{{ $fila['paciente'] }}</td>
                <td>{{ $fila['codigo'] }}</td>
                <td>{{ $fila['modulo'] }}</td>
                <td>{{ $fila['turno'] }}</td>
                <td>{{ $fila['dializador'] > 0 ? $fila['dializador'] : '-' }}</td>
                <td>{{ $fila['heparina'] > 0 ? $fila['heparina'] : '-' }}</td>
                <td>{{ $fila['epo'] > 0 ? $fila['epo'] : '-' }}</td>
                <td>{{ $fila['hierro'] > 0 ? $fila['hierro'] : '-' }}</td>
                <td>{{ $fila['vitamina_b12'] > 0 ? $fila['vitamina_b12'] : '-' }}</td>
                <td>{{ $fila['calcitriol'] > 0 ? $fila['calcitriol'] : '-' }}</td>
                <td>{{ $fila['bicarbonato'] > 0 ? '1' : '-' }}</td>
                <td>{{ $fila['qb'] > 0 ? '1' : '-' }}</td>
                <td>{{ $fila['estado'] }}</td>
            </tr>
            @empty
            <tr><td colspan="13" style="text-align:center;">Sin registros para esta fecha.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Material base de diálisis consumido</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @forelse($materialesDialisis as $material)
            <tr>
                <td>{{ $material['nombre'] }}</td>
                <td style="text-align:center;">{{ $material['cantidad'] }}</td>
            </tr>
            @empty
            <tr><td colspan="2" style="text-align:center;">Sin consumos base para esta fecha.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Material indirecto consumido</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @forelse($materialesIndirectos as $material)
            <tr>
                <td>{{ $material['nombre'] }}</td>
                <td style="text-align:center;">{{ $material['cantidad'] }}</td>
            </tr>
            @empty
            <tr><td colspan="2" style="text-align:center;">Sin materiales indirectos para esta fecha.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
