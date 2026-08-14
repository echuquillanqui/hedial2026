
<table width="100%" border="2px" style="border-collapse:collapse; margin-top: -41px -auto">
    <tr>
      <th style="text-align: center"><img src="{{ asset('img/brand/logo_hysdialisis.png') }}" alt="LOGO" width="80px"></th>
      <th width="39%" style="text-align:center;"><h3>HISTORIA CLINICA DE CONTROL</h3></th>
      <th width="45%"><p style="font-size:12px; text-align: center">STICKER DE FILTRO A USARSE</p></th>
    </tr>
</table>

<table width="100%" style="margin:-22px auto">
    <tr>
      <th width="100%" style="text-align:right;"><p style="font-size:12px">HCL N°: {{ $order->nurse->hcl }}</p></th>
    </tr>
</table>

<table width="100%" style="margin:-12px auto">
    <tr>
      <td style="text-align:left;font-size:0.8rem" colspan="3"><strong>PACIENTE: </strong>{{ $order->patient->name }}</p> </td><p>
    </tr>
</table>
<br>
<table width="100%" style="margin:-12px auto">
  <tr>
    <td style="font-size:0.8rem"><strong>FECHA: </strong>{{ $date }}</td>
    <td style="font-size:0.8rem"><strong>Frecuencia HD: </strong>{{ $order->nurse->frequency }}</td>
    <td style="font-size:0.8rem"><strong>TURNO: </strong>{{ $order->shift->name}}</td>
  </tr>
</table>
<table width="100%">
  <tr>
    <th></th>
    <th></th>
  </tr>
  <tr >
    <td style="font-size:0.8rem"><strong>N° de HD: </strong>{{ $order->nurse->nhd }}</td>
    <td colspan="2" style="font-size:0.9rem"><strong>Otros: </strong>{{ $order->nurse->others }}</td>
    <td style="font-size:0.8rem"></td>
  </tr>
</table>

<span style="font-size:0.9rem"><strong>I.- PARTE MÉDICO</strong></span>
<p style="margin:0px auto; font-size:12px">EVALUACIÓN MÉDICA</p >
<br>

<table width="100%" style="border: 1px solid black;">
  <tr>
    <td style="font-size:0.8rem">PROBLEMA CLÍNICO: {{ $order->medical->clinical_trouble }}</td>
    <td style="font-size:0.8rem">P.A: {{ $order->medical->start_pa }}</td>
  </tr>
  <tr>
    <td> </td>
    <td style="font-size:0.8rem">F.C: {{ $order->medical->fc }}</td>
  </tr>
</table>

<table width="100%" style="border: 1px solid black; margin:-1px 0px auto">
  <tr>
    <td tr style="font-size:0.7rem"><strong>EVALUACIÓN: </strong> Signos y Síntomas: {{ $order->medical->evaluation }}</td>
  </tr>
</table>

<table width="100%" style="border: 1px solid black; margin:-1px 0px auto;">
  <tr style="font-size:0.8rem">
    <td><strong>Hrs. HD: </strong>{{ $order->medical->hour_hd }} h</td><strong></strong>
    <td><strong>QB:</strong> {{ $order->medical->qb }}</td>
    <td><strong>CND: </strong>{{ $order->medical->cnd }}</td>
    <td><strong>Área filtro: </strong>{{ $order->medical->area_filter }} m2</td>
  </tr>
  <tr style="font-size:0.8rem">
    <td><strong>Heparina: </strong>{{ $order->medical->heparin }}</td>
    <td><strong>QD: </strong>{{ $order->medical->qd }}</td>
    <td><strong>Na inicial: </strong>{{ $order->medical->start_na }}</td>
    <td><strong>Membrana: </strong>{{ $order->medical->membrane }}</td>
  </tr>
  <tr style="font-size:0.8rem">
    <td><strong>Peso Seco: </strong>{{ $order->medical->dry_weight }}</td>
    <td><strong>Baño: </strong>{{ $order->medical->bathroom }}</td>
    <td><strong>Na Final: </strong>{{ $order->medical->end_na }}</td>
    <td><strong>Condic. Serolog: </strong>{{ $order->medical->serological }}</td>
  </tr>
  <tr style="font-size:0.8rem">
    <td><strong>UF: </strong>{{ $order->medical->uf }}</td>
    <td><strong>T: </strong>{{ $order->medical->temperature }} °C</td>
  </tr>

  <tr style="font-size:0.8rem">
    <td></td>
    <td></td>
    <td style="text-align:center;font-size:0.8rem">
      <br>
      <br>
      <br>
      __________________________
      <br>
      MÉDICO NEFRÓLOGO
      <br>
      FIRMA/SELLO
    </td>
    <td></td>
  </tr>
