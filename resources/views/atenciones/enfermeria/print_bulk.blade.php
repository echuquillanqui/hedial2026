<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha HD</title>
    <style>
        @page { margin-left: 1cm; }
        body { 
            font-family: 'Helvetica', Arial, sans-serif; 
            font-size: 8.5px; 
            color: #000; 
            margin: 0;
            line-height: 1.1;
        }
        
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td { padding: 2px; vertical-align: middle; }

        /* Bordes y Estructura */
        .border-full { border: 1px solid #000; }
        .border-bottom { border-bottom: 1px solid #000; }
        .box { border: 1px solid #000; padding: 2px 4px; text-align: center; display: block; }
        
        /* Encabezados Negros */
        .bg-black { background-color: #000; color: #fff; font-weight: bold; padding: 3px 5px; }
        
        .title { text-align: center; font-size: 11px; font-weight: bold; margin-bottom: 10px; text-transform: uppercase; }
        .bold { font-weight: bold; }
        
        /* Tabla de Monitoreo */
        .table-monitoreo th { border: 1px solid #000; background-color: #000; color: #fff; font-size: 7px; padding: 2px; }
        .table-monitoreo td { border: 1px solid #000; text-align: center; height: 18px; }

        /* Medicamentos */
        .med-square { border: 1px solid #000; width: 25px; height: 15px; display: inline-block; text-align: center; line-height: 15px; margin-left: 3px; }
    </style>
</head>
<body>
@foreach($orders as $order)
    @php($date = \Carbon\Carbon::parse($order->fecha_orden)->format('d/m/Y'))
    <div style="page-break-after: {{ $loop->last ? 'auto' : 'always' }};">
        @include('atenciones.enfermeria._print_sheet')
    </div>
@endforeach
</body>
</html>
