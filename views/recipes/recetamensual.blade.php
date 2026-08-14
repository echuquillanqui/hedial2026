<div style="font-family: 'Nunito', sans-serif;">
    <table style="width: 100%; border: #0a0c0d 2px solid; padding: 10px">
        <tr>
            <td>
                <img src="{{ asset('img/brand/logo_hs_occupational.png') }}" alt="" width="20%">
            </td>

            <td>
                <h2 style="text-align: left; width: 120%">RECETA MEDICA</h2>
            </td>

            <td style="text-align: right">
                <img src="{{ asset('img/brand/logo_hs_occupational.png') }}" alt="" width="20%">
            </td>
        </tr>
    </table>

    <br>

    <table style="width: 100%;font-size: 0.8rem; border: #0a0c0d 2px solid; margin-top: -15px">
        <tr>

            <td>NOMBRES Y APELLIDOS: {{ $recipe->patient->name }}</td>
            <td>DNI: {{ $recipe->patient->dni }}</td>
            <td>H.CL: {{ $recipe->patient->dni }}</td>
            <td>FECHA: {{ $recipe->date_order }}</td>

        </tr>
    </table>
    <br>
    <table width="100%" style="border-collapse:collapse;text-align:center; margin-top:-15px; font-size: 0.7rem" border="1px;">

        <tr style="border: #0a0c0d 2px solid; brecipe-collapse: collapse">
            <th>CODIGO</th>
            <th>MEDICAMENTO</th>
            <th>PRESCRIPCION</th>
            <th>C. PRESCRITA</th>
            <th>C. PRESCRITA</th>
        </tr>

        @if($recipe->prescrita1 > 0  || $recipe->entregada1 > 0)
            <tr>
                <td>{{ $recipe->code1 }}</td>
                <td>{{ $recipe->med1 }}</td>
                <td>{{ $recipe->prescripcion1 }}</td>
                <td>{{ $recipe->prescrita1 }}</td>
                <td>{{ $recipe->entregada1 }}</td>
            </tr>
        @endif
        @if($recipe->prescrita2 > 0  || $recipe->entregada2 > 0 )
        <tr>
            <td>{{ $recipe->code2 }}</td>
            <td>{{ $recipe->med2 }}</td>
            <td>{{ $recipe->prescripcion2 }}</td>
            <td>{{ $recipe->prescrita2 }}</td>
            <td>{{ $recipe->entregada2 }}</td>
        </tr>
        @endif
        @if($recipe->prescrita3 > 0  || $recipe->entregada3 > 0 )
        <tr>
            <td>{{ $recipe->code3 }}</td>
            <td>{{ $recipe->med3 }}</td>
            <td>{{ $recipe->prescripcion3 }}</td>
            <td>{{ $recipe->prescrita3 }}</td>
            <td>{{ $recipe->entregada3 }}</td>
        </tr>
        @endif
        @if($recipe->prescrita4 > 0  || $recipe->entregada4 > 0 )
        <tr>
            <td>{{ $recipe->code4 }}</td>
            <td>{{ $recipe->med4 }}</td>
            <td>{{ $recipe->prescripcion4 }}</td>
            <td>{{ $recipe->prescrita4 }}</td>
            <td>{{ $recipe->entregada4 }}</td>
        </tr>
        @endif
        @if($recipe->prescrita5 > 0  || $recipe->entregada5 > 0 )
        <tr>
            <td>{{ $recipe->code5 }}</td>
            <td>{{ $recipe->med5 }}</td>
            <td>{{ $recipe->prescripcion5 }}</td>
            <td>{{ $recipe->prescrita5 }}</td>
            <td>{{ $recipe->entregada5 }}</td>
        </tr>
        @endif
        @if($recipe->prescrita6 > 0  || $recipe->entregada6 > 0 )
        <tr>
            <td>{{ $recipe->code6 }}</td>
            <td>{{ $recipe->med6 }}</td>
            <td>{{ $recipe->prescripcion6 }}</td>
            <td>{{ $recipe->prescrita6 }}</td>
            <td>{{ $recipe->entregada6 }}</td>
        </tr>
        @endif
        @if($recipe->prescrita7 > 0  || $recipe->entregada7 > 0 )
        <tr>
            <td>{{ $recipe->code7 }}</td>
            <td>{{ $recipe->med7 }}</td>
            <td>{{ $recipe->prescripcion7 }}</td>
            <td>{{ $recipe->prescrita7 }}</td>
            <td>{{ $recipe->entregada7 }}</td>
        </tr>
        @endif
        @if($recipe->prescrita8 > 0  || $recipe->entregada8 > 0 )
        <tr>
            <td>{{ $recipe->code8 }}</td>
            <td>{{ $recipe->med8 }}</td>
            <td>{{ $recipe->prescripcion8 }}</td>
            <td>{{ $recipe->prescrita8 }}</td>
            <td>{{ $recipe->entregada8 }}</td>
        </tr>
        @endif
        @if($recipe->prescrita9 > 0  || $recipe->entregada9 > 0 )
        <tr>
            <td>{{ $recipe->code9 }}</td>
            <td>{{ $recipe->med9 }}</td>
            <td>{{ $recipe->prescripcion9 }}</td>
            <td>{{ $recipe->prescrita9 }}</td>
            <td>{{ $recipe->entregada9 }}</td>
        </tr>
        @endif
        @if($recipe->prescrita10 > 0  || $recipe->entregada10 > 0 )
        <tr>
            <td>{{ $recipe->code10 }}</td>
            <td>{{ $recipe->med10 }}</td>
            <td>{{ $recipe->prescripcion10 }}</td>
            <td>{{ $recipe->prescrita10 }}</td>
            <td>{{ $recipe->entregada10 }}</td>
        </tr>
        @endif
        @if($recipe->prescrita11 > 0  || $recipe->entregada11 > 0 )
        <tr>
            <td>{{ $recipe->code11 }}</td>
            <td>{{ $recipe->med11 }}</td>
            <td>{{ $recipe->prescripcion11 }}</td>
            <td>{{ $recipe->prescrita11 }}</td>
            <td>{{ $recipe->entregada11 }}</td>
        </tr>
        @endif
        @if($recipe->prescrita12 > 0  || $recipe->entregada12 > 0 )
        <tr>
            <td>{{ $recipe->code12 }}</td>
            <td>{{ $recipe->med12 }}</td>
            <td>{{ $recipe->prescripcion12 }}</td>
            <td>{{ $recipe->prescrita12 }}</td>
            <td>{{ $recipe->entregada12 }}</td>
        </tr>
        @endif
        @if($recipe->prescrita13 > 0  || $recipe->entregada13 > 0 )
        <tr>
            <td>{{ $recipe->code13 }}</td>
            <td>{{ $recipe->med13 }}</td>
            <td>{{ $recipe->prescripcion13 }}</td>
            <td>{{ $recipe->prescrita13 }}</td>
            <td>{{ $recipe->entregada13 }}</td>
        </tr>
        @endif
        @if($recipe->prescrita14 > 0 || $recipe->entregada14 > 0)
        <tr>
            <td>{{ $recipe->code14 }}</td>
            <td>{{ $recipe->med14 }}</td>
            <td>{{ $recipe->prescripcion14 }}</td>
            <td>{{ $recipe->prescrita14 }}</td>
            <td>{{ $recipe->entregada14 }}</td>
        </tr>
        @endif

    </table>

    <table width="100%" style="border-collapse:collapse; margin-top:5px; font-size: 0.8rem;" border="1px">
         <tr>
             <td colspan="6" style="height: 200px; text-align: center">
                 @if($recipe->order->user_id)
                     <img src="{{ asset($recipe->order->user->image) }}" alt="" width="180px" height="100px">
                 @else
                     <p>AQUI VA SELLO DEL MEDICO</p>
                 @endif
             </td>

             <td colspan="6" style="width: 50%; text-align: left">

             </td>
         </tr>

        <tr style="text-align: center">
            <td colspan="6" style="font-weight: bold">SELLO Y FIRMA DEL MEDICO</td>
            <td colspan="6" style="font-weight: bold">FIRMA Y HUELLA DEL PACIENTE</td>
        </tr>
    </table>

</div>