</table>

<table width="100%" style="border: 1px solid black; margin:-1px 0px auto">
  <tr>
    <td style="font-size:0.7rem"><strong>INDICACIONES DURANTE LA SESIÓN: </strong> {{ $order->medical->indications }}</td>
  </tr>

</table>

<span style="font-size:0.9rem"><strong>II.- PARTE ENFERMERIA</strong></span>

<table width="100%" >
  <tr style="font-size:0.8rem">
    <td><strong>P.A. Inicial: </strong>{{ $order->nurse->start_pa }} mmhg</td>
    <td><strong>N° de maq: </strong>{{ $order->nurse->machine }}</td>
    <td><strong>Filtro: </strong>{{ $order->nurse->filter }}</td>
  </tr>
  <tr style="font-size:0.8rem">
    <td><strong>P.A. Final: </strong>{{ $order->nurse->end_pa }} mmhg</td>
    <td><strong>Marca / Mod.: </strong>{{ $order->nurse->brand_model }}</td>
    <td><strong>UF: </strong>{{ $order->nurse->uf }}</td>
  </tr>
  <tr style="font-size:0.8rem">
    <td><strong>Peso inicial: </strong>{{ $order->nurse->start_weight }} kg</td>
    <td><strong>Acceso: </strong>{{ $order->nurse->access1 }}</td>
  </tr>
  <tr style="font-size:0.8rem">
    <td><strong>Peso Final: </strong>{{ $order->nurse->end_weight }} kg</td>
    <td><strong>Acceso: </strong>{{ $order->nurse->access2 }}</td>
  </tr>
</table>

<table width="100%" style="border: 1px solid black; margin:-1px 0px auto">
  <tr>
    <td style="font-size:0.7rem"><strong>OBSERVACION INICIAL:</strong> {{ $order->nurse->start_observation }}</td>
  </tr>
</table>

<span style="font-size:0.7rem;">EVOLUCIÓN DEL TRATAMIENTO:</span>

