
<table width="100%" style="border-collapse:collapse; margin-top: -25px -auto; float:right;">
    <tr>
      <th><img src="{{ asset('img/brand/logo_hysdialisis.png') }}" alt="LOGO" width="80px"></th>
    </tr>
</table>

<h4 style="text-align:center; font-family:Arial, Helvetica, sans-serif; ">FORMATO N° 006</h4>
<p style="text-align:center; font-size:0.7rem; margin-top:-10; font-family:Arial, Helvetica, sans-serif;">
    <strong>FICHA DE EVALUACIÓN Y SEGUIMIENTO DEL ACCESO VASCULAR EN SALA DE TRATAMIENTO</strong>
</p>

<p style="margin-left:-5px; font-size:0.7rem; font-family:Arial, Helvetica, sans-serif;"><strong>I. DATOS DE FILIACIÓN</strong></p>

<table width="95%" style="margin-top:-8px; margin-left:-5px; margin-right:-1px; font-family:Arial, Helvetica, sans-serif; font-size:0.6rem; border-collapse:collapse">
    <tr>
        <td style="margin-right:5px !important; width:100px">NOMBRE DEL PACIENTE:</strong></td>
        <td style="text-align: center !important; border: 1px solid !important; width:180px;height:20px !important; font-size:0.8rem;">{{$tracing->patient->name }}</td>
        <td style="margin-left:5px !important; width:0px">EDAD</td>
        <td style="text-align: center !important; border: 1px solid !important; width: 80px; font-size:0.7rem;">{{ $tracing->patient->age }}</td>
        <td style="margin-left:5px !important; width:35px">SEXO</td>
        <td style="text-align: center !important; border: 1px solid !important; width: 70px; font-size:0.7rem;">{{ $tracing->patient->sex }}</td>
        <td style="margin-left:5px !important; width:120px">HOPS. DE REFERENCIA</td>
        <td style="text-align: center !important; border: 1px solid !important; width: 180px; font-size:0.7rem;">{{ $tracing->patient->hosp_origin }}</td>
    </tr>

    <tr>
        <td></td>
        <td></td>
        <td style="margin-left:5px !important; height:20px">SALA</td>
        <td style="text-align: center !important; border: 1px solid !important; font-size:0.7rem;">{{$tracing->room->name }}</td>
        <td style="margin-left:5px !important;">TURNO</td>
        <td style="text-align: center !important; border: 1px solid !important; font-size:0.7rem;">{{$tracing->shift->name }}</td>
        <td style="margin-left:5px !important;">UNIDAD DE DIALISIS</td>
        <td style="text-align: center !important; border: 1px solid !important; width: 200px; font-size:0.7rem;">{{ $tracing->format006->unit_dial }}</td>
    </tr>

    <tr>
        <td></td>
        <td></td>
        <td style="margin-left:5px !important; height:20px">FREC.</td>
        <td style="text-align: center !important; border: 1px solid !important; font-size:0.7rem;">{{$tracing->format006->frec }}</td>
        <td></td>
        <td></td>
        <td style="margin-left:5px !important;">NEFROLOGO TRATANTE</td>
        <td style="text-align: center !important; border: 1px solid !important; width: 200px; font-size:0.7rem;">{{ $tracing->format006->nefro_treat }}</td>
    </tr>
    <tr>
        <td style="margin-right:5px !important;">AUTOGENERADO:</strong></td>
        <td style="text-align: center !important; border: 1px solid !important;height:10px !important; font-size:0.8rem;">{{$tracing->patient->code }}</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td style="margin-left:5px !important; height:20px">FRECUENCIA DE CONSULTA</td>
        <td style="text-align: center !important; border: 1px solid !important; width: 200px">{{ $tracing->format006->consult_frec }}</td>
    </tr>
</table>

<p style="margin-left:-5px; font-size:0.7rem; font-family:Arial, Helvetica, sans-serif;"><strong>II. ANTECEDENTES CLINICOS</strong></p>

<table width="100%" style="margin-top:-8px; margin-left:-5px; margin-right:-13px; font-family:Arial, Helvetica, sans-serif; font-size:0.6rem; border-collapse:collapse">

    <tr>
        <td style="margin-left:10px !important; text-align: center !important; width:116px">CAUSA DE LA ERCA</td>
        <td colspan="8" style="text-align: center !important; border: 1px solid !important; height:20px !important; width:50% !important">{{ $tracing->format006->cause_erca }}</td>
        <td></td>
    </tr>

    <tr>
        <td></td>
        <td colspan="4" style="text-align: left !important; !important;height:10px !important">TIEMPO</td>
        <td colspan="4" style="text-align: left !important; !important;height:10px !important">TIPO DE ACCESO VSC</td>
        <td></td>
    </tr>
    
    <tr>
        <td style="margin-right:5px !important; text-align: center !important;">PREDIALISIS</td>
        <td colspan="4" style="text-align: center !important; border: 1px solid !important;height:20px !important">{{$tracing->format006->time_predial }}</td>
        <td colspan="4" style="text-align: center !important; border: 1px solid !important;height:20px !important">{{$tracing->format006->access_vsc }}</td>
        <td></td>
    </tr>
</table>

