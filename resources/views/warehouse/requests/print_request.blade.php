<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud {{ $requestModel->request_code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h2>Solicitud de materiales entre sedes</h2>
    <p><strong>Código:</strong> {{ $requestModel->request_code }}</p>
    <p><strong>Sede solicitante:</strong> {{ $requestModel->fromWarehouse->sede->name ?? $requestModel->fromWarehouse->name }}</p>
    <p><strong>Almacén principal:</strong> {{ $requestModel->toWarehouse->sede->name ?? $requestModel->toWarehouse->name }}</p>
    <p><strong>Solicitante:</strong> {{ $requestModel->requester->name ?? '-' }}</p>
    <p><strong>Estado:</strong> {{ \App\Http\Controllers\WarehouseRequestController::statusLabel($requestModel->status) }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Material</th>
                <th>Unidad</th>
                <th>Cantidad solicitada</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requestModel->items as $idx => $item)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $item->material->name }}</td>
                <td>{{ $item->material->unit }}</td>
                <td>{{ number_format($item->qty_requested, 2) }}</td>
                <td>{{ $item->notes ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <br><br>
    <table style="border: none;">
        <tr>
            <td style="border: none; text-align: center;">_____________________________<br>Firma solicitante</td>
            <td style="border: none; text-align: center;">_____________________________<br>Recepción almacén principal</td>
        </tr>
    </table>
</body>
</html>
