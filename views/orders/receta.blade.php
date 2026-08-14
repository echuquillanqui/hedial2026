<div style="font-family: 'Nunito', sans-serif;">
    <table style="width: 100%; border: #0a0c0d 2px solid; padding: 10px">
        <tr>
            <td>
                <img src="{{ asset('img/brand/logo_hs_occupational.png') }}" alt="" width="20%">
            </td>

            <td>
                <h2>RECETA MEDICA POR SESION</h2>
            </td>
        </tr>
    </table>

    <br>

    <table style="width: 100%;font-size: 0.8rem; border: #0a0c0d 2px solid;">
        <tr>

            <td>NOMBRES Y APELLIDOS: {{ $order->patient->name }}</td>
            <td>DNI: {{ $order->patient->dni }}</td>
            <td>H.CL: {{ $order->patient->dni }}</td>
            <td>FECHA: {{ \Carbon\Carbon::parse($order->date_order)->format('Y-m-d') }}</td>

        </tr>
    </table>
    <br>
    <table width="100%" style="border-collapse:collapse;text-align:center; margin-top:0px" border="1px;">

            <tr style="border: #0a0c0d 2px solid; border-collapse: collapse">
                <th>CODIGO</th>
                <th>MEDICAMENTO</th>
                <th>PRESENTACION</th>
                <th>CANTIDAD</th>


                @if($order->medical->epo > 0)
                <tr>
                    <td>3107</td>
                    <td>EPOETINA ALFA (ERITROPOYETINA)</td>
                    <td> 1 ML 2000 UI/ML INY</td>
                    <td>{{ $order->medical->epo }}</td>
                </tr>
                @endif


                @if($order->medical->epo4000 > 0)
                    <tr>
                        <td>3113</td>
                        <td>EPOETINA ALFA (ERITROPOYETINA)</td>
                        <td>4000 UI/ML INY 1 ML</td>
                        <td>{{ $order->medical->epo4000 }}</td>
                    </tr>
                @endif

                @if($order->medical->vitb12 > 0)
                    <tr>
                        <td>3979</td>
                        <td>VITAMINA B12 HIDROXICOBALAMINA</td>
                        <td>1MG/ML INY 1ML</td>
                        <td>{{ $order->medical->vitb12 }}</td>
                    </tr>
                @endif

                @if($order->medical->iron > 0)
                    <tr>
                        <td>19238</td>
                        <td>HIERRO (COMO SACARATO)</td>
                        <td>5 ML 20 MG FE/ML INY</td>
                        <td>{{ $order->medical->iron }}</td>
                    </tr>
                @endif

        @if($order->medical->calci > 0)
            <tr>
                <td>01502</td>
                <td>CALCITRIOL</td>
                <td>1 MCG/ML INY</td>
                <td>{{ $order->medical->calci }}</td>
            </tr>
        @endif

    </table>


    <br>
    <br>
    <br>
    <br>
    <div style="text-align: center">

        @if($order->user_id)
            <img src="{{ asset($order->medical->user->image) }}" width="250" height="120" alt="">
        @else
            <p>AQUI VA SELLO DEL MEDICO</p>
        @endif

    </div>
</div>



