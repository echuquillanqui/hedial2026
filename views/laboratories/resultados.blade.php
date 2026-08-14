<div style="font-family: Arial,sans-serif; width: 100%; height: 965px">

    <table style="width: 100%; text-align: center; margin-top: 40px">

        <h3 style="text-decoration: underline">RESULTADOS DE LABORATORIO</h3>
    </table>

    <table style="padding: 5px; width: 100%; text-align: center">
        <tr>
            <td style="font-size: 0.7rem; font-weight: bold">PACIENTE:</td>
            <td  colspan="3" style="font-size: 0.7rem; border-bottom: 1px solid; text-align: center">{{ $laboratory->patient->name }}</td>
            <td style="font-size: 0.7rem; font-weight: bold">DNI:</td>
            <td style="font-size: 0.7rem; border-bottom: 1px solid; text-align: center">{{ $laboratory->patient->dni }}</td>
            <td style="font-size: 0.7rem; font-weight: bold">PROCEDENCIA:</td>
            <td style="font-size: 0.7rem; border-bottom: 1px solid; text-align: center">FISSAL</td>
        </tr>

        <tr>
            <td style="font-size: 0.7rem; font-weight: bold">FECHA:</td>
            <td colspan="2" style="font-size: 0.7rem; border-bottom: 1px solid; text-align: center">{{ $laboratory->updated_at }}</td>
            <td style="font-size: 0.7rem; font-weight: bold">MEDICO:</td>
            <td colspan="3" style="font-size: 0.7rem; border-bottom: 1px solid; text-align: center">{{ $laboratory->order->user->name }}</td>
        </tr>
    </table>

    <table style="width: 100%; text-align: center; margin-top: -40px;">
        <h5 style="text-decoration: underline">AREA DE HEMATOLOGIA</h5>
    </table>

    <table style="width: 100%; text-align: center; padding: 7px; border: solid 1px #000000; border-collapse: collapse;" border="1">
        <tr style="background-color: #A8A8A8">
            <th style="font-size: 0.7rem; padding: 5px">ANALISIS</th>
            <th style="font-size: 0.7rem; padding: 5px">RESULTADOS</th>
            <th style="font-size: 0.7rem; padding: 5px">UNIDAD</th>
            <th style="font-size: 0.7rem; padding: 5px">VALORES REFERENCIALES</th>
        </tr>

        <tr>
            <td style="font-size: 0.6rem; text-align: left; padding: 3px">HEMATOCRITO</td>
            <td style="font-size: 0.6rem;">{{ $laboratory->result2 }}</td>
            <td style="font-size: 0.6rem;">%</td>
            <td style="font-size: 0.6rem;">VARONES: 42.0 - 54.0 / MUEJRES 37.0 - 48.0</td>
        </tr>

        <tr>
            <td style="font-size: 0.6rem; text-align: left; padding: 3px">HEMOGLOBINA</td>
            <td style="font-size: 0.6rem;">{{ $laboratory->result3 }}</td>
            <td style="font-size: 0.6rem;">g/dl</td>
            <td style="font-size: 0.6rem;">VARONES: 14.0 - 18.0 / MUEJRES 12.0 - 16.0</td>
        </tr>
    </table>

    <table style="width: 100%; text-align: center; margin-top: -45px;">
        <h5 style="text-decoration: underline">AREA DE BIOQUIMICA</h5>
    </table>

    <table style="width: 100%; text-align: center; padding: 7px; border: solid 1px #000000; border-collapse: collapse;" border="1">
        <tr style="background-color: #A8A8A8">
            <th style="font-size: 0.7rem; padding: 5px; width: 300px">ANALISIS</th>
            <th style="font-size: 0.7rem; padding: 5px; width: 40px">RESULTADOS</th>
            <th style="font-size: 0.7rem; padding: 5px; width: 40px">UNIDAD</th>
            <th style="font-size: 0.7rem; padding: 5px">VALORES REFERENCIALES</th>
        </tr>

        <tr>
            <td style="font-size: 0.6rem; text-align: left; padding: 3px;">UREA PRE</td>
            <td style="font-size: 0.6rem;">{{ $laboratory->pre }}</td>
            <td style="font-size: 0.6rem;">mg/dl</td>
            <td style="font-size: 0.6rem;">10 - 50</td>
        </tr>

        <tr>
            <td style="font-size: 0.6rem; text-align: left; padding: 3px">UREA POST</td>
            <td style="font-size: 0.6rem;">{{ $laboratory->post }}</td>
            <td style="font-size: 0.6rem;">mg/dL</td>
            <td style="font-size: 0.6rem;">10 - 50</td>
        </tr>

        @if($laboratory->cloro > 0 && $laboratory->sodio > 0 && $laboratory->potasio > 0 && $laboratory->result5 > 0 && $laboratory->result6)
            <tr>
                <th colspan="4" style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description4 }}</th>
            </tr>

            <tr style="background-color: #BEBEBE">
                <td style="font-size: 0.7rem; text-align: left; padding: 3px">Cloro</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->cloro }}</td>
                <td style="font-size: 0.6rem;">mmol/L</td>
                <td style="font-size: 0.6rem;">98 - 107</td>
            </tr>

            <tr style="background-color: #BEBEBE">
                <td style="font-size: 0.7rem; text-align: left; padding: 3px">Sodio</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->sodio }}</td>
                <td style="font-size: 0.6rem;">mmol/L</td>
                <td style="font-size: 0.6rem;">135 - 148</td>
            </tr>

            <tr style="background-color: #BEBEBE">
                <td style="font-size: 0.7rem; text-align: left; padding: 3px">Potasio</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->potasio }}</td>
                <td style="font-size: 0.6rem;">mmol/L</td>
                <td style="font-size: 0.6rem;">3.5 - 5.3</td>
            </tr>

            <tr>
                <td style="font-size: 0.7rem; text-align: left; padding: 3px">{{ $laboratory->description5 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result5 }}</td>
                <td style="font-size: 0.6rem;">mg/dl</td>
                <td style="font-size: 0.6rem;">2.5 - 5.6</td>
            </tr>

            <tr>
                <td style="font-size: 0.7rem; text-align: left; padding: 3px">{{ $laboratory->description6 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result6 }}</td>
                <td style="font-size: 0.6rem;">mg/dL</td>
                <td style="font-size: 0.6rem;">8.8 - 10.2</td>
            </tr>
        @endif

        @if($laboratory->result7 > 0)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description7 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result7 }}</td>
                <td style="font-size: 0.6rem;">U/L</td>
                <td style="font-size: 0.6rem;">Varones: menor a 50 - Mujeres: menor a 35</td>
            </tr>
        @endif

        @if($laboratory->result8 > 0)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description8 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result8 }}</td>
                <td style="font-size: 0.6rem;">U/L</td>
                <td style="font-size: 0.6rem;">Varones: menor a 50 - Mujeres: menor a 35</td>
            </tr>
        @endif

        @if($laboratory->result9 > 0)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description9 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result9 }}</td>
                <td style="font-size: 0.6rem;">g/dL</td>
                <td style="font-size: 0.6rem;">3.97 - 4.94</td>
            </tr>
        @endif

        @if($laboratory->result10 > 0)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description10 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result10 }}</td>
                <td style="font-size: 0.6rem;">U/L</td>
                <td style="font-size: 0.6rem;">
                    Varones: 40 - 129 /
                    Mujeres: 35 - 104
                </td>
            </tr>
        @endif

        @if($laboratory->result11 > 0)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description11 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result11 }}</td>
                <td style="font-size: 0.6rem;">ug/dL</td>
                <td style="font-size: 0.6rem;">59 - 158</td>
            </tr>
        @endif

        @if($laboratory->result12 > 0)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description12 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result12 }}</td>
                <td style="font-size: 0.6rem;">ng/dL</td>
                <td style="font-size: 0.6rem;">30 - 400</td>
            </tr>
        @endif

        @if($laboratory->result13 > 0)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description13 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result13 }}</td>
                <td style="font-size: 0.6rem;">mg/dL</td>
                <td style="font-size: 0.6rem;">120 - 400</td>
            </tr>
        @endif

        @if($laboratory->result14 > 0)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description14 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result14 }}</td>
                <td style="font-size: 0.6rem;">pg/mL</td>
                <td style="font-size: 0.6rem;">Adultos: 10 - 65 / Niños: 9 - 52 </td>
            </tr>
        @endif

        @if($laboratory->result15 > 0)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description15 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result15 }}</td>
                <td style="font-size: 0.6rem;">COl</td>
                <td style="font-size: 0.6rem;">Inf. 0.90 No Reactivo</td>
            </tr>
        @endif

        @if($laboratory->result16 == 'NO REACTIVO' || $laboratory->result16 == 'REACTIVO')
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description16 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result16 }}</td>
                <td style="font-size: 0.6rem;">COl</td>
                <td style="font-size: 0.6rem;">No Reactivo</td>
            </tr>
        @endif

        @if($laboratory->result17)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description17 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result17 }}</td>
                <td style="font-size: 0.6rem;">COl</td>
                <td style="font-size: 0.6rem;">Menor 0.90 Negativo / Mayor 1 Positivo / 0.90 - 0.99 Indeterminado</td>
            </tr>
        @endif

        @if($laboratory->result18)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description18 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result18 }}</td>
                <td style="font-size: 0.6rem;">COl</td>
                <td style="font-size: 0.6rem;">Inf 10 Positivo / Sup 10 Negativo</td>
            </tr>
        @endif

        @if($laboratory->result19 > 0)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description19 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result19 }}</td>
                <td style="font-size: 0.6rem;">COl</td>
                <td style="font-size: 0.6rem;">Inf 1 Positivo / Sup 1 Negativo</td>
            </tr>
        @endif

        @if($laboratory->result20 > 0)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description20 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result20 }}</td>
                <td style="font-size: 0.6rem;">COl</td>
                <td style="font-size: 0.6rem;">Menor 0.90 Negativo / Mayor a 1 Positivo / 0.90 - 0.99 Indeterminado</td>
            </tr>
        @endif

        @if($laboratory->result21 > 0)
            <tr>
                <td style="font-size: 0.6rem; text-align: left; padding: 3px">{{ $laboratory->description21 }}</td>
                <td style="font-size: 0.7rem;">{{ $laboratory->result21 }}</td>
                <td style="font-size: 0.6rem;">COl</td>
                <td style="font-size: 0.6rem;">Inf 1 No Reactivo / Sup 1 Reactivo</td>
            </tr>
        @endif
    </table>

    <table style="width: 100%; margin-top: 75px">
        <tr>
            <td style="text-align: center">
                <img src="{{ asset('img/sellos/ivan-cerron.jpg') }}" style="text-align: center">
            </td>
        </tr>
    </table>

</div>
