<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Despacho {{ $requestModel->request_code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h2>Orden de despacho de materiales</h2>
    <p><strong>Código:</strong> {{ $requestModel->request_code }}</p>
    <p><strong>Origen:</strong> {{ $requestModel->toWarehouse->sede->name ?? $requestModel->toWarehouse->name }}</p>
    <p><strong>Destino:</strong> {{ $requestModel->fromWarehouse->sede->name ?? $requestModel->fromWarehouse->name }}</p>
    <p><strong>Estado:</strong> {{ \App\Http\Controllers\WarehouseRequestController::statusLabel($requestModel->status) }}</p>

    <table>
        <thead>
            <tr>
                <th>Material</th>
                <th>Solicitado</th>
                <th>Aprobado</th>
                <th>Enviado</th>
                <th>No enviado</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requestModel->items as $item)
            <tr>
                <td>{{ $item->material->name }}</td>
                <td>{{ number_format($item->qty_requested, 2) }}</td>
                <td>{{ number_format($item->qty_approved, 2) }}</td>
                <td>{{ number_format($item->qty_sent, 2) }}</td>
                <td>{{ number_format(max(0, $item->qty_approved - $item->qty_sent), 2) }}</td>
                <td>{{ $item->not_sent_reason ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <br><br>
    <table style="border: none;">
        <tr>
            <td style="border: none; text-align: center;">_____________________________<br>Encargado almacén principal</td>
            <td style="border: none; text-align: center;">_____________________________<br>Responsable sede destino</td>
        </tr>
    </table>
</body>
</html>