<table width="100%" style="margin-top:2px; margin-left:-5px; font-family:Arial, Helvetica, sans-serif; font-size:0.6rem; border-collapse:collapse">
    <tr>
        <td colspan="2"></td>
        <td style="text-align: left !important; !important; height:20px !important">FECHA</td>
        <td style="text-align: left !important; !important; height:20px !important">DP</td>
        <td style="text-align: left !important; !important; height:20px !important">HEMOD</td>
        <td style="text-align: left !important; !important; height:20px !important">ACCESO VASC</td>
        <td style="text-align: left !important; !important; height:20px !important">ESTABLECIMIENTO DE SALUD</td>
    </tr>

    <tr>
        <td colspan="2" style="margin-left:20px !important; text-align: left !important; width:106px; height:20px !important">PRIMERA DIALISIS</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important;">{{$tracing->format006->date_predl }}</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important">{{$tracing->format006->dp_predl }}</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important">{{$tracing->format006->hemod_dl }}</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important">{{$tracing->format006->acc_vasc_dl }}</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important">{{$tracing->format006->establishment }}</td>
    </tr>
</table>

<table width="100%" style="margin-top:2px; margin-left:-5px; margin-right:-13px; font-family:Arial, Helvetica, sans-serif; font-size:0.6rem; border-collapse:collapse">

    <tr>
        <td colspan="2" style="width:220px">ACCESO VASCULAR</td>
        <td style="text-align: center !important; height:10px !important; width:100px">CVC</td>
        <td style="text-align: center !important; height:10px !important; width:100px">FAV</td>
        <td style="width:70px !important"> </td>
        <td colspan="2" style="text-align: center !important;">TRATAMIENTO HIA / FRECUENCIA</td>
    </tr>

    <tr>
        <td colspan="2">TIEMPO PROM PERMANENCIA</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important;">{{$tracing->format006->tpp_acc_cvc }}</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important;">{{$tracing->format006->tpp_acc_cvc }}</td>
        <td></td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important;">{{$tracing->format006->hia1 }}</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important;">{{$tracing->format006->frecuency1 }}</td>
    </tr>
    <tr>
        <td colspan="2" style="margin-right:25px !important;">NUMERO</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important;">{{$tracing->format006->num_acc_cvc }}</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important;">{{$tracing->format006->num_acc_cvc }}</td>
        <td></td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important;">{{$tracing->format006->hia2 }}</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important;">{{$tracing->format006->frecuency2 }}</td>
    </tr>
    <tr>
        <td colspan="2" style="margin-right:25px !important;">CAUSA DE CAMBIO Y/O PERDIDA</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important;">{{$tracing->format006->lost_acc_cvc }}</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important;">{{$tracing->format006->lost_acc_cvc }}</td>
        <td></td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important;">{{$tracing->format006->hia3 }}</td>
        <td style="text-align: center !important; border: 1px solid !important; height:20px !important;">{{$tracing->format006->frecuency3 }}</td>
    </tr>
</table>

<table style="margin-top:8px; margin-left:-5px; margin-right:-13px; font-family:Arial, Helvetica, sans-serif; font-size:0.6rem; border-collapse:collapse">

    <tr>
        <td style="width: 100px">TRANSPLANTE</td>
        <td style="margin-left:10px; border: 1px solid; text-align:center;width:150px; height:20px !important;"><strong>{{$tracing->format006->transplant }}</strong></td>
        <td style="text-align:left; width:50px; margin-left:10px !important">FECHA</td>
        <td style="margin-left:10px; border: 1px solid; text-align:center;width:150px; height:20px !important;"><strong>{{$tracing->format006->date_transplant }}</strong></td>
    </tr>

</table>


<table width="100%" style="margin-top:18px; margin-left:-20px; margin-right:-3px; font-family:Arial, Helvetica, sans-serif; font-size:0.6rem; border-collapse:collapse;border:1px solid black; text-align:center !important;">
    
    <thead style="border: 1px solid black">
        <tr>
            <td style="border: 1px solid black;" rowspan="3">N°</td>
            <td style="border: 1px solid black;" rowspan="3">Mes</td>
            <td style="border: 1px solid black;" rowspan="3">Fecha</td>
            <td style="border: 1px solid black;" colspan="3">CONDICION DEL ACCESO VASCULAR ACTUAL</td>
            <td style="border: 1px solid black;" colspan="2">FACTOR CLINICO</td>
            <td style="border: 1px solid black;" colspan="5">FACTORES HEMODINAMICO</td>
            <td style="border: 1px solid black;" rowspan="3">PROBLEMAS</td>
            <td style="border: 1px solid black;" rowspan="3">OBSERVACION</td>
        </tr>
    
        <tr>
            <td style="border: 1px solid black" rowspan="2">TIPO</td>
            <td style="border: 1px solid black" rowspan="2">TIEMPO.</td>
            <td style="border: 1px solid black" rowspan="2">UBICAC.</td>
            <td style="border: 1px solid black" colspan="2">TRILL</td>
            <td style="border: 1px solid black" colspan="3">PRESION ARTERIAL</td>
            <td style="border: 1px solid black" colspan="2">PARAMETROS</td>
        </tr>
    
        <tr>
            <td style="border: 1px solid black">CARAC</td>
            <td style="border: 1px solid black">DIST.</td>
            <td style="border: 1px solid black">INIC.</td>
            <td style="border: 1px solid black">FIN</td>
            <td style="border: 1px solid black">QB</td>
            <td style="border: 1px solid black">RA</td>
            <td style="border: 1px solid black">RV</td>
        </tr>
    </thead>

    <tbody style="border: 1px solid black">
        <tr>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->position }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->month }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->date_register }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->type }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->time }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->location }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->carac }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->dist }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->start }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->end }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->qb }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->ra }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->rv }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->trouble }}</td>
            <td style="border: 1px solid black; width: 3%; height: 50px">{{ $tracing->format006->observation }}</td> 
        </tr>
          
    </tbody>



</table>