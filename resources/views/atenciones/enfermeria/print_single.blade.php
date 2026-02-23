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

    <div class="title">FORMATO DE PROCEDIMIENTO DE HEMODIALISIS</div>

    <table>
        <tr>
            <td width="15%" class="bold">Apellidos y Nombres:</td>
            <td width="85%" class="border-bottom" style="text-transform: uppercase;">
                {{ $order->patient->surname }} {{ $order->patient->last_name }}, {{ $order->patient->first_name }} {{ $order->patient->other_names }}
            </td>
        </tr>
    </table>

    <table style="margin-top: 0px;">
        <tr>
            <td width="20%" class="bold">N° afiliación aseguradora:</td>
            <td width="25%" class="border-bottom">{{ $order->patient->affiliation_code }}</td>
            <td width="20%" class="text-right bold">N° historia clínica:</td>
            <td width="15%"><span class="box">{{ $order->patient->dni }}</span></td>
            <td width="10%" class="text-right bold">Fecha:</td>
            <td width="10%"><span class="box">{{ $date }}</span></td>
        </tr>
    </table>

    <table style="margin-top: 0px;">
        <tr>
            <td width="10%" class="bold">Frecuencia:</td>
            <td width="30%" class="border-bottom">3 VECES POR SEMANA ({{ $order->nurse->frecuencia_hd }})</td>
            <td width="8%" class="text-right bold">Turno:</td>
            <td width="12%"><span class="box">{{ $order->turno }}</span></td>
            <td width="10%" class="text-right bold">N° Sesión:</td>
            <td width="8%"><span class="box">{{ $order->nurse->numero_hd }}</span></td>
            <td width="12%" class="text-right bold">COVID 19:</td>
            <td width="10%"><span class="box">{{ $order->es_covid ? 'SI' : 'NO' }}</span></td>
        </tr>
    </table>

    <div class="bg-black" style="margin-top: 8px;">I. PARTE DE ATENCION MEDICA</div>
    
    <table class="border-full" style="border-bottom: none;">
        <tr>
            <td class="bold" style="padding: 4px;">1.1 EVALUACIÓN INICIAL</td>
            <td class="text-right" style="padding: 4px;">Hora de evaluación inicial: <strong>{{ $order->medical->hora_inicial }}</strong></td>
        </tr>
    </table>

    <div class="border-full" style="padding: 5px; border-bottom: none;">
        <span class="bold">PROBLEMAS CLINICOS:</span> {{ $order->medical->problemas_clinicos }}<br>
        <span class="bold">SIGNOS - SINTOMAS:</span> {{ $order->medical->signos_sintomas }}
    </div>

    <table class="border-full">
        <tr>
            <td width="15%" class="bold">EXAMEN FISICO</td>
            <td width="17%"><span class="bold">PA:</span> {{ $order->medical->pa_inicial }} mmHg</td>
            <td width="17%"><span class="bold">FC:</span> {{ $order->medical->frecuencia_cardiaca }} x'</td>
            <td width="17%"><span class="bold">SATO2:</span> {{ $order->medical->so2 }}%</td>
            <td width="17%"><span class="bold">FIO2:</span> {{ $order->medical->fio2 }}</td>
            <td width="17%"><span class="bold">T°:</span> {{ $order->medical->temperatura }} °C</td>
        </tr>
        <tr>
            <td colspan="6" style="padding: 2px; height: 10px; vertical-align: top; border-top: 1px solid #000;">
                {{ $order->medical->evaluacion }}<br><br>
                <span class="bold">INDICACIONES:</span> {{ $order->medical->indicaciones }}
            </td>
        </tr>
    </table>

    <table class="border-full" style="margin-top: 2px; background-color: #f9f9f9;">
        <tr><td colspan="8" class="bold" style="border-bottom: 1px solid #000;">PRESCRIPCIÓN DEL TRATAMIENTO DE HEMODIALISIS</td></tr>
        <tr>
            <td class="bold">Horas de HD: </td><td class="border-bottom text-center">{{ $order->medical->hora_hd }} HRSS</td>
            <td class="bold">Qb:</td><td class="border-bottom text-center">{{ $order->medical->qb }}</td>
            <td class="bold">Conductividad:</td><td class="border-bottom text-center">{{ $order->medical->cnd }}</td>
            <td class="bold">Heparina:</td><td class="border-bottom text-center">{{ $order->medical->heparina }} Ul</td>
        </tr>
        <tr>
            <td class="bold">Qd:</td><td class="border-bottom text-center">{{ $order->medical->qd }}</td>
            <td class="bold">Na Inicial:</td><td class="border-bottom text-center">{{ $order->medical->na_inicial }} Meq/L</td>
            <td class="bold">Peso. Seco:</td><td class="border-bottom text-center">{{ $order->medical->peso_seco }} Kg</td>
            <td class="bold">Bicarbonato:</td><td class="border-bottom text-center">{{ $order->medical->bicarbonato }}</td>
        </tr>

        <tr>
            <td class="bold">Na Final:</td><td class="border-bottom text-center">{{ $order->medical->na_final }} Meq/L</td>
            <td class="bold">Peso Inicial:</td><td class="border-bottom text-center">{{ $order->medical->peso_inicial }}</td>
            <td class="bold">Perfil de Na:</td><td class="border-bottom text-center">{{ $order->medical->perfil_na }}</td>
            <td class="bold">Ultrafiltrado:</td><td class="border-bottom text-center">{{ $order->medical->uf }}</td>
        </tr>

        <tr>
            <td class="bold">Perfil de Uf:</td><td class="border-bottom text-center">{{ $order->medical->perfil_uf }}</td>
            <td><td> </td> </td>
            <td><td> </td> </td>
            <td><td> </td> </td>
        </tr>

        <tr>
            <td colspan="2" class="bold">PRESCRIPCION PARA DIALIZADOR:</td>
            <td class="bold">Area de dializador:</td><td class="border-bottom text-center">{{ $order->medical->area_filtro }}</td>
            <td class="bold">Membrana de dializador:</td><td class="border-bottom text-center">{{ $order->medical->membrana }}</td>
            <td class="bold"></td><td></td>
        </tr>

        <tr>
            <td colspan="4" style="height: 90px; vertical-align: bottom; text-align: center; padding-bottom: 5px;">
                <div style="width: 50%; margin: 0 auto;">
                    <div class="bold" style="font-size: 8px; text-transform: uppercase;">
                        
                    </div>
                    <div style="border-top: 1px solid #000; font-size: 7px; margin-top: 2px;" class="bold">
                        {{ $order->medical->medico_inicia_nombre ?? 'DRA. CYNTHIA LISETTE YANQUI BALLENA' }}<br>
                        MEDICO NEFROLOGO<br>
                        C.M.P. {{ $order->medical->medico_inicia_cmp ?? '82062' }} - R.N.E. {{ $order->medical->medico_inicia_rne ?? '51592' }}<br>
                        <span>Médico que Inicia HD</span>
                    </div>
                </div>
            </td>
            <td colspan="4" style="height: 90px; vertical-align: bottom; text-align: center; padding-bottom: 5px;">
                <div style="width: 50%; margin: 0 auto;">
                    <div class="bold" style="font-size: 8px; text-transform: uppercase;">
                    </div>
                    <div style="border-top: 1px solid #000; font-size: 7px; margin-top: 2px;" class="bold">
                        {{ $order->medical->medico_finaliza_nombre ?? 'DRA. CYNTHIA LISETTE YANQUI BALLENA' }}<br>
                        MEDICO NEFROLOGO<br>
                        C.M.P. {{ $order->medical->medico_finaliza_cmp ?? '82062' }} - R.N.E. {{ $order->medical->medico_finaliza_rne ?? '51592' }}<br>
                        <span>Médico que Finaliza HD</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="bg-black" style="margin-top: -1px; display: table; width: 100%;">
        <div style="display: table-cell; width: 50%; padding: 0px 0px;">I.2. EVALUACIÓN FINAL</div>
        <div style="display: table-cell; width: 50%; text-align: right; padding: 0px 0px; font-weight: normal;">
            Hora de evaluacion final: {{ $order->medical->hora_final ?? '18:31' }}
        </div>
    </div>

    <div class="border-full" style="border-top: none; padding: 2px; min-height: 12px;">
        <span class="bold uppercase" style="font-size: 7.5px;">CONDICION CLINICA DEL PACIENTE AL FINALIZAR HEMODIALISIS Y OTROS:</span> 
        {{ $order->medical->evaluacion_final ?? 'SIN COMPLICACIONES' }} 
    </div>

    <div style="margin-top: 5px; font-size: 10px; font-weight: bold;">
    II. PARTE DE ATENCION DE ENFERMERIA
