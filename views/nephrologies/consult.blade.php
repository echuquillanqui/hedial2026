<div style="font-family: Arial,sans-serif; width: 100%; padding: 5px; box-sizing: border-box; margin: -25px -25px auto; border: 3px solid black;">

    <table style="width: 100%; border: solid 1px black; border-collapse: collapse">
        <tr>
            <th style="text-align: left">
                <figure>
                    <img src="{{ asset('img/brand/logo-medio.png') }}" style="width: 10%">
                </figure>
            </th>

            <th style="text-align: center;">
                <h5 style="font-size: 0.9rem; width: 350px">FORMATO DE CONSULTA NEFROLOGICA</h5>
            </th>

            <th style="text-align: right">
                <figure>
                    <img src="{{ asset('img/brand/logo-medio.png') }}" style="width: 10%">
                </figure>
            </th>
        </tr>
    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:5px;">
        <tr>
            <td style="font-weight: bold; font-size: 0.6rem; width: 20%;">NOMBRES Y APELLIDOS: </td>
            <td style="font-size: 0.6rem; text-align: left; width: 32%">{{ $nephrology->patient->name }}</td>

            <td style="font-weight: bold; font-size: 0.6rem; width: 5%;">DNI: </td>
            <td style="font-size: 0.6rem; width: 8%">{{ $nephrology->patient->dni }}</td>

            <td style="font-weight: bold; font-size: 0.6rem; width: 17%">FECHA DE NACIMIENTO: </td>
            <td style="font-size: 0.6rem; text-align: left; width: 10%;">{{ !$nephrology->patient->date_of_birth ? '-------' : $nephrology->patient->date_of_birth }}</td>

            <td style="font-weight: bold; font-size: 0.5rem; width: 4%">EDAD: </td>
            <td style="font-size: 0.5rem; text-align: left; width: 8%">{{ $nephrology->patient->age }} años</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:5px;">

        <tr>
            <td style="font-weight: bold; font-size: 0.6rem; width: 5%">HORA: </td>
            <td style="font-size: 0.6rem; width: 7%; text-align: left;">{{ $nephrology->timenefro }}</td>

            <td style="font-weight: bold; font-size: 0.6rem; width: 15%">FECHA DE ATENCION: </td>
            <td style="font-size: 0.6rem; width: 8%">{{ $nephrology->date_order }}</td>

            <td style="font-weight: bold; font-size: 0.6rem;">MOTIVO DE CONSULTA: </td>
            <td style="font-size: 0.6rem; text-align: left;">{{ $nephrology->consult }}</td>

            <td style="font-weight: bold; font-size: 0.6rem">TIEMPO DE ENFERMEDAD: </td>
            <td style="font-size: 0.6rem; text-align: left;">{{ $nephrology->time_disease }}</td>

        </tr>
    </table>
    <table style="width: 100%; border-collapse:collapse; margin-top:5px;">

        <tr>
            <td style="font-weight: bold; font-size: 0.6rem; width: 8%">ANAMNESIS: </td>
            <td style="font-size: 0.6rem; text-align: left;">{{ $nephrology->anamnesis }} </td>

            <td style="font-weight: bold; font-size: 0.6rem; width: 13%;">FECHA DE INICIO: </td>
            <td style="font-size: 0.6rem; text-align: left;">{{ $nephrology->date_start }}</td>
        </tr>
    </table>
    <table style="width: 100%; border-collapse:collapse; margin-top:5px;">

        <tr>
            <td style="font-weight: bold; font-size: 0.6rem; width: 8%;">ETIOLOGIA: </td>
            <td style="font-size: 0.6rem; text-align: left;">{{ $nephrology->etiology }}</td>

            <td colspan="2" style="font-weight: bold; font-size: 0.6rem; width: 20%">ACCESO VASCULAR ACTUAL: </td>
            <td style="font-size: 0.6rem; text-align: left;">{{ $nephrology->access }} {{ $nephrology->desc_access }}</td>
        </tr>
    </table>
    <table style="width: 100%; border-collapse:collapse; margin-top:5px;">

        <tr>
            <td style="font-weight: bold; font-size: 0.6rem; width: 15%">SIGNOS Y SINTOMAS: </td>
            <td colspan="6" style="font-size: 0.6rem; text-align: left;">{{ $nephrology->symptoms }}</td>
        </tr>
    </table>


    <table style="width: 100%; border-collapse:collapse; margin-top:10px;">
        <tr>
            <td style="font-size: 0.6rem; text-align: left; font-weight: bold">EXAMEN FISICO: </td>
            <td style="font-size: 0.6rem; text-align: left; font-weight: bold">T°: {{ $nephrology->temperature }} °C</td>
            <td style="font-size: 0.6rem; text-align: left; font-weight: bold">PA: {{ $nephrology->pa }} mmHg</td>
            <td style="font-size: 0.6rem; text-align: left; font-weight: bold">FC: {{ $nephrology->fc }} X'</td>
            <td style="font-size: 0.6rem; text-align: left; font-weight: bold">FR: {{ $nephrology->fr }} X'</td>
            <td style="font-size: 0.6rem; text-align: left; font-weight: bold">PESO: {{ $nephrology->peso }} kg</td>
            <td style="font-size: 0.6rem; text-align: left; font-weight: bold">TALLA: {{ $nephrology->talla }} cm</td>
            <td style="font-size: 0.6rem; text-align: left; font-weight: bold">IMC: {{ $nephrology->imc }}</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:5px;">
        <tr>
            <td style="font-size: 0.7rem; text-align: left;">{{ $nephrology->physical_observation }}</td>
        </tr>
        <tr>
            <td style="font-size: 0.7rem; text-align: left;">{{ $nephrology->torax_pulmones }}</td>
        </tr>
        <tr>
            <td style="font-size: 0.7rem; text-align: left;">{{ $nephrology->cardio }}</td>
        </tr>
        <tr>
            <td style="font-size: 0.7rem; text-align: left;"><strong>Diuresis:</strong> {{ $nephrology->diuresis }} ml</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:5px;">
        <tr>
            <th style="font-size: 0.7rem; text-align: left;">DIAGNOSTICO</th>
            <th style="font-size: 0.7rem; text-align: left;">CIE 10</th>
        </tr>

        <tr>
            <td style="font-size: 0.7rem; text-align: left;">1. {{ $nephrology->dx1 }}</td>
            <td style="font-size: 0.7rem; text-align: left;">{{ $nephrology->cie1 }}</td>
        </tr>

        <tr>
            <td style="font-size: 0.7rem; text-align: left;">2. {{ $nephrology->dx2 }}</td>
            <td style="font-size: 0.7rem; text-align: left;">{{ $nephrology->cie2 }}</td>
        </tr>

        <tr>
            <td style="font-size: 0.7rem; text-align: left;">3. {{ $nephrology->dx3 }}</td>
            <td style="font-size: 0.7rem; text-align: left;">{{ $nephrology->cie3 }}</td>
        </tr>

        <tr>
            <td style="font-size: 0.7rem; text-align: left;">4. {{ $nephrology->dx4 }}</td>
            <td style="font-size: 0.7rem; text-align: left;">{{ $nephrology->cie4 }}</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:5px;">
        <tr>
            <td style="font-weight: bold; font-size: 0.6rem; width: 20%">PRESCRIPCION DE DIALISIS: </td>
            <td style="font-size: 0.6rem; text-align: left;">{{ $nephrology->preescripcion }}</td>

            <td style="font-weight: bold; font-size: 0.6rem; width: 7%;">TIEMPO: </td>
            <td  style="font-size: 0.6rem; text-align: left; width: 10%;">{{ $nephrology->tiempo_hd }} horas</td>

            <td style="font-weight: bold; font-size: 0.6rem; width: 13%">AREA DE FILTRO: </td>
            <td style="font-size: 0.6rem; text-align: left;">{{ $nephrology->area_filtro }} m2</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:5px; border: solid 2px black">
        <tr>
            <td style="font-weight: bold; font-size: 0.7rem; background-color: #BEBEBE; padding: 3px; ">TRATAMIENTO COMPLEMENTARIO</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:5px;">
        <tr>
            <td style="font-weight: bold; font-size: 0.6rem; width: 12%">a) Anemia</td>
            <td style="font-weight: bold; font-size: 0.6rem; width: 2%">SI</td>
            <td style="font-weight: bold; font-size: 0.6rem; border: solid 2px black; width: 4%; text-align: center">{{ $nephrology->anemia == 'SI' ? 'X' : '' }}</td>
            <td width="1%"></td>
            <td style="font-weight: bold; font-size: 0.6rem; width: 3%">NO</td>
            <td style="font-weight: bold; font-size: 0.6rem; border: solid 2px black; width: 4%; text-align: center">{{ $nephrology->anemia == 'NO' ? 'X' : '' }}</td>
            <td width="5%"></td>
            <td style="font-weight: bold; font-size: 0.6rem; width: 10%">Especificar: </td>
            <td style="font-size: 0.6rem; width: 20%">Hemoglobina: </td>
            <td style="font-size: 0.6rem;">{{ $nephrology->hb }} mg/dl</td>
        </tr>

        @if($nephrology->epo2000)
            <tr>
                <td colspan="8"></td>
                <td style="font-size: 0.6rem; width: 10%">Epoetina alfa 2000Ul: </td>
                <td style="font-size: 0.6rem;">{{ $nephrology->epo2000 }}</td>
            </tr>
        @endif

        @if( $nephrology->epo4000 )
        <tr>
            <td colspan="8"></td>
            <td style="font-size: 0.6rem; width: 10%">Epoetina alfa 4000Ul: </td>
            <td style="font-size: 0.6rem;">{{ $nephrology->epo4000 }}</td>
        </tr>
        @endif

        @if( $nephrology->vitb12 )
            <tr>
                <td colspan="8"></td>
                <td style="font-size: 0.6rem; width: 10%">Hidroxicobalamina 1mg: </td>
                <td style="font-size: 0.6rem;">{{ $nephrology->vitb12 }}</td>
            </tr>
        @endif

        @if($nephrology->hierro)
            <tr>
                <td colspan="8"></td>
                <td style="font-size: 0.6rem; width: 10%">Hierro 100mg: </td>
                <td style="font-size: 0.6rem;">{{ $nephrology->hierro }}</td>
            </tr>
        @endif


        <tr>
            <td colspan="12" style="font-size: 0.6rem;"><strong>Observacion:</strong> {{ $nephrology->observacion }}</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:10px;">
        <tr>
            <td style="font-weight: bold; font-size: 0.6rem; width: 30%">b) Alteracion metabolismo oseo mineral</td>
            <td style="font-weight: bold; font-size: 0.6rem; width: 2%">SI</td>
            <td style="font-weight: bold; font-size: 0.6rem; border: solid 2px black; width: 4%; text-align: center">{{ $nephrology->alteracion_meta == 'SI' ? 'X' : '' }}</td>
            <td width="1%"></td>
            <td style="font-weight: bold; font-size: 0.6rem; width: 3%">NO</td>
            <td style="font-weight: bold; font-size: 0.6rem; border: solid 2px black; width: 4%; text-align: center">{{ $nephrology->alteracion_meta == 'NO' ? 'X' : '' }}</td>
            <td width="5%"></td>
            @if($nephrology->alteracion_meta == 'SI')
                <td style="font-weight: bold; font-size: 0.6rem; width: 10%">Especificar: </td>
                <td style="font-size: 0.6rem;">
                    @for($i = 1; $i <= 15; $i++)
                        @if($nephrology->order->recipe->{'code'.$i} == '28897' || $nephrology->order->recipe->{'code'.$i} == '20635' ||
                            $nephrology->order->recipe->{'code'.$i} == '01502' || $nephrology->order->recipe->{'code'.$i} == '01503')
                            {{ $nephrology->order->recipe->{'med'.$i} }} : {{ $nephrology->order->recipe->{'prescripcion'.$i} }}
                        @endif
                    @endfor
                </td>
            @else
                <td style="font-weight: bold; font-size: 0.6rem; width: 10%"></td>
                <td style="font-size: 0.6rem;"></td>
            @endif

        </tr>

    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:10px;">
        <tr>
            <td style="font-weight: bold; font-size: 0.6rem; width: 30%">c) Antihipertensivos</td>
            <td style="font-weight: bold; font-size: 0.6rem; width: 2%">SI</td>
            <td style="font-weight: bold; font-size: 0.6rem; border: solid 2px black; width: 4%; text-align: center">{{ $nephrology->antihipertensivos == 'SI' ? 'X' : '' }}</td>
            <td width="1%"></td>
            <td style="font-weight: bold; font-size: 0.6rem; width: 3%">NO</td>
            <td style="font-weight: bold; font-size: 0.6rem; border: solid 2px black; width: 4%; text-align: center">{{ $nephrology->antihipertensivos == 'NO' ? 'X' : '' }}</td>
            <td width="5%"></td>
            @if($nephrology->antihipertensivos == 'SI')
                <td style="font-weight: bold; font-size: 0.6rem; width: 10%">Especificar: </td>
                <td style="font-size: 0.6rem;">
                    @for($i = 1; $i <= 15; $i++)
                        @if($nephrology->order->recipe->{'code'.$i} == '04701' ||
                             $nephrology->order->recipe->{'code'.$i} == '04523' || $nephrology->order->recipe->{'code'.$i} == '00671' ||
                             $nephrology->order->recipe->{'code'.$i} == '03078' || $nephrology->order->recipe->{'code'.$i} == '05018' ||
                             $nephrology->order->recipe->{'code'.$i} == '05021' || $nephrology->order->recipe->{'code'.$i} == '00900')

                            {{ $nephrology->order->recipe->{'med'.$i} }} : {{ $nephrology->order->recipe->{'prescripcion'.$i} }}
                        @endif
                    @endfor
                </td>
            @else
                <td style="font-weight: bold; font-size: 0.6rem; width: 10%"></td>
                <td style="font-size: 0.6rem;"></td>
            @endif
        </tr>
    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:10px;">

            <tr>
                <td style="font-weight: bold; font-size: 0.6rem; width: 10%">d) Otros</td>

                <td style="font-size: 0.6rem; text-align: justify">
                    @for($i = 1; $i <= 15; $i++)
                        @if(!$nephrology->order->recipe->{'med'.$i} || $nephrology->order->recipe->{'code'.$i} == '03107' || $nephrology->order->recipe->{'code'.$i} == '01502' ||
                             $nephrology->order->recipe->{'code'.$i}== '03113' || $nephrology->order->recipe->{'code'.$i} == '03979' ||
                             $nephrology->order->recipe->{'code'.$i} == '19238' || $nephrology->order->recipe->{'code'.$i} == '28897' ||
                             $nephrology->order->recipe->{'code'.$i} == '20635' || $nephrology->order->recipe->{'code'.$i} == '01502' ||
                             $nephrology->order->recipe->{'code'.$i} == '01503' || $nephrology->order->recipe->{'code'.$i} == '04701' ||
                             $nephrology->order->recipe->{'code'.$i} == '04523' || $nephrology->order->recipe->{'code'.$i} == '00671' ||
                             $nephrology->order->recipe->{'code'.$i} == '03078' || $nephrology->order->recipe->{'code'.$i} == '05018' ||
                             $nephrology->order->recipe->{'code'.$i} == '05021' || $nephrology->order->recipe->{'code'.$i} == '00900')
                        @else
                            {{ $nephrology->order->recipe->{'med'.$i} }} : {{ $nephrology->order->recipe->{'prescripcion'.$i} }} /
                        @endif
                    @endfor
                </td>
            </tr>


    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:5px; border: solid 2px black">
        <tr>
            <td style="font-weight: bold; font-size: 0.7rem; background-color: #BEBEBE; padding: 3px; ">INDICACIONES DE EXAMENES AUXILIARES</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:10px;">
        <tr>
            <td style="font-weight: bold; font-size: 0.7rem; width: 8%">Se solicita: </td>
            <td style="font-size: 0.7rem;">{{ $nephrology->solicitud }}</td>
        </tr>

        <tr>
            <td style="font-weight: bold; font-size: 0.7rem; width: 20%">Fecha de toma de muestra: </td>
            <td style="font-size: 0.7rem;">{{ $nephrology->date_lab }}</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:10px;">
        <tr>
            <td style="font-weight: bold; font-size: 0.7rem; width: 11%">Proxima Cita: </td>
            <td style="font-size: 0.7rem;">{{ $nephrology->date_appointment }}</td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse:collapse; margin-top:10px;">
        <tr>
            <td style="font-weight: bold; font-size: 0.7rem; width: 11%">Atendido por: </td>
        </tr>

        <tr>
            <td style="font-weight: bold; font-size: 0.7rem; width: 13%">Nombre y Apellido:</td>
            <td style="font-size: 0.7rem; width: 25%">{{ $nephrology->order->user->name }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; font-size: 0.7rem; width: 13%">Profesion:</td>
            <td style="font-size: 0.7rem">Medico Cirujano</td>
        </tr>

        <tr>
            <td style="font-weight: bold; font-size: 0.7rem; width: 13%">Especialidad:</td>
            <td style="font-size: 0.7rem">Nefrologia</td>
        </tr>

        <tr>
            <td colspan="8" style="font-size: 0.7rem;"> <strong>N° C.M.P.:</strong> {{ $nephrology->order->user->code_specialty }} - <strong>N° R.N.E.:</strong> {{ $nephrology->order->user->rne }}</td>

        </tr>
    </table>

    <table width="100%" style="margin-top: 5px;">
        <tr>
            @if($nephrology->order->user_id)
                <td rowspan="6" style="text-align: center;">
                    <img src="{{ asset($nephrology->order->user->image) }}" style="width: 180px;height: 90px">
                </td>
            @else
                <td>
                    Aqui va firma del medico.
                </td>
            @endif
        </tr>
    </table>

</div>