<table width="100%" style="border-collapse:collapse;text-align:center;" border="1px;">
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
  <tr style="font-size:0.8rem;">
    <td height="15">{{ $order->nurse->hr }}</td>
    <td height="15">{{ $order->nurse->pa }}</td>
    <td height="15">{{ $order->nurse->px }}</td>
    <td height="15">{{ $order->nurse->qb }}</td>
    <td height="15">{{ $order->nurse->cnd }}</td>
    <td height="15">{{ $order->nurse->ra }}</td>
    <td height="15">{{ $order->nurse->rv }}</td>
    <td height="15">{{ $order->nurse->ptm }}</td>
    <td height="15">{{ $order->nurse->obs }}</td>
  </tr>

  <tr style="font-size:0.8rem;">
    <td height="15">{{ $order->nurse->hr2 }}</td>
    <td height="15">{{ $order->nurse->pa2 }}</td>
    <td height="15">{{ $order->nurse->px2 }}</td>
    <td height="15">{{ $order->nurse->qb2 }}</td>
    <td height="15">{{ $order->nurse->cnd2 }}</td>
    <td height="15">{{ $order->nurse->ra2 }}</td>
    <td height="15">{{ $order->nurse->rv2 }}</td>
    <td height="15">{{ $order->nurse->ptm2 }}</td>
    <td height="15">{{ $order->nurse->obs2 }}</td>
  </tr>

  <tr style="font-size:0.8rem;">
    <td height="15">{{ $order->nurse->hr3 }}</td>
    <td height="15">{{ $order->nurse->pa3 }}</td>
    <td height="15">{{ $order->nurse->px3 }}</td>
    <td height="15">{{ $order->nurse->qb3 }}</td>
    <td height="15">{{ $order->nurse->cnd3 }}</td>
    <td height="15">{{ $order->nurse->ra3 }}</td>
    <td height="15">{{ $order->nurse->rv3 }}</td>
    <td height="15">{{ $order->nurse->ptm3 }}</td>
    <td height="15">{{ $order->nurse->obs3 }}</td>
  </tr>

  <tr style="font-size:0.8rem;">
    <td height="15">{{ $order->nurse->hr4 }}</td>
    <td height="15">{{ $order->nurse->pa4 }}</td>
    <td height="15">{{ $order->nurse->px4 }}</td>
    <td height="15">{{ $order->nurse->qb4 }}</td>
    <td height="15">{{ $order->nurse->cnd4 }}</td>
    <td height="15">{{ $order->nurse->ra4 }}</td>
    <td height="15">{{ $order->nurse->rv4 }}</td>
    <td height="15">{{ $order->nurse->ptm4 }}</td>
    <td height="15">{{ $order->nurse->obs4 }}</td>
  </tr>

  <tr style="font-size:0.8rem;">
    <td height="15">{{ $order->nurse->hr5 }}</td>
    <td height="15">{{ $order->nurse->pa5 }}</td>
    <td height="15">{{ $order->nurse->px5 }}</td>
    <td height="15">{{ $order->nurse->qb5 }}</td>
    <td height="15">{{ $order->nurse->cnd5 }}</td>
    <td height="15">{{ $order->nurse->ra5 }}</td>
    <td height="15">{{ $order->nurse->rv5 }}</td>
    <td height="15">{{ $order->nurse->ptm5 }}</td>
    <td height="15">{{ $order->nurse->obs5 }}</td>
  </tr>

  <tr style="font-size:0.8rem;">
    <td height="15">{{ $order->nurse->hr6 }}</td>
    <td height="15">{{ $order->nurse->pa6 }}</td>
    <td height="15">{{ $order->nurse->px6 }}</td>
    <td height="15">{{ $order->nurse->qb6 }}</td>
    <td height="15">{{ $order->nurse->cnd6 }}</td>
    <td height="15">{{ $order->nurse->ra6 }}</td>
    <td height="15">{{ $order->nurse->rv6 }}</td>
    <td height="15">{{ $order->nurse->ptm6 }}</td>
    <td height="15">{{ $order->nurse->obs6 }}</td>
  </tr>

  <tr style="font-size:0.8rem;">
    <td height="15">{{ $order->nurse->hr7 }}</td>
    <td height="15">{{ $order->nurse->pa7 }}</td>
    <td height="15">{{ $order->nurse->px7 }}</td>
    <td height="15">{{ $order->nurse->qb7 }}</td>
    <td height="15">{{ $order->nurse->cnd7 }}</td>
    <td height="15">{{ $order->nurse->ra7 }}</td>
    <td height="15">{{ $order->nurse->rv7 }}</td>
    <td height="15">{{ $order->nurse->ptm7 }}</td>
    <td height="15">{{ $order->nurse->obs7 }}</td>
  </tr>

  <tr style="font-size:0.8rem;">
    <td height="15">{{ $order->nurse->hr8 }}</td>
    <td height="15">{{ $order->nurse->pa8 }}</td>
    <td height="15">{{ $order->nurse->px8 }}</td>
    <td height="15">{{ $order->nurse->qb8 }}</td>
    <td height="15">{{ $order->nurse->cnd8 }}</td>
    <td height="15">{{ $order->nurse->ra8 }}</td>
    <td height="15">{{ $order->nurse->rv8 }}</td>
    <td height="15">{{ $order->nurse->ptm8 }}</td>
    <td height="15">{{ $order->nurse->obs8 }}</td>
  </tr>

</table>

<table width="100%" style="border: 1px solid black; margin:-1px 0px auto;">
  <tr>
    <td style="font-size:0.7rem"><strong>OBSERVACION FINAL:</strong></td>
  </tr>
  <tr>
    <td style="font-size:0.7rem;text-align:center;">{{ $order->nurse->end_observation }}</td>
  </tr>
</table>

<table width="100%" style="border: 0px solid black; margin:-1px 0px auto;">
  <tr>
    <td style="font-size:0.7rem"><strong>ADMINSTRACION: DE MEDICAMENTOS</strong></td>
    <td style="font-size:0.8rem">EPO: <span style="border:1px solid #000; padding:16px">{{ $order->nurse->epo }} </span></td>
    <td style="font-size:0.8rem">HIERRO: <span style="border:1px solid #000; padding:16px">{{ $order->nurse->iron }}</span> </td>
    <td style="font-size:0.8rem">VIT B12: <span style="border:1px solid #000; padding:16px">{{ $order->nurse->vitb12 }} </span> </td>
  </tr>
</table>

<table width="100%">
  <tr>
    <td style="font-size:0.6rem">ENFERMERA INICIA HD:</td>
    <td style="font-size:0.6rem">ENFERMERA FINALIZA HD:</td>
  </tr>
</table>
<br>
<br>
<br>
<br>
<br>

<table width="100%" style="text-align:center; margin-bottom: -41px">
  <tr>
    <td style="font-size:0.8rem">Firma / Sello</td>
    <td style="font-size:0.8rem">Firma / Sello</td>
  </tr>
</table>