</div>

<div class="bg-black" style="margin-top: 2px;">
    II.1. ATENCION DE ENFERMERIA - VALORACION
</div>

<div class="bold" style="margin-top: 5px; font-size: 9px;">II. PARTE DE ATENCION DE ENFERMERIA</div>

<div class="bg-black">II.1. ATENCION DE ENFERMERIA - VALORACION</div>
<div class="border-full" style="border-top: none; padding: 2px 5px;">
    <div style="border-bottom: 1px dotted #000;"><span class="bold">S.-</span> {{ $order->nurse->s }}</div>
    <div style="border-bottom: 1px dotted #000;"><span class="bold">O.-</span> {{ $order->nurse->o }}</div>
    <div style="border-bottom: 1px dotted #000;"><span class="bold">A.-</span> {{ $order->nurse->a }}</div>
    <div><span class="bold">P.-</span> {{ $order->nurse->p }}</div>
</div>

<table class="border-full" style="border-top: none; table-layout: fixed; width: 100%; border-collapse: collapse;">
    <tr>
        <td width="18%" style="border: 1px solid #000;">Peso Inicial: <span class="box-val">{{ $order->nurse->peso_inicial }}</span></td>
        <td width="18%" style="border: 1px solid #000;">PA Inicial: <span class="box-val">{{ $order->nurse->pa_inicial }}</span></td>
        <td width="18%" style="border: 1px solid #000;">N° de Maq: <span class="box-val">{{ $order->nurse->numero_maquina }}</span></td>
        <td width="30%" style="border: 1px solid #000;">Marca/Modelo de maquina: <span class="box-val">{{ $order->nurse->marca_modelo }}</span></td>
        <td width="16%" style="border: 1px solid #000;">N° de puesto: <span class="box-val">{{ $order->nurse->puesto }}</span></td>
    </tr>
    <tr>
        <td style="border: 1px solid #000;">Area/membrana de filtro: <span class="box-val">{{ $order->medical->area_filtro }} m2</span></td>
        <td colspan="2" style="border: 1px solid #000;">Ultrafiltrado programado: <span class="box-val">{{ $order->medical->uf }} cc</span></td>
        <td style="border: 1px solid #000;">Acceso Arterial: <span class="box-val">{{ $order->nurse->acceso_arterial }}</span></td>
        <td style="border: 1px solid #000;">Acceso Venoso: <span class="box-val">{{ $order->nurse->acceso_venoso }}</span></td>
    </tr>
