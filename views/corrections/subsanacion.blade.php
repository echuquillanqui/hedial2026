<div style="font-family: Arial,sans-serif; text-transform: uppercase; margin: 0px auto;width: 100%; border: black 2px solid; padding: 5px;">
    <table >

        <tr>
            <td colspan="10" style="text-align: left">
                <img src="{{ asset('img/brand/logo-fissal.png') }}" alt="logo superior" style="width: 520px; height: 60px">
            </td>
        </tr>

    </table>

    <div width="100%" style="text-align: center; margin-top: -10px; font-size: 0.9rem">
        <h5>FORMATO DE ATENCION</h5>
    </div>

    <table width="100%" style="border-collapse:collapse;text-align:center; margin-top:-15px">
        <tr>
            <td style="border: none"></td>
            <td style="width: 200px; background: #A8A8A8; font-weight: bold; font-size: 0.6rem">NUMERO DE FORMATO</td>
            <td></td>
        </tr>

        <tr>
            <td></td>
            <td style="border: #0a0c0d solid 1px; font-size: 0.9rem; font-weight: bold">8181-23-000{{ $correction->serie_fua }}</td>
            <td></td>
        </tr>
    </table>

    <table width="100%" style="border-collapse:collapse;text-align:center; margin-top:5px;" border="1px">
        <tr style="border: #0a0c0d solid 1px; background: #A8A8A8; font-size: 0.5rem; font-weight: bold">
            <td style="height: 15px; width: 150px">CODIGO IPRESS</td>
            <td style="width: 300px">NOMBRE DE IPRESS QUE REALIZA LA ATENCION</td>
            <td style="width: 180px;">SUBSANACION</td>
            <td style="background: white; padding: 2px">X</td>
        </tr>

        <tr>
            <td rowspan="2" style="font-size: 0.7rem; font-weight: bold">8181</td>
            <td rowspan="2" style="font-size: 0.7rem; font-weight: bold">H&S OCCUPATIONAL SAC</td>
            <td colspan="2" style="font-size: 0.5rem; font-weight: bold">N° FORMATO ATENCION PARA SUBSANACION</td>
        </tr>

        <tr>

            <td colspan="2" style="font-size: 0.8rem; font-weight: bold">8181-23-0000{{ $correction->order->n_fua }}</td>
        </tr>

    </table>

    <table width="100%" style="border-collapse:collapse;text-align:center; margin-top:5px;" border="1px">
        <tr>
            <td colspan="3" style="height: 10px; font-size: 0.5rem; font-weight: bold; background: #A8A8A8;">FECHA DE ATENCION</td>
            <td style="height: 10px; font-size: 0.5rem; font-weight: bold; background: #A8A8A8">HORA</td>
            <td style="height: 10px; font-size: 0.5rem; font-weight: bold; background: #A8A8A8">N° HISTORIA CLINICA</td>
            <td colspan="4" style="height: 15px; font-size: 0.5rem; font-weight: bold; background: #A8A8A8">IDENTIFICACION</td>
            <td colspan="2" style="height: 15px; font-size: 0.5rem; font-weight: bold; background: #A8A8A8">REGIMEN</td>
        </tr>

        <tr>
            <td style="height: 10px; font-size: 0.6rem; background: #BEBEBE">DIA</td>
            <td style="height: 10px; font-size: 0.6rem; background: #BEBEBE">MES</td>
            <td style="height: 10px; font-size: 0.6rem; background: #BEBEBE">AÑO</td>

            @if($correction->order->shift_id == 1)
                <td rowspan="2" style="font-size: 0.7rem; font-weight: bold">5:20</td>
            @elseif($correction->order->shift_id == 2)
                <td rowspan="2" style="font-size: 0.7rem; font-weight: bold">9:20</td>
            @elseif($correction->order->shift_id == 3)
                <td rowspan="2" style="font-size: 0.7rem; font-weight: bold">13:20</td>
            @else
                <td rowspan="2" style="font-size: 0.7rem; font-weight: bold">17:20</td>
            @endif


            <td rowspan="2" style="font-size: 0.6rem; font-weight: bold">{{ $correction->order->patient->dni }}</td>
            <td rowspan="2" style="font-size: 0.5rem; background: #BEBEBE">TD</td>
            <td rowspan="2" style="font-size: 0.6rem; font-weight: bold">2</td>
            <td rowspan="2" style="font-size: 0.5rem; background: #BEBEBE">N° DOCUMENTO</td>
            <td rowspan="2" style="font-size: 0.6rem; font-weight: bold">{{ $correction->order->patient->dni }}</td>
            <td style="font-size: 0.4rem; background: #BEBEBE">SUBSIDIADO</td>
            <td style="font-size: 0.5rem; font-weight: bold;">X</td>
        </tr>

        <tr>
            <td style="height: 10px; font-size: 0.6rem;  font-weight: bold">{{ $carbonDia }}</td>
            <td style="height: 10px; font-size: 0.6rem;  font-weight: bold">{{ $carbonMes }}</td>
            <td style="height: 10px; font-size: 0.6rem;  font-weight: bold">{{ $carbonAnio }}</td>
            <td style="font-size: 0.4rem; background: #BEBEBE">SEMICONTRIBUTIVO</td>
            <td style="font-size: 0.5rem; font-weight: bold;"></td>
        </tr>
    </table>

    <table width="100%" style="border-collapse:collapse;text-align:center; margin-top:5px;" border="1px">
        <tr style="font-size: 0.5rem; font-weight: bold; background: #A8A8A8;">
            <td style="height: 15px">APELLIDO PATERNO</td>
            <td style="height: 15px">APELLIDO MATERNO</td>
        </tr>

        <tr style="font-size: 0.6rem; font-weight: bold;">
            <td style="height: 15px">{{ $correction->order->patient->surname }}</td>
            <td style="height: 15px">{{ $correction->order->patient->lastname }}</td>
        </tr>

        <tr style="font-size: 0.5rem; font-weight: bold; background: #A8A8A8;">
            <td  style="height: 15px">PRIMER NOMBRE</td>
            <td  style="height: 15px">OTROS NOMBRES</td>
        </tr>

        <tr style="font-size: 0.6rem; font-weight: bold;">
            <td style="height: 15px">{{ $correction->order->patient->firstname }}</td>
            <td style="height: 15px">{{ $correction->order->patient->othername }}</td>
        </tr>
    </table>

    <table width="100%" style="border-collapse:collapse;text-align:center; margin-top:5px;" border="1px">
        <tr style="font-size: 0.6rem; font-weight: bold; background: #A8A8A8">
            <td colspan="4" style="height: 20px">TIPO DE PRESTACION</td>
        </tr>

        <tr>
            <td style="font-size: 0.6rem; background: #BEBEBE; text-align: left; height: 20px"> Consulta externa</td>
            <td style="font-size: 0.6rem; font-weight: bold;"></td>
            <td style="font-size: 0.6rem; background: #BEBEBE; text-align: left"> Atencion de procedimientos ambulatorios</td>
            <td style="font-size: 0.6rem; font-weight: bold;">X</td>
        </tr>
    </table>

    <table width="100%" style="border-collapse:collapse;text-align:center; margin-top:5px;" border="1px">
        <tr style="font-size: 0.6rem; font-weight: bold; background: #A8A8A8">
            <td rowspan="2" style="width: 15px">N°</td>
            <td colspan="5" style="height: 15px">DIAGNOSTICOS</td>
        </tr>

        <tr style="font-size: 0.5rem; font-weight: bold; background: #BEBEBE">
            <td style="width: 490px; height: 20px">DESCRIPCION</td>
            <td style="width: 90px">CIE - 10</td>
            <td colspan="3">TIPO Dx</td>
        </tr>

        <tr style="font-size: 0.6rem; font-weight: bold;">
            <td style="height: 25px">1</td>
            <td style="text-align: left">ENFERMEDAD RENAL CRONICA 5 EN HEMODIALISIS</td>
            <td style="font-size: 0.6rem">N18.0</td>
            <td></td>
            <td>D</td>
            <td></td>
        </tr>

        <tr style="font-size: 0.5rem; font-weight: bold;">
            <td style="height: 25px">2</td>
            <td style="text-align: left"></td>
            <td></td>
            <td>P</td>
            <td>D</td>
            <td>R</td>
        </tr>

        <tr style="font-size: 0.5rem; font-weight: bold;">
            <td style="height: 25px">3</td>
            <td style="text-align: left"></td>
            <td></td>
            <td>P</td>
            <td>D</td>
            <td>R</td>
        </tr>

        <tr style="font-size: 0.5rem; font-weight: bold;">
            <td style="height: 25px">4</td>
            <td style="text-align: left"></td>
            <td></td>
            <td>P</td>
            <td>D</td>
            <td>R</td>
        </tr>

        <tr style="font-size: 0.5rem; font-weight: bold;">
            <td style="height: 25px">5</td>
            <td style="text-align: left"></td>
            <td></td>
            <td>P</td>
            <td>D</td>
            <td>R</td>
        </tr>

        <tr style="font-size: 0.5rem; font-weight: bold;">
            <td style="height: 25px">6</td>
            <td style="text-align: left"></td>
            <td></td>
            <td>P</td>
            <td>D</td>
            <td>R</td>
        </tr>

    </table>

    <table width="100%" style="border-collapse:collapse;text-align:center; margin-top:5px;" border="1px">
        <tr style="font-size: 0.5rem; font-weight: bold; background: #A8A8A8">
            <td style="width: 130px; height: 15px">N° DNI</td>
            <td> NOMBRE DEL RESPONSABLE DE LA ATENCION</td>
            <td style="width: 130px;">N° COLEGIATURA</td>
        </tr>

        <tr style="font-size: 0.5rem; font-weight: bold;">
            @if($correction->order->user_id)
                <td style="height: 15px">{{ $correction->order->user->dni }}</td>
                <td style="height: 15px">{{ $correction->order->user->name }}</td>
                <td style="height: 15px">{{ $correction->order->user->code_specialty }}</td>
            @else
                <td style="height: 15px">aun no se registra medico</td>
                <td style="height: 15px">aun no se registra medico</td>
                <td style="height: 15px">aun no se registra medico</td>
            @endif
        </tr>
    </table>

    <table width="100%" style="border-collapse:collapse;text-align:center; margin-top:5px;">
        <tr style="font-size: 0.5rem; font-weight: bold;">
            <td style="background: #A8A8A8;border: #000000 1px solid; width: 150px; height: 20px">RESPONSABLE DE LA ATENCION</td>
            <td style="border: black 1px solid; width: 30px"> 1 </td>

            <td style="border: white;"></td>

            <td style="background: #A8A8A8;border: #000000 1px solid; width: 90px; height: 20px">ESPECIALIDAD</td>
            <td style="border: black 1px solid; width: 190px"> NEFROLOGIA </td>

            <td></td>

            <td style="border: white"></td>

            <td rowspan="3" style="border: #000000 1px solid; width: 200px;">

            </td>
        </tr>

        <tr style="font-size: 0.4rem; text-align: left;">
            <td colspan="5" style="border: black 1px solid; height: 25px">1= MÉDICO; 2=FARMACÉUTICO; 3=ODONTOLOGO; 4=BIÓLOGO; 5=OBSTETRIZ; 6= ENFERMERA;
                7= TRABAJADORA SOCIAL; 8=PSICÓLOGO; 9=TECNÓLOGO MÉDICO;
                10=NUTRICIONISTA; 11= TÉCNICO ENFERMERÍA; 12=AUXILIAR DE ENFERMERÍA
            </td>

            <td style="border: white"></td>
        </tr>

        <tr>
            <td style="height: 150px" colspan="5"></td>
        </tr>

    </table>

    <table width="100%" style="border-collapse:collapse; margin-bottom: 100px">
        <tr style="font-weight: bold;">

            @if($correction->order->user_id)
                <td style="text-align: center;">
                    <img src="" style="width: 180px;height: 90px">
                </td>
            @else
                <td>
                    <p style="width: 110px;height: 50px">Aqui va firma del medico</p>
                </td>
            @endif

            <td style="text-align: center; font-size: 0.5rem; width: 250px">Firma del asegurado o apoderado</td>

            <td style="text-align: center; font-size: 0.5rem; width: 200px">Huella digital del Asegurado o Apoderado</td>
        </tr>
    </table>


    <table width="100%">
        <tr>
            <br>
            <br>
            <td style="font-size: 0.4rem; text-align:center;">FORMATO DE ATENCION</td>
        </tr>
        <tr>
            <td style="font-size: 0.4rem; text-align:left;">(CARA POSTERIOR)</td>
        </tr>
    </table>

    <table width="100%" style="border-collapse:collapse;text-align:center;margin-top: -30px; font-size: 0.4rem" border="1px">
        <tr style="font-size: 0.5rem; font-weight: bold; background: #000000">
            <td colspan="8" style="height: 10px; color: white;">MEDICAMENTOS</td>
        </tr>
        <tr style="font-size: 0.5rem; font-weight: bold; background: #BEBEBE">
            <td>CODIGO</td>
            <td>DESCRIPCION</td>
            <td>C. PRESCRITA</td>
            <td>C. ENTREGADA</td>
            <td>CODIGO</td>
            <td>DESCRIPCION</td>
            <td>C. PRESCRITA</td>
            <td>C. ENTREGADA</td>
        </tr>

        @if(!$correction->order->recipe->med1 || $correction->order->recipe->code1 == '03107' || $correction->order->recipe->code1 == '03113' || $correction->order->recipe->code1 == '03979' || $correction->order->recipe->code1 == '19238')

        @else
            <tr>
                <td>{{ $correction->order->recipe->code1 }}</td>
                <td>{{ $correction->order->recipe->med1 }}</td>
                <td>{{ $correction->order->recipe->prescrita1 }}</td>
                <td>{{ $correction->order->recipe->entregada1 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if(!$correction->order->recipe->med2 || $correction->order->recipe->code2 == '03107' || $correction->order->recipe->code2 == '03113' || $correction->order->recipe->code2 == '03979' || $correction->order->recipe->code2 == '19238')
            @else
            <tr>
                <td>{{ $correction->order->recipe->code2 }}</td>
                <td>{{ $correction->order->recipe->med2 }}</td>
                <td>{{ $correction->order->recipe->prescrita2 }}</td>
                <td>{{ $correction->order->recipe->entregada2 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if(!$correction->order->recipe->med3 || $correction->order->recipe->code3 == '03107' || $correction->order->recipe->code3 == '03113' || $correction->order->recipe->code3 == '03979' || $correction->order->recipe->code3 == '19238')
            @else
            <tr>
                <td>{{ $correction->order->recipe->code3 }}</td>
                <td>{{ $correction->order->recipe->med3 }}</td>
                <td>{{ $correction->order->recipe->prescrita3 }}</td>
                <td>{{ $correction->order->recipe->entregada3 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if(!$correction->order->recipe->med4 || $correction->order->recipe->code4 == '03107' || $correction->order->recipe->code4 == '03113' || $correction->order->recipe->code4 == '03979' || $correction->order->recipe->code4 == '19238')
            @else
            <tr>
                <td>{{ $correction->order->recipe->code4 }}</td>
                <td>{{ $correction->order->recipe->med4 }}</td>
                <td>{{ $correction->order->recipe->prescrita4 }}</td>
                <td>{{ $correction->order->recipe->entregada4 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if(!$correction->order->recipe->med5 || $correction->order->recipe->code5 == '03107' || $correction->order->recipe->code5 == '03113' || $correction->order->recipe->code5 == '03979' || $correction->order->recipe->code5 == '19238')
            @else
            <tr>
                <td>{{ $correction->order->recipe->code5 }}</td>
                <td>{{ $correction->order->recipe->med5 }}</td>
                <td>{{ $correction->order->recipe->prescrita5 }}</td>
                <td>{{ $correction->order->recipe->entregada5 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if(!$correction->order->recipe->med6 || $correction->order->recipe->code6 == '03107' || $correction->order->recipe->code6 == '03113' || $correction->order->recipe->code6 == '03979' || $correction->order->recipe->code6 == '19238')
            @else
            <tr>
                <td>{{ $correction->order->recipe->code6 }}</td>
                <td>{{ $correction->order->recipe->med6 }}</td>
                <td>{{ $correction->order->recipe->prescrita6 }}</td>
                <td>{{ $correction->order->recipe->entregada6 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if(!$correction->order->recipe->med7 || $correction->order->recipe->code7 == '03107' || $correction->order->recipe->code7 == '03113' || $correction->order->recipe->code7 == '03979' || $correction->order->recipe->code7 == '19238')
            @else
            <tr>
                <td>{{ $correction->order->recipe->code7 }}</td>
                <td>{{ $correction->order->recipe->med7 }}</td>
                <td>{{ $correction->order->recipe->prescrita7 }}</td>
                <td>{{ $correction->order->recipe->entregada7 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if(!$correction->order->recipe->med8 || $correction->order->recipe->code8 == '03107' || $correction->order->recipe->code8 == '03113' || $correction->order->recipe->code8 == '03979' || $correction->order->recipe->code8 == '19238')
            @else
            <tr>
                <td>{{ $correction->order->recipe->code8 }}</td>
                <td>{{ $correction->order->recipe->med8 }}</td>
                <td>{{ $correction->order->recipe->prescrita8 }}</td>
                <td>{{ $correction->order->recipe->entregada8 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if(!$correction->order->recipe->med9 || $correction->order->recipe->code9 == '03107' || $correction->order->recipe->code9 == '03113' || $correction->order->recipe->code9 == '03979' || $correction->order->recipe->code9 == '19238')
            @else
            <tr>
                <td>{{ $correction->order->recipe->code9 }}</td>
                <td>{{ $correction->order->recipe->med9 }}</td>
                <td>{{ $correction->order->recipe->prescrita9 }}</td>
                <td>{{ $correction->order->recipe->entregada9 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if(!$correction->order->recipe->med10 || $correction->order->recipe->code10 == '03107' || $correction->order->recipe->code10 == '03113' || $correction->order->recipe->code10 == '03979' || $correction->order->recipe->code10 == '19238')
            @else
            <tr>
                <td>{{ $correction->order->recipe->code10 }}</td>
                <td>{{ $correction->order->recipe->med10 }}</td>
                <td>{{ $correction->order->recipe->prescrita10 }}</td>
                <td>{{ $correction->order->recipe->entregada10 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if(!$correction->order->recipe->med11 || $correction->order->recipe->code11 == '03107' || $correction->order->recipe->code11 == '03113' || $correction->order->recipe->code11 == '03979' || $correction->order->recipe->code11 == '19238')
            @else
            <tr>
                <td>{{ $correction->order->recipe->code11 }}</td>
                <td>{{ $correction->order->recipe->med11 }}</td>
                <td>{{ $correction->order->recipe->prescrita11 }}</td>
                <td>{{ $correction->order->recipe->entregada11 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if(!$correction->order->recipe->med12 || $correction->order->recipe->code12 == '03107' || $correction->order->recipe->code12 == '03113' || $correction->order->recipe->code12 == '03979' || $correction->order->recipe->code12 == '19238')
            @else
            <tr>
                <td>{{ $correction->order->recipe->code12 }}</td>
                <td>{{ $correction->order->recipe->med12 }}</td>
                <td>{{ $correction->order->recipe->prescrita12 }}</td>
                <td>{{ $correction->order->recipe->entregada12 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if(!$correction->order->recipe->med13 || $correction->order->recipe->code13 == '03107' || $correction->order->recipe->code13 == '03113' || $correction->order->recipe->code13 == '03979' || $correction->order->recipe->code13 == '19238')
            @else
            <tr>
                <td>{{ $correction->order->recipe->code13 }}</td>
                <td>{{ $correction->order->recipe->med13 }}</td>
                <td>{{ $correction->order->recipe->prescrita13 }}</td>
                <td>{{ $correction->order->recipe->entregada13 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif
        @if(!$correction->order->recipe->med14 || $correction->order->recipe->code14 == '03107' || $correction->order->recipe->code14 == '03113' || $correction->order->recipe->code14 == '03979' || $correction->order->recipe->code14 == '19238')
            @else
            <tr>
                <td>{{ $correction->order->recipe->code14 }}</td>
                <td>{{ $correction->order->recipe->med14 }}</td>
                <td>{{ $correction->order->recipe->prescrita14 }}</td>
                <td>{{ $correction->order->recipe->entregada14 }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        @endif

         <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
         </tr>

         <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
         </tr>
         <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
         </tr>
         <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
         </tr>
         <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
         </tr>
         <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
         </tr>
         <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
         </tr>

        <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
        </tr>
        <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
        </tr>

        <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
        </tr>
        <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
        </tr>

        <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
        </tr>
        <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
        </tr>

        <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
        </tr>
        <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
        </tr>

        <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
        </tr>
        <tr style="font-size: 0.5rem;">
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
            <td style="font-size: 0.5rem; height: 10px"></td>
        </tr>


    </table>

    <table width="100%" style="border-collapse:collapse;text-align:center;margin-top: 5px" border="1px">
        <tr style="font-size: 0.5rem; font-weight: bold; background: #000000">
            <td colspan="8" style="height: 10px; color: white;">PROCEDIMIENTOS: SESIONES DE HEMODIALISIS / EXAMENES AUXILIARES / CONSULTAS</td>
        </tr>
        <tr style="font-size: 0.5rem; font-weight: bold; background: #BEBEBE">
            <td>CODIGO</td>
            <td>DESCRIPCION</td>
            <td>C. PRESCRITA</td>
            <td>C. EJECUTADA</td>
            <td>CODIGO</td>
            <td>DESCRIPCION</td>
            <td>C. PRESCRITA</td>
            <td>C. EJECUTADA</td>
        </tr>

        <tr style="font-size: 0.4rem;">

            <td></td>
            <td style="text-align: left"></td>
            <td></td>
            <td></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>

        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant1 > 0 ? '' : $correction->order->laboratory->code1 }}</td>
            <td style="font-size: 0.4rem; height: 15px; text-align: left">{{ !$correction->order->laboratory->cant1 > 0 ? '' : $correction->order->laboratory->description1 }}</td>
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant1 > 0 ? '' : $correction->order->laboratory->cant1 }}</td>
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant1 > 0 ? '' : $correction->order->laboratory->cant1 }}</td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>

        <tr style="font-size: 0.4rem; text-align: center">
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant2 > 0 ? '' : $correction->order->laboratory->code2 }}</td>
            <td style="font-size: 0.4rem; height: 15px; text-align: left">{{ !$correction->order->laboratory->cant2 > 0 ? '' : $correction->order->laboratory->description2 }}</td>
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant2 > 0 ? '' : $correction->order->laboratory->cant2 }}</td>
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant2 > 0 ? '' : $correction->order->laboratory->cant2 }}</td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>

        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant3 > 0 ? '' : $correction->order->laboratory->code3 }}</td>
            <td style="font-size: 0.4rem; height: 15px; text-align: left">{{ !$correction->order->laboratory->cant3 > 0 ? '' : $correction->order->laboratory->description3 }}</td>
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant3 > 0 ? '' : $correction->order->laboratory->cant3 }}</td>
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant3 > 0 ? '' : $correction->order->laboratory->cant3 }}</td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>


        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant4 > 0 ? '' : $correction->order->laboratory->code4 }}</td>
            <td style="font-size: 0.4rem; height: 15px; text-align: left">{{ !$correction->order->laboratory->cant4 > 0 ? '' : $correction->order->laboratory->description4 }}</td>
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant4 > 0 ? '' : $correction->order->laboratory->cant4 }}</td>
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant4 > 0 ? '' : $correction->order->laboratory->cant4 }}</td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>
        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant5 > 0 ? '' : $correction->order->laboratory->code5 }}</td>
            <td style="font-size: 0.4rem; height: 15px; text-align: left">{{ !$correction->order->laboratory->cant5 > 0 ? '' : $correction->order->laboratory->description5 }}</td>
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant5 > 0 ? '' : $correction->order->laboratory->cant5 }}</td>
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant5 > 0 ? '' : $correction->order->laboratory->cant5 }}</td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>
        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant6 > 0 ? '' : $correction->order->laboratory->code6 }}</td>
            <td style="font-size: 0.4rem; height: 15px; text-align: left">{{ !$correction->order->laboratory->cant6 > 0 ? '' : $correction->order->laboratory->description6 }}</td>
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant6 > 0 ? '' : $correction->order->laboratory->cant6 }}</td>
            <td style="font-size: 0.4rem; height: 15px">{{ !$correction->order->laboratory->cant6 > 0 ? '' : $correction->order->laboratory->cant6 }}</td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>
        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>
        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>
        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>
        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>
        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>
        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>
        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>
        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>
        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>
        <tr style="font-size: 0.4rem;">
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
            <td style="font-size: 0.4rem; height: 15px"></td>
        </tr>
    </table>

    <table width="100%" style="border-collapse:collapse;text-align:center;margin-top: 5px" border="1px">
        <tr style="font-size: 0.5rem; font-weight: bold; background: #000000">
            <td colspan="8" style="height: 10px; color: white;">OBSERVACIONES</td>
        </tr>
        <tr style="font-size: 0.5rem; font-weight: bold;">
            <td colspan="8" style="text-align: left"></td>
        </tr>

    </table>

    <table width="100%" style="margin-top: 10px; margin-top: 10px">
        <tr>
            @if($correction->order->user_id)
                <td rowspan="6" style="text-align: center;">
                    <img src="" style="width: 180px;height: 90px">
                </td>
            @else
                <td>
                    Aqui va firma del medico
                </td>
            @endif
        </tr>

        <tr>
            <td style="text-align: center; font-size: 0.5rem; width: 250px">
                <p style="margin-top: 100px">Firma del asegurado o apoderado</p>
            </td>

            <td style="text-align: center; font-size: 0.5rem; width: 200px; border: #000000 1px solid;">
                <p style="margin-top: 120px">Huella digital del Asegurado o Apoderado</p>
            </td>
        </tr>
    </table>

</div>


