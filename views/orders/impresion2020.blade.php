<div>
    <table style="margin-top: -100px; width: 100%">

        <tr>
            <td colspan="10" style="font-size: 0.7rem; text-align: center">
                <h4 style="margin-top: 90px">FICHA DE PRESCRIPCION Y EVOLUCIÓN DE LA SESIÓN DE HEMODIALISIS</h4>
            </td>
        </tr>

    </table>

        <table style="margin-top:0px;">
          <tr>

            <td colspan="6" style="font-size: 0.6rem; text-align: right;"><strong>Apellidos y Nombres: </strong></td>
            <td width="586px" style="border-bottom: solid 1px; font-size: 0.6rem; text-transform:uppercase;">{{ $order->patient->name }}</td>

          </tr>
        </table>

        <table style="margin-top:0px;">
            <tr>

                <td colspan="5" style="font-size: 0.7rem"><strong>N° de afiliacion a aseguradora: </strong></td>
                <td width="250px" style="border-bottom: solid 1px; font-size: 0.6rem; text-transform:uppercase;">{{ $order->patient->code }}</td>

                <td style="width: 6%; font-size: 0.6rem"><strong>N° de historia clinica: </strong></td>
                <td style="border:solid 1px; text-align: center; font-size: 0.6rem;" width="50px">{{ $order->patient->dni }}</td>

                <td style="font-size: 0.6rem"><strong>Fecha: </strong></td>
                <td style="border: solid 1px; padding: 2px; font-size: 0.7rem; text-align: center" width="76px">{{ $order->date_order }}</td>

            </tr>
        </table>

        <table style="margin-top: 0px; font-size: 0.8rem;" width="100%">
          <tr>
            <td style="width: 2%; font-size:0.6rem;"><strong>Frecuencia:</strong></td>
            <td style="border:solid 1px; width: 8%; text-align: center; font-size:0.6rem;">{{ $order->nurse->frequency }} ( {{ $order->nurse->others }} )<</td>

            <td style="width: 1%; font-size:0.6rem;"><strong>Turno: </strong></td>
            <td style="border:solid 1px;width: 3%; text-align: center; font-size:0.6rem;">{{ $order->shift->name}}</td>

            <td style="width: 3%; font-size:0.6rem; text-align: center"><strong>N° de Sesión: </strong></td>
            <td style="border:solid 1px; width: 2%; text-align: center; font-size:0.6rem;">{{ $order->nurse->nhd }}</td>

              <td style="width: 6%; font-size:0.6rem; text-align: center"><strong>Atencion en condiciones COVID 19: </strong></td>
              <td style="border:solid 1px; width: 2%; text-align: center; font-size:0.6rem;">{{ $order->covid }}</td>

          </tr>
        </table>

        <table width="100%" style="border:2px solid; border-collapse: collapse;">
          <tr>
            <td style="font-size: 0.6rem; color: white; background-color: black; text-align: left;" colspan="2"><strong>1. EVALUACIÓN MÉDICA </strong></td>
            <td style="color: white; background-color: black; text-align: center;font-size: 0.7rem;">Hora de evaluacion inicial: {{ $order->medical->start_hour }}</td>
          </tr>


          <table width="100%" style="border:2px solid; border-collapse: collapse; margin-top: -3px;">

            <tr>
              <td style="font-size: 0.5rem; width: 95%; text-transform:uppercase;" colspan="12">PROBLEMAS CLINICOS: {{ $order->medical->clinical_trouble }} </td>
            </tr>

            <tr>
              <td style="font-size: 0.5rem; width: 10%; text-transform:uppercase;" colspan="12">Signos - Sintomas: {{ $order->medical->signal }}</td>
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
                  <td style="font-size: 0.6rem;" colspan="2">EXAMEN FISICO</td>
                  <td style="font-size: 0.5rem;" colspan="2"><strong> PA:</strong> {{ $order->medical->start_pa }} mmHg</td>
                  <td style="font-size: 0.5rem;" colspan="2"><strong>FC:</strong> {{ $order->medical->fc }} x'</td>
                  <td style="font-size: 0.5rem;" colspan="2"><strong>SATO2:</strong> {{ $order->medical->so2 }} %</td>
                  <td style="font-size: 0.5rem;" colspan="2"><strong>FIO2:</strong> {{ $order->medical->fio }}</td>
                  <td style="font-size: 0.5rem;" colspan="2"><strong>T°:</strong> {{ $order->medical->temp }} °C</td>
              </tr>

                <tr>
                    <td style="font-size: 0.6rem; margin-bottom: 1px; height: 20px" colspan="12">{{ $order->medical->evaluation }}</td>
                </tr>

                <tr>
                    <td style="font-size: 0.6rem; margin-bottom: 1px; height: 20px" colspan="12">INDICACIONES: {{ $order->medical->indications }}</td>
                </tr>
            </table>
          </tr>
        </table>

        <table width="100%" style="border:2px solid; border-collapse: collapse; border-top: none;">
          <tr>
            <td style="font-size: 0.6rem; margin-bottom: 1px; font-weight: bold" colspan="8">PRESCRIPCIÓN DEL TRATAMIENTO DE HEMODIALISIS</td>
          </tr>
          <tr>
            <td style="font-size: 0.6rem; width: 12%">Horas de  hemodialisis:</td>
            <td style="font-size: 0.6rem; border-bottom: 1px solid; width: 10%; text-align: center">{{ $order->medical->hour_hd }} HRSS</td>
            <td style="font-size: 0.6rem; width: 5%">Qb:</td>
            <td style="font-size: 0.6rem; border-bottom: 1px solid; width: 5%; text-align: center">{{ $order->medical->qb }}</td>
            <td style="font-size: 0.6rem; width: 5%">Conductividad:</td>
            <td style="font-size: 0.6rem; border-bottom: 1px solid; width: 8%; text-align: center">{{ $order->medical->cnd }} </td>
            <td style="font-size: 0.6rem; width: 10%">Dosis de heparina:</td>
            <td style="font-size: 0.6rem; border-bottom: 1px solid; width: 10%; text-align: center">{{ $order->medical->heparin }} UI</td>
          </tr>
          <tr>
            <td style="font-size: 0.6rem; width: 12%">Qd:</td>
            <td style="font-size: 0.6rem; border-bottom: 1px solid; width: 10%; text-align: center">{{ $order->medical->qd }}</td>
            <td style="font-size: 0.6rem; width: 5%">Na Inicial:</td>
            <td style="font-size: 0.6rem; border-bottom: 1px solid; width: 5%; text-align: center">{{ $order->medical->start_na }} Meq/L</td>
              <td style="font-size: 0.6rem; width: 5%">Peso Seco:</td>
              <td style="font-size: 0.6rem; border-bottom: 1px solid; width: 8%; text-align: center">{{ $order->medical->dry_weight }} Kg</td>
              <td style="font-size: 0.6rem; width: 10%">Bicarbonato:</td>
              <td style="font-size: 0.6rem; border-bottom: 1px solid; width: 10%; text-align: center">{{ $order->medical->bicarbonat }}</td>
          </tr>

          <tr>
                <td style="font-size: 0.6rem; width: 12%">Na Final:</td>
                <td style="font-size: 0.6rem; border-bottom: 1px solid; width: 10%; text-align: center">{{ $order->medical->end_na }} Meq/L</td>
                <td style="font-size: 0.6rem; width: 5%">Peso Inicial:</td>
                <td style="font-size: 0.6rem; border-bottom: 1px solid; width: 5%; text-align: center">{{ $order->medical->start_weight }} Kg</td>
                <td style="font-size: 0.6rem; width: 5%">Perfil de Na:</td>
                <td style="font-size: 0.6rem; border-bottom: 1px solid; width: 8%; text-align: center">{{ $order->medical->profile_na }}</td>
                <td></td>
                <td></td>
          </tr>

          <tr>
            <td style="font-size: 0.6rem; width: 10%"></td>
            <td style="font-size: 0.6rem; width: 10%"></td>
            <td style="font-size: 0.6rem; width: 10%">Ultrafiltrado:</td>
            <td style="font-size: 0.5rem; border-bottom: 1px solid; width: 10%; text-align: center">{{ $order->medical->uf }}</td>
            <td style="font-size: 0.6rem; width: 5%"> Perfil de Uf:</td>
            <td style="font-size: 0.6rem; border-bottom: 1px solid; width: 8%; text-align: center">{{ $order->medical->profile_uf }} </td>
            <td style="font-size: 0.6rem; width: 6%"></td>
            <td style="font-size: 0.6rem; width: 10%"></td>
          </tr>

            <tr style="margin-top: 10px">
                <td style="font-size: 0.6rem; margin-top: 1px; font-weight: bold" colspan="8">PRESCRIPCIÓN PARA DIALIZADOR</td>
            </tr>

            <tr>

                <td colspan="2" style="font-size: 0.6rem; width: 10%; text-align: right">Area de dializador:</td>
                <td style="font-size: 0.5rem; border-bottom: 1px solid; width: 10%; text-align: center">{{ $order->medical->area_filter }}</td>
                <td colspan="2" style="font-size: 0.6rem; width: 5%; text-align: right"> Membrana de dializador:</td>
                <td style="font-size: 0.5rem; border-bottom: 1px solid; width: 8%; text-align: center">{{ $order->medical->membrane }}</td>
                <td></td>
                <td></td>

            </tr>

            <tr>
                @if($order->medical->user_id)
                    <td colspan="4" style="text-align: center">
                        <img src="{{ asset($order->medical->user->image) }}" width="140" height="65" alt="">
                    </td>
                @else
                    <p>AQUI VA SELLO DEL MEDICO</p>
                @endif



                    @if($order->medical->user_id)
                        <td colspan="4" style="text-align: center">
                            <img src="{{ asset($order->medical->user->image) }}" width="140" height="65" alt="">
                        </td>
                    @else
                        <p>AQUI VA SELLO DEL MEDICO</p>
                    @endif
            </tr>

          <tr>
            <td colspan="4" style="font-size: 0.4rem; text-align: center;">

              Médico que Inicia HD
            </td>
            <td colspan="4" style="font-size: 0.4rem; text-align: center">

              Médico que Finaliza HD
            </td>
          </tr>

        </table>

        <table width="100%" style="border:2px solid; border-collapse: collapse;">
        <tr>
            <td style="font-size: 0.6rem; color: white; background-color: black; text-align: left;" colspan="2"><strong>2. EVALUACIÓN FINAL </strong></td>
            <td style="color: white; background-color: black; text-align: center;font-size: 0.5rem;">Hora de evaluacion final: {{ $order->medical->end_hour }}</td>
        </tr>

        <tr>
            <table width="100%" style="border:2px solid; border-collapse: collapse; border-top: none; margin-top: -2px">
                <tr>
                    <td style="font-size: 0.5rem; margin-bottom: 1px; height: 20px" colspan="2">Condicion clinica del paciente al finalizar hemodialisis y otros: {{ $order->medical->end_evaluation }}</td>
                </tr>
            </table>
        </tr>
    </table>

        <table width="100%">
          <tr>
            <td style="font-size: 0.6rem; color: white; background-color: black; text-align: left;" colspan="10"><strong>3. ATENCION DE ENFERMERIA - VALORACION</strong></td>
          </tr>

            <tr>
                <td colspan="10" style="font-size: 0.8rem; border-bottom:1px dotted;" ><strong style="font-size:0.7rem;">S.-</strong>  {{ $order->nurse->s }}</td>
            </tr>

            <tr>
                <td colspan="10" style="font-size: 0.8rem; border-bottom:1px dotted;" ><strong style="font-size:0.7rem">O.-</strong> {{ $order->nurse->o }}</td>
            </tr>

            <tr>
                <td colspan="10" style="font-size: 0.8rem; border-bottom:1px dotted;" ><strong style="font-size:0.7rem">A.-</strong> {{ $order->nurse->a }}</td>
            </tr>

            <tr>
                <td colspan="10" style="font-size: 0.8rem; border-bottom:1px dotted;" ><strong style="font-size:0.7rem">P.-</strong> {{ $order->nurse->p }}</td>
            </tr>

          <tr>
            <td style="font-size: 0.6rem; width:3%; text-align: center">Peso Inicial:</td>
            <td style="font-size: 0.6rem; border:1px solid; text-align: center" width="2%">{{ $order->medical->start_weight }}</td>

            <td style="font-size: 0.6rem; width:3%; text-align: center">PA Inicial:</td>
            <td style="font-size: 0.6rem; border:1px solid; text-align: center" width="3%">{{ $order->nurse->start_pa }}</td>

            <td style="font-size: 0.6rem; width:3%; text-align: center">N° de Maq:</td>
            <td style="font-size: 0.6rem; border:1px solid; text-align: center" width="2%">{{ $order->nurse->machine }}</td>

            <td style="font-size: 0.6rem; width:7%; text-align: center">Marca/Modelo de maquina:</td>
            <td style="font-size: 0.6rem; border:1px solid; text-align: center" width="4%">{{ $order->nurse->brand_model }}</td>

            <td style="font-size: 0.6rem; width:4%; text-align: center">N° de puesto:</td>
            <td style="font-size: 0.6rem; border:1px solid; text-align: center" width="3%">{{ $order->nurse->position }}</td>
          </tr>
        </table>

        <table width="100%">
            <tr>
                <td style="font-size: 0.6rem; width:3%; text-align: center">Area/membrana de filtro:</td>
                <td style="font-size: 0.5rem; border:1px solid; text-align: center" width="1%">{{ $order->nurse->filter }}</td>

                <td style="font-size: 0.6rem; width:3%; text-align: center">Ultrafiltrado programado:</td>
                <td style="font-size: 0.5rem; border:1px solid; text-align: center" width="4%">{{ $order->medical->uf }} cc</td>

                <td style="font-size: 0.6rem; width:2%; text-align: center"> Acc. Arterial:</td>
                <td style="font-size: 0.5rem; border:1px solid; text-align: center" width="2%">{{ $order->nurse->access_arterial }}</td>

                <td style="font-size: 0.6rem; width:2%; text-align: center">Acc. Venoso:</td>
                <td style="font-size: 0.5rem; border:1px solid; text-align: center" width="2%">{{ $order->nurse->access_venoso }}</td>
            </tr>
        </table>


        <table width="100%" style="border-collapse:collapse;text-align:center; margin-top:0px" border="1px;">

          <tr>
            <td style="font-size: 0.6rem; color: white; background-color: black; text-align: left;" colspan="10"><strong>I. EVOLUCIÓN DEL TRATAMIENTO DE HEMODIALISIS</strong></td>
          </tr>

          <tr style="font-size:0.6rem;">
            <th>HR</th>
            <th>P.A.</th>
            <th>Px</th>
            <th>QB</th>
            <th>CND</th>
            <th>RA</th>
            <th>RV</th>
            <th>PTM</th>
            <th>SOL/HEMODERIVADOS</th>
            <th>OBSERVACIONES</th>
          </tr>
          <tr style="font-size:0.6rem;">
            <td height="14">{{ $order->nurse->hr }}</td>
            <td height="14">{{ $order->nurse->pa }}</td>
            <td height="14">{{ $order->nurse->fc1 }}</td>
            <td height="14">{{ $order->nurse->qb }}</td>
            <td height="14">{{ $order->nurse->cnd }}</td>
            <td height="14">{{ $order->nurse->ra }}</td>
            <td height="14">{{ $order->nurse->rv }}</td>
            <td height="14">{{ $order->nurse->ptm }}</td>
            <td height="6" style="font-size:0.6rem !important">{{ $order->nurse->sol_hemodev }}</td>
            <td height="14" style="font-size:0.7rem !important">{{ $order->nurse->obs }}</td>
          </tr>

          <tr style="font-size:0.6rem;">
            <td height="14">{{ $order->nurse->hr2 }}</td>
            <td height="14">{{ $order->nurse->pa2 }}</td>
            <td height="14">{{ $order->nurse->fc2 }}</td>
            <td height="14">{{ $order->nurse->qb2 }}</td>
            <td height="14">{{ $order->nurse->cnd2 }}</td>
            <td height="14">{{ $order->nurse->ra2 }}</td>
            <td height="14">{{ $order->nurse->rv2 }}</td>
            <td height="14">{{ $order->nurse->ptm2 }}</td>
            <td height="6" style="font-size:0.6rem !important">{{ $order->nurse->sol_hemodev2 }}</td>
            <td height="14" style="font-size:0.7rem !important">{{ $order->nurse->obs2 }}</td>
          </tr>

          <tr style="font-size:0.6rem;">
            <td height="14">{{ $order->nurse->hr3 }}</td>
            <td height="14">{{ $order->nurse->pa3 }}</td>
            <td height="14">{{ $order->nurse->fc3 }}</td>
            <td height="14">{{ $order->nurse->qb3 }}</td>
            <td height="14">{{ $order->nurse->cnd3 }}</td>
            <td height="14">{{ $order->nurse->ra3 }}</td>
            <td height="14">{{ $order->nurse->rv3 }}</td>
            <td height="14">{{ $order->nurse->ptm3 }}</td>
              <td height="6" style="font-size:0.6rem !important">{{ $order->nurse->sol_hemodev3 }}</td>
            <td height="14" style="font-size:0.7rem !important">{{ $order->nurse->obs3 }}</td>
          </tr>

          <tr style="font-size:0.6rem;">
            <td height="14">{{ $order->nurse->hr4 }}</td>
            <td height="14">{{ $order->nurse->pa4 }}</td>
            <td height="14">{{ $order->nurse->fc4 }}</td>
            <td height="14">{{ $order->nurse->qb4 }}</td>
            <td height="14">{{ $order->nurse->cnd4 }}</td>
            <td height="14">{{ $order->nurse->ra4 }}</td>
            <td height="14">{{ $order->nurse->rv4 }}</td>
            <td height="14">{{ $order->nurse->ptm4 }}</td>
              <td height="6" style="font-size:0.6rem !important">{{ $order->nurse->sol_hemodev4 }}</td>
            <td height="14" style="font-size:0.7rem !important">{{ $order->nurse->obs4 }}</td>
          </tr>

          <tr style="font-size:0.6rem;">
            <td height="14">{{ $order->nurse->hr5 }}</td>
            <td height="14">{{ $order->nurse->pa5 }}</td>
            <td height="14">{{ $order->nurse->fc5 }}</td>
            <td height="14">{{ $order->nurse->qb5 }}</td>
            <td height="14">{{ $order->nurse->cnd5 }}</td>
            <td height="14">{{ $order->nurse->ra5 }}</td>
            <td height="14">{{ $order->nurse->rv5 }}</td>
            <td height="14">{{ $order->nurse->ptm5 }}</td>
              <td height="6" style="font-size:0.6rem !important">{{ $order->nurse->sol_hemodev5 }}</td>
            <td height="14" style="font-size:0.7rem !important">{{ $order->nurse->obs5 }}</td>
          </tr>

          <tr style="font-size:0.6rem;">
            <td height="14">{{ $order->nurse->hr6 }}</td>
            <td height="14">{{ $order->nurse->pa6 }}</td>
            <td height="14">{{ $order->nurse->fc6 }}</td>
            <td height="14">{{ $order->nurse->qb6 }}</td>
            <td height="14">{{ $order->nurse->cnd6 }}</td>
            <td height="14">{{ $order->nurse->ra6 }}</td>
            <td height="14">{{ $order->nurse->rv6 }}</td>
            <td height="14">{{ $order->nurse->ptm6 }}</td>
              <td height="6" style="font-size:0.6rem !important">{{ $order->nurse->sol_hemodev6 }}</td>
            <td height="14" style="font-size:0.7rem !important">{{ $order->nurse->obs6 }}</td>
          </tr>

          <tr style="font-size:0.6rem;">
            <td height="14">{{ $order->nurse->hr7 }}</td>
            <td height="14">{{ $order->nurse->pa7 }}</td>
            <td height="14">{{ $order->nurse->fc7 }}</td>
            <td height="14">{{ $order->nurse->qb7 }}</td>
            <td height="14">{{ $order->nurse->cnd7 }}</td>
            <td height="14">{{ $order->nurse->ra7 }}</td>
            <td height="14">{{ $order->nurse->rv7 }}</td>
            <td height="14">{{ $order->nurse->ptm7 }}</td>
              <td height="6" style="font-size:0.6rem !important">{{ $order->nurse->sol_hemodev7 }}</td>
            <td height="14" style="font-size:0.7rem !important">{{ $order->nurse->obs7 }}</td>
          </tr>

            <tr style="font-size:0.6rem;">
                <td height="14">{{ $order->nurse->hr8 }}</td>
                <td height="14">{{ $order->nurse->pa8 }}</td>
                <td height="14">{{ $order->nurse->fc8 }}</td>
                <td height="14">{{ $order->nurse->qb8 }}</td>
                <td height="14">{{ $order->nurse->cnd8 }}</td>
                <td height="14">{{ $order->nurse->ra8 }}</td>
                <td height="14">{{ $order->nurse->rv8 }}</td>
                <td height="14">{{ $order->nurse->ptm8 }}</td>
                <td height="6" style="font-size:0.6rem !important">{{ $order->nurse->sol_hemodev8 }}</td>
                <td height="14" style="font-size:0.7rem !important">{{ $order->nurse->obs8 }}</td>
            </tr>

            <tr style="border: none">
                <td colspan="8" style="font-size:0.5rem !important; text-align: left">P.A. Final: {{ $order->nurse->end_pa }}</td>
                <td colspan="2" style="font-size:0.5rem !important; text-align: left">Peso Final: {{ $order->nurse->end_weight }} Kg</td>
            </tr>

          <tr>
            <td colspan="10" style="font-size:0.7rem; text-align: left"><strong>E. OBSERVACION FINAL:</strong> {{ $order->nurse->end_observation }}</td>
          </tr>

          <tr>
            <td colspan="10" style="font-size:0.6rem; text-align: left"><strong>Aspecto de filtro:</strong> {{ $order->nurse->aspect_dializer}}</td>
          </tr>
        </table>

        <p style="font-size:0.5rem; margin-top:0px;margin-bottom: 10px">(*)El número de maquina asignado debe coincidir con el número de serie del equipo.</p>
        <br>

        <table width="100%" style="border: 0px solid black; margin:-28px 0px auto;">
          <tr>
            <th colspan="3" style="font-size: 0.6rem; color: white; background-color: black; text-align: left;"><strong>ADMINISTRACION: DE MEDICAMENTOS</strong></th>
              <th colspan="4"></th>
              <th colspan="3"></th>
          </tr>

            <tr>
                <td style="font-size:0.6rem;">Hierro 20 mg Fe/mL INY 5 mL: <span style="border:1px solid #000; padding:10px">{{ $order->medical->iron }} </span></td>
                <td style="font-size:0.6rem;">Epoetina alfa 2000 Ul/mL: <span style="border:1px solid #000; padding:10px">{{ $order->medical->epo }} </span></td>
                <td style="font-size:0.6rem;">Epoetina alfa 4000 Ul/mL: <span style="border:1px solid #000; padding:10px">{{ $order->medical->epo4000 }} </span></td>
            </tr>

            <tr>
                <td style="font-size:0.6rem">Hidroxicobalamina 1mg/mL INY 1mL: <span style="border:1px solid #000; padding:10px">{{ $order->medical->vitb12 }} </span></td>
                <td style="font-size:0.6rem">Calcitriol 1 mcg/mL INY: <span style="border:1px solid #000; padding:10px">{{ $order->medical->calci }} </span></td>
                <td style="font-size:0.6rem">Otros: <span style="border:1px solid #000; padding:10px">{{ $order->nurse->others_med }} </span></td>
            </tr>
        </table>



        <table width="100%" style="text-align:center; margin-top:0px">
      <tr>
        <td style="font-size: 0.4rem">ENFERMERA(O) QUE INICIA LA DIALISIS</td>

        <td style="font-size: 0.4rem">ENFERMERA(O) QUE FINALIZA LA DIALISIS</td>

      </tr>

            <tr>
                @if($order->nurse->user_id)
                    <td>
                        <img src="{{ asset($order->nurse->user->image) }}" width="145" height="65" alt="">
                    </td>
                @else
                    <td>aqui va firma del licenciado</td>
                @endif


                    @if($order->nurse->user_id)
                        <td>
                            <img src="{{ asset($order->nurse->user->image) }}" width="145" height="65" alt="">
                        </td>
                    @else
                        <td>aqui va firma del licenciado</td>
                    @endif
            </tr>

    </table>
</div>