</table>

<div class="bg-black" style="margin-top: -1px;">II.2.ADMINISTRACION: DE MEDICAMENTOS</div>
<table class="border-full" style="border-top: none; table-layout: fixed; width: 100%; border-collapse: collapse;">
    <tr>
        <td width="30%" style="border: 1px solid #000; font-size: 8px;">Hierro 20 mg Fe/mL INY 5 mL</td>
        <td width="10%" style="border: 1px solid #000; text-align: center; font-size: 10px; font-weight: bold;">
            {{ $order->nurse->hierro }}
        </td>
        
        <td width="22%" style="border: 1px solid #000; font-size: 8px;">Epoetina alfa 2000 UI/mL </td>
        <td width="8%" style="border: 1px solid #000; text-align: center; font-size: 10px; font-weight: bold;">
            {{ $order->nurse->epo2000 }} 
        </td>
        
        <td width="22%" style="border: 1px solid #000; font-size: 8px;">Epoetina alfa 4000 UI/mL </td>
        <td width="8%" style="border: 1px solid #000; text-align: center; font-size: 10px; font-weight: bold;">
            {{ $order->nurse->epo4000 }} 
        </td>
    </tr>
    <tr>
        <td style="border: 1px solid #000; font-size: 8px;">Hidroxicobalamina 1mg/mL INY 1mL </td>
        <td style="border: 1px solid #000; text-align: center; font-size: 10px; font-weight: bold;">
            {{ $order->nurse->vitamina_b12 }}
        </td>
        
        <td style="border: 1px solid #000; font-size: 8px;">Calcitriol 1 mcg/mL INY </td>
        <td style="border: 1px solid #000; text-align: center; font-size: 10px; font-weight: bold;">
            {{ $order->nurse->calcitriol }} 
        </td>
        
        <td style="border: 1px solid #000; font-size: 8px;">Otros </td>
        <td style="border: 1px solid #000; text-align: center; font-size: 10px; font-weight: bold;">
            {{ $order->nurse->otros_med }} 
        </td>
    </tr>
</table>

    <div class="bg-black" style="margin-top: -1px;">
    II.3. EVOLUCIÓN DEL TRATAMIENTO DE HEMODIALISIS
