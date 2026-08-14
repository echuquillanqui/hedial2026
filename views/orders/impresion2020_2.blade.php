<table style="margin-top:-25px;border:solid 1px black; float: right; height: 9%">
  <th style="font-size: 0.7rem;">
    <p>Coloque el adhesivo del Código de Barras y/o Lote de Dializador</p>
  </th>
</table>
<br>
<br>
<br>
<table width="100%" style="margin-top: -5px;">
  <tr>
    <th style="text-align: center">
      <h4 width="100%">FICHA DE PRESCRIPCION Y EVOLUCIÓN DE LA SESIÓN DE HEMODIALISIS</h4>
    </th>
  </tr>
</table>

    <table style="margin-top:-20px;">
      <tr>
        <td colspan="4" style="font-size: 0.8rem"><strong>Apellidos y Nombres: </strong></th>
          <td width="453px" style="border-bottom: solid 1px; font-size: 0.8rem; text-transform:uppercase;">{{ $order->patient->name }}</td>
          <td style="font-size: 0.8rem"><strong>Fecha: </strong></td>
            <td style="border: solid 1px; padding: 2px; font-size: 0.8rem">{{ $date }}</td>
          </tr>
    </table>

    <table style="margin-top: 0px; font-size: 0.8rem;" width="100%">
      <tr>
        <td style="width: 5%;"><strong>Frecuencia:</strong></td>
        <td style="border:solid 1px; width: 15%; text-align: center; font-size:0.7rem;">{{ $order->nurse->frequency }} ( {{ $order->nurse->others }} )<</td>

        <td style="width: 3%"><strong>Turno: </strong></td>
        <td style="border:solid 1px;width: 7%; text-align: center">{{ $order->shift->name}}</td>

        <td style="width: 6%"><strong>N° de Sesión: </strong></td>
        <td style="border:solid 1px; width: 5%; text-align: center">{{ $order->nurse->nhd }}</td>

        <td style="width: 5%"><strong>N° de HC: </strong></td>
        <td style="border:solid 1px;width: 5%; text-align: center">{{ $order->nurse->hcl }}</td>
      </tr>
    </table>

    <table width="100%" style="border:2px solid; border-collapse: collapse;">
      <tr>
        <td style="font-size: 0.8rem; color: white; background-color: black; text-align: center;" colspan="2"><strong>EVALUACIÓN MÉDICA </strong></td>
      </tr>

      <table width="100%" style="border:2px solid; border-collapse: collapse; margin-top: -3px;">
        <tr>
          <td style="font-size: 0.6rem; width: 95%; text-transform:uppercase;" colspan="10">PROBLEMAS CLINICOS: {{ $order->medical->clinical_trouble }} </td>
          <td style="font-size: 0.6rem; width: 5%; text-align:right" colspan="2">P.A: {{ $order->medical->start_pa }}</td>
        </tr>

        <tr>
          <td style="font-size: 0.6rem; width: 10%" colspan="11"></td>
          <td style="font-size: 0.6rem; width: 10%; text-align:right" colspan="1">F.C: {{ $order->medical->fc }}</td>
        </tr>

        <tr>
          <td style="font-size: 0.6rem;" colspan="10"></td>
          <td colspan="2"></td>
        </tr>

        <tr>
          <td style="font-size: 0.6rem;" colspan="1"></td>
          <td colspan="11"></td>
        </tr>

        
      </table>

      <tr>
        <table width="100%" style="border:2px solid; border-collapse: collapse; border-top: none; margin-top: -2px">
          <tr>
            <td style="font-size: 0.6rem; margin-bottom: 1px; height: 30px" colspan="2">EXAMEN: {{ $order->medical->evaluation }}</td>
          </tr>
        </table>
      </tr>
    </table>

    <table width="100%" style="border:2px solid; border-collapse: collapse; border-top: none;">
      <tr>
        <td style="font-size: 0.7rem; margin-bottom: 1px;" colspan="8">PRESCRIPCIÓN</td>
      </tr>
      <tr>
        <td style="font-size: 0.7rem; width: 9%">Tiempo Dialisis:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 10%">{{ $order->medical->hour_hd }} HRSS</td>
        <td style="font-size: 0.7rem; width: 10%">Flujo Sanguineo(Qb):</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 10%">{{ $order->medical->qb }} cc</td>
        <td style="font-size: 0.7rem; width: 5%">Perfil UF:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 8%">{{ $order->medical->profile_uf }} </td>
        <td style="font-size: 0.7rem; width: 6%">Dializador:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 10%">{{ $order->medical->dializer }} </td>
      </tr>
      <tr>
        <td style="font-size: 0.7rem; width: 2%">Heparina:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 5%">{{ $order->medical->heparin }} UI</td>
        <td style="font-size: 0.7rem; width: 11%">Flujo Dializado(Qd):</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 10%">{{ $order->medical->qd }} cc</td>
        <td style="font-size: 0.7rem; width: 7%">Sodio Inicial:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 8%">{{ $order->medical->start_na }} Meq/L</td>
        <td style="font-size: 0.7rem; width: 9%">Tipo Membrana:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 10%">{{ $order->medical->membrane }} </td>
      </tr>

      <tr>
        <td style="font-size: 0.7rem; width: 9%">Peso Seco:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 10%">{{ $order->medical->dry_weight }} Kg</td>
        <td style="font-size: 0.7rem; width: 10%">Solución:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 10%">{{ $order->medical->bathroom }}</td>
        <td style="font-size: 0.7rem; width: 5%">Sodio Final:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 8%">{{ $order->medical->end_na }} Meq/L</td>
        <td style="font-size: 0.7rem; width: 6%">Área:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 10%">{{ $order->medical->area_filter }} m2 </td>
      </tr>
      <tr>
        <td style="font-size: 0.7rem; width: 9%">Ultrafiltración:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 10%">{{ $order->medical->uf }} cc</td>
        <td style="font-size: 0.7rem; width: 10%">Bicarbonato:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 10%">{{ $order->medical->bircarbonat }}</td>
        <td style="font-size: 0.7rem; width: 5%" colspan="1"></td>
        <td style="font-size: 0.7rem; width: 8%"> </td>
        <td style="font-size: 0.7rem; width: 6%">Serologia:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 10%">{{ $order->medical->serological }} </td>
      </tr>

      <tr>
        <td style="font-size: 0.7rem; width: 9%"></td>
        <td style="font-size: 0.7rem; width: 10%"></td>
        <td style="font-size: 0.7rem; width: 10%">Calcio en la Solución:</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 10%">{{ $order->medical->na_in_solution }}</td>
        <td style="font-size: 0.7rem; width: 5%"> Temperatura</td>
        <td style="font-size: 0.7rem; border-bottom: 1px solid; width: 8%">{{ $order->medical->temperature }} °C </td>
        <td style="font-size: 0.7rem; width: 6%"></td>
        <td style="font-size: 0.7rem; width: 10%"></td>
      </tr>

      <tr>
        <td style="border:solid 1px; font-size:0.6rem; text-transform:uppercase;" colspan="8"><strong>indicaciones: </strong>{{ $order->medical->indications }}</td>
      </tr>

      <tr>
        <td colspan="4" style="font-size: 0.7rem; text-align: center;">
          <br>
          <br>
          __________________________
          <br>
          Firma y Sellos de <br>Médico que Inicia HD
        </td>
        <td colspan="4" style="font-size: 0.7rem; text-align: center">
          <br>
          <br>
          __________________________
          <br>
          Firma y Sellos de <br> Médico que Finaliza HD
        </td>
      </tr>

    </table>

    <table width="100%">
      <tr>
        <td style="font-size: 0.8rem; color: white; background-color: black; text-align: center;" colspan="8"><strong>EVOLUCIÓN DE ENFERMERÍA</strong></td>
      </tr>

      <tr>
        <td style="font-size: 0.6rem; width:8%">PA Inicial:</td>
        <td style="font-size: 0.6rem; border:1px solid;">{{ $order->nurse->start_pa }}</td>
        <td style="font-size: 0.6rem; width:18%" colspan="4">Tipo de Acceso vascular:</td>
        <td style="font-size: 0.6rem; width:10%">N° de Maq:</td>
        <td style="font-size: 0.6rem; border:1px solid">{{ $order->nurse->machine }}</td>
      </tr>

      <tr>
        <td style="font-size: 0.6rem; width:8%">PA Final:</td>
        <td style="font-size: 0.6rem; border:1px solid;">{{ $order->nurse->end_pa }}</td>
        @if ($order->nurse->access1 === 'FAV' || $order->nurse->access2 === 'FAV')
          <td style="font-size: 0.6rem;" colspan="2">FAV (X)</td>
        @else
          <td style="font-size: 0.6rem;" colspan="2">FAV ( )</td>
        @endif

        @if ($order->nurse->access1 === 'CVCLP' || $order->nurse->access2 === 'CVCLP')
          <td style="font-size: 0.6rem;" colspan="2">CVCLP (X)</td>
        @else
          <td style="font-size: 0.6rem;" colspan="2">CVCLP ( )</td>
        @endif

        <td style="font-size: 0.6rem">Puesto:</td>
        <td style="font-size: 0.6rem; border:1px solid">{{ $order->nurse->position }}</td>
      </tr>

      <tr>
        <td style="font-size: 0.6rem; width:8%">Peso Inicial:</td>
        <td style="font-size: 0.6rem; border:1px solid;">{{ $order->nurse->start_weight }}</td>
        @if ($order->nurse->access2 === 'CVCT' || $order->nurse->access1 === 'CVCT')
          <td style="font-size: 0.6rem;" colspan="2">CVCT (X)</td>
        @else
          <td style="font-size: 0.6rem;" colspan="2">CVCT ( )</td>
        @endif

        @if ($order->nurse->access2 === 'INJ' || $order->nurse->access1 === 'INJ' )
          <td style="font-size: 0.6rem;" colspan="2">INJERTO (X)</td>
        @else
          <td style="font-size: 0.6rem;" colspan="2">INJERTO ( )</td>
        @endif
        <td style="font-size: 0.6rem">Marca/Mod:</td>
        <td style="font-size: 0.6rem; border:1px solid">{{ $order->nurse->brand_model }}</td>
      </tr>
      <tr>
        <td style="font-size: 0.6rem; width:8%">Peso Final: </td>
        <td style="font-size: 0.6rem; border:1px solid;">{{ $order->nurse->end_weight }}</td>
      </tr>
    </table>

    <table width="100%" width="100%" style="border:1px solid; border-collapse: collapse;">
      
      <tr>
        <td style="font-size: 0.6rem; border-bottom:1px solid; height:25px !important">
          OBSERVACIÓN INICIAL: {{ $order->nurse->start_observation }}
        </td>
      </tr>

      <tr>
        <td style="font-size: 0.6rem; border-bottom:1px solid; height:25px !important " ><strong style="font-size:0.8rem;">S.-</strong>  {{ $order->nurse->s }}</td>
      </tr>

      <tr>
        <td style="font-size: 0.6rem; border-bottom:1px solid; height:25px !important " ><strong style="font-size:0.8rem">O.-</strong> {{ $order->nurse->o }}</td>
      </tr>

      <tr>
        <td style="font-size: 0.6rem; border-bottom:1px solid; height:25px !important " ><strong style="font-size:0.8rem">A.-</strong> {{ $order->nurse->a }}</td>
      </tr>

      <tr>
        <td style="font-size: 0.6rem; border-bottom:1px solid; height:25px !important " ><strong style="font-size:0.8rem">P.-</strong> {{ $order->nurse->p }}</td>
      </tr>
    </table>

    <table width="100%" style="border-collapse:collapse;text-align:center; margin-top:0px" border="1px;">

      <tr>
        <td style="font-size: 0.8rem; color: white; background-color: black; text-align: left;" colspan="9"><strong>I. EVOLUCIÓN DE ENFERMERÍA</strong></td>
      </tr>

      <tr style="font-size:0.7rem;">
        <th>HR</th>
        <th>P.A.</th>
        <th>Px</th>
        <th>QB</th>
        <th>CND</th>
        <th>RA</th>
        <th>RV</th>
        <th>PTM</th>
        <th>OBSERVACIONES</th>
      </tr>
      <tr style="font-size:0.7rem;">
        <td height="14">{{ $order->nurse->hr }}</td>
        <td height="14">{{ $order->nurse->pa }}</td>
        <td height="14">{{ $order->nurse->px }}</td>
        <td height="14">{{ $order->nurse->qb }}</td>
        <td height="14">{{ $order->nurse->cnd }}</td>
        <td height="14">{{ $order->nurse->ra }}</td>
        <td height="14">{{ $order->nurse->rv }}</td>
        <td height="14">{{ $order->nurse->ptm }}</td>
        <td height="14" style="font-size:0.6rem !important">{{ $order->nurse->obs }}</td>
      </tr>
    
      <tr style="font-size:0.7rem;">
        <td height="14">{{ $order->nurse->hr2 }}</td>
        <td height="14">{{ $order->nurse->pa2 }}</td>
        <td height="14">{{ $order->nurse->px2 }}</td>
        <td height="14">{{ $order->nurse->qb2 }}</td>
        <td height="14">{{ $order->nurse->cnd2 }}</td>
        <td height="14">{{ $order->nurse->ra2 }}</td>
        <td height="14">{{ $order->nurse->rv2 }}</td>
        <td height="14">{{ $order->nurse->ptm2 }}</td>
        <td height="14" style="font-size:0.6rem !important">{{ $order->nurse->obs2 }}</td>
      </tr>
    
      <tr style="font-size:0.7rem;">
        <td height="14">{{ $order->nurse->hr3 }}</td>
        <td height="14">{{ $order->nurse->pa3 }}</td>
        <td height="14">{{ $order->nurse->px3 }}</td>
        <td height="14">{{ $order->nurse->qb3 }}</td>
        <td height="14">{{ $order->nurse->cnd3 }}</td>
        <td height="14">{{ $order->nurse->ra3 }}</td>
        <td height="14">{{ $order->nurse->rv3 }}</td>
        <td height="14">{{ $order->nurse->ptm3 }}</td>
        <td height="14" style="font-size:0.6rem !important">{{ $order->nurse->obs3 }}</td>
      </tr>
    
      <tr style="font-size:0.7rem;">
        <td height="14">{{ $order->nurse->hr4 }}</td>
        <td height="14">{{ $order->nurse->pa4 }}</td>
        <td height="14">{{ $order->nurse->px4 }}</td>
        <td height="14">{{ $order->nurse->qb4 }}</td>
        <td height="14">{{ $order->nurse->cnd4 }}</td>
        <td height="14">{{ $order->nurse->ra4 }}</td>
        <td height="14">{{ $order->nurse->rv4 }}</td>
        <td height="14">{{ $order->nurse->ptm4 }}</td>
        <td height="14" style="font-size:0.6rem !important">{{ $order->nurse->obs4 }}</td>
      </tr>
    
      <tr style="font-size:0.7rem;">
        <td height="14">{{ $order->nurse->hr5 }}</td>
        <td height="14">{{ $order->nurse->pa5 }}</td>
        <td height="14">{{ $order->nurse->px5 }}</td>
        <td height="14">{{ $order->nurse->qb5 }}</td>
        <td height="14">{{ $order->nurse->cnd5 }}</td>
        <td height="14">{{ $order->nurse->ra5 }}</td>
        <td height="14">{{ $order->nurse->rv5 }}</td>
        <td height="14">{{ $order->nurse->ptm5 }}</td>
        <td height="14" style="font-size:0.6rem !important">{{ $order->nurse->obs5 }}</td>
      </tr>
    
      <tr style="font-size:0.7rem;">
        <td height="14">{{ $order->nurse->hr6 }}</td>
        <td height="14">{{ $order->nurse->pa6 }}</td>
        <td height="14">{{ $order->nurse->px6 }}</td>
        <td height="14">{{ $order->nurse->qb6 }}</td>
        <td height="14">{{ $order->nurse->cnd6 }}</td>
        <td height="14">{{ $order->nurse->ra6 }}</td>
        <td height="14">{{ $order->nurse->rv6 }}</td>
        <td height="14">{{ $order->nurse->ptm6 }}</td>
        <td height="14" style="font-size:0.6rem !important">{{ $order->nurse->obs6 }}</td>
      </tr>
    
      <tr style="font-size:0.7rem;">
        <td height="14">{{ $order->nurse->hr7 }}</td>
        <td height="14">{{ $order->nurse->pa7 }}</td>
        <td height="14">{{ $order->nurse->px7 }}</td>
        <td height="14">{{ $order->nurse->qb7 }}</td>
        <td height="14">{{ $order->nurse->cnd7 }}</td>
        <td height="14">{{ $order->nurse->ra7 }}</td>
        <td height="14">{{ $order->nurse->rv7 }}</td>
        <td height="14">{{ $order->nurse->ptm7 }}</td>
        <td height="14" style="font-size:0.6rem !important">{{ $order->nurse->obs7 }}</td>
      </tr>
    
      <tr style="font-size:0.7rem;">
        <td height="14">{{ $order->nurse->hr8 }}</td>
        <td height="14">{{ $order->nurse->pa8 }}</td>
        <td height="14">{{ $order->nurse->px8 }}</td>
        <td height="14">{{ $order->nurse->qb8 }}</td>
        <td height="14">{{ $order->nurse->cnd8 }}</td>
        <td height="14">{{ $order->nurse->ra8 }}</td>
        <td height="14">{{ $order->nurse->rv8 }}</td>
        <td height="14">{{ $order->nurse->ptm8 }}</td>
        <td height="14" style="font-size:0.6rem !important">{{ $order->nurse->obs8 }}</td>
      </tr>
    
      <tr>
        <td colspan="9" style="font-size:0.7rem; text-align: left"><strong>E. OBSERVACION FINAL:</strong> {{ $order->nurse->end_observation }}</td>
      </tr>

      <tr>
        <td colspan="9" style="font-size:0.7rem; text-align: left"><strong>Aspecto del dializador:</strong> {{ $order->nurse->aspect_dializer}}</td>
      </tr>
    </table>

    <p style="font-size:0.5rem; margin-top:0px">(*)El número de maquina asignado debe coincidir con el número de serie del equipo.</p>

    <table width="100%" style="border: 0px solid black; margin:-20px 0px auto;">
      <tr>
        <td style="font-size:0.7rem"><strong>ADMINSTRACION: DE MEDICAMENTOS</strong></td>
        <td style="font-size:0.8rem">EPO: <span style="border:1px solid #000; padding:16px">{{ $order->nurse->epo }} </span></td>
        <td style="font-size:0.8rem">HIERRO: <span style="border:1px solid #000; padding:16px">{{ $order->nurse->iron }}</span> </td>
        <td style="font-size:0.8rem">VIT B12: <span style="border:1px solid #000; padding:16px">{{ $order->nurse->vitb12 }} </span> </td>
      </tr>
    </table>

    <table width="100%" style="text-align:center; margin-bottom:0px">
      <tr>
        <td style="font-size: 0.6rem">ENFERMERA(O) QUE INICIA LA DIALISIS</td>
        <td style="font-size: 0.6rem">ENFERMERA(O) QUE FINALIZA LA DIALISIS</td>
      </tr>  
    
    </table>