</div>
<table class="table-monitoreo" style="border-top: none; table-layout: fixed; width: 100%; border-collapse: collapse; margin-top: -1px;">
    <thead>
        <tr style="background-color: #ffffff !important; color: #000000 !important; font-size: 10px;">
            <th width="8%" style="border: 1px solid #000; padding: 2px; background-color: #ffffff; color: #000;">HR</th>
            <th width="9%" style="border: 1px solid #000; padding: 2px; background-color: #ffffff; color: #000;">P.A.</th>
            <th width="7%" style="border: 1px solid #000; padding: 2px; background-color: #ffffff; color: #000;">FC</th>
            <th width="8%" style="border: 1px solid #000; padding: 2px; background-color: #ffffff; color: #000;">QB</th>
            <th width="8%" style="border: 1px solid #000; padding: 2px; background-color: #ffffff; color: #000;">CND</th>
            <th width="8%" style="border: 1px solid #000; padding: 2px; background-color: #ffffff; color: #000;">RA</th>
            <th width="8%" style="border: 1px solid #000; padding: 2px; background-color: #ffffff; color: #000;">RV</th>
            <th width="8%" style="border: 1px solid #000; padding: 2px; background-color: #ffffff; color: #000;">PTM</th>
            <th width="50%" style="border: 1px solid #000; padding: 2px; background-color: #ffffff; color: #000;">OBSERVACIONES</th>
        </tr>
    </thead>
    <tbody style="font-size: 9px;">
        @foreach($order->treatments as $t)
        <tr class="text-center">
            <td style="border: 1px solid #000; height: 10px;">{{ \Carbon\Carbon::parse($t->hora)->format('H:i') }}</td>
            <td style="border: 1px solid #000;" class="bold">{{ $t->pa }}</td>
            <td style="border: 1px solid #000;">{{ $t->fc }}</td>
            <td style="border: 1px solid #000;">{{ $t->qb }}</td>
            <td style="border: 1px solid #000;">{{ $t->cnd }}</td>
            <td style="border: 1px solid #000;">{{ $t->ra }}</td>
            <td style="border: 1px solid #000;">{{ $t->rv }}</td>
            <td style="border: 1px solid #000;">{{ $t->ptm }}</td>
            <td style="border: 1px solid #000; text-align: left; padding-left: 5px; font-size: 10px;">{{ $t->observacion }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table style="border-top: none; table-layout: fixed; width: 100%; border-collapse: collapse; margin-top: -1px;">
    <tr class="bold" style="font-size: 8.5px;">
        <td width="42%" style="border: 1px solid #000; padding: 4px; background-color: #ffffff;">
            P.A. Final: <span style="font-size: 11px; margin-left: 10px;">{{ $order->nurse->pa_final }}</span>
        </td>
        <td width="58%" style="border: 1px solid #000; padding: 4px; background-color: #ffffff;">
            Peso Final: <span style="font-size: 11px; margin-left: 10px;">{{ $order->nurse->peso_final }} Kg</span>
        </td>
    </tr>
</table>

    <div class="border-full" style="border-top: none; padding: 4px; font-size: 8px; margin-top: -1px;">
    <div style="margin-bottom: 2px;">
        <span class="bold">E. OBSERVACION FINAL:</span> 
        {{ $order->nurse->observacion_final }}
    </div>
    <div style="border-top: 1px solid #ccc; padding-top: 2px;">
        <span class="bold">Aspecto de filtro:</span> {{ $order->nurse->aspecto_dializador }}
    </div>
</div>

<div style="font-size: 6.5px; font-style: italic; margin-top: 2px; color: #333;">
    (*) El número de maquina asignado debe coincidir con el número de serie del equipo.
</div>

<table class="" style="margin-top: 5px; table-layout: fixed; width: 100%; border-collapse: collapse;">
    <tr>
        <td colspan="4" style="height: 100px; vertical-align: bottom; text-align: center; padding-bottom: 5px;">
            <div style="width: 50%; margin: 0 auto;">
                <div style="border-top: 1px solid #000; font-size: 9px; margin-top: 2px;" class="bold">
                    {{ $order->nurse->enfermeroInicia->name ?? '_______________________' }}<br>
                    ENFERMERO(A)<br>
                    {{ $order->nurse->enfermeroInicia->license_number ?? '-------' }}<br>
                    <span>Enfermero(a) que Inicia HD</span>
                </div>
            </div>
        </td>

        <td colspan="4" style="height: 100px; vertical-align: bottom; text-align: center; padding-bottom: 5px;">
            <div style="width: 50%; margin: 0 auto;">
                <div style="border-top: 1px solid #000; font-size: 9px; margin-top: 2px;" class="bold">
                    {{ $order->nurse->enfermeroFinaliza->name ?? '_______________________' }}<br>
                    ENFERMERO(A)<br>
                    {{ $order->nurse->enfermeroFinaliza->license_number ?? '-------' }}<br>
                    <span>Enfermero(a) que Finaliza HD</span>
                </div>
            </div>
        </td>
    </tr>
</table>

</body>
</html>