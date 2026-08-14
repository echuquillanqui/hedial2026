<div class="row text-center">

    <div class="form-group col-sm-12 col-lg-2">
        <label for="consult">HORA</label>
        <input type="time" name="timenefro" class="form-control" value="{{ $nephrology->timenefro }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="date_start">FECHA INICIO</label>
        <input type="date" name="date_start" class="form-control" id="fechaInicioDialisis" onblur="calcularDiferencia()" value="{{ !$nephrology->date_start ? '//' : $nephrology->date_start }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="consult">MOTIVO DE CONSULTA</label>
        <input type="text" name="consult" class="form-control" value="{{ !$nephrology->consult ? 'Control mensual' : $nephrology->consult }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="time_disease">TIEMPO ENFERMEDAD</label>
        <input type="text" name="time_disease" class="form-control" id="resultado" value="{{ !$nephrology->time_disease ? '0' : $nephrology->time_disease}}" >
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <label for="anamnesis">ANAMNESIS</label>
        <input type="text" name="anamnesis"  class="form-control" value="{{ !$nephrology->anamnesis ? 'Paciente con insuficiencia renal terminal, en Hemodiálisis' : $nephrology->anamnesis }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="etiology">ETOLOGIA</label>
        <input type="text" name="etiology" class="form-control" value="{{ !$nephrology->etiology ? 'Nefropatía diabética' : $nephrology->etiology }}">
    </div>

    <div class="form-group col-sm-12 col-lg-3">
        <label for="access">Acceso Vascular actual</label>
        <select name="access" id="access" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$nephrology->access ? '' : $nephrology->access }}" disabled selected>{{ !$nephrology->access ? '' : $nephrology->access }}</option>
            <option value="Fístula Arteriovenosa">Fístula Arteriovenosa</option>
            <option value="Catéter venoso central temporal ">Catéter venoso central temporal </option>
            <option value="Catéter Venoso Central Tunelizado">Catéter Venoso Central Tunelizado</option>
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-3">
        <label for="desc_access">TIPO ACCESO</label>
        <input type="text" name="desc_access" class="form-control" value="{{ !$nephrology->desc_access ? '-' : $nephrology->desc_access }}">
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <label for="symptoms">Signos y Sintomas</label>
        <input type="text" name="symptoms" class="form-control" value="{{ !$nephrology->symptoms ? 'Niega' : $nephrology->symptoms }}">
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <label for="temperature">T°</label>
        <input type="text" name="temperature" class="form-control" value="{{ !$nephrology->temperature ? '0' : $nephrology->temperature }}">
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <label for="pa">PA</label>
        <input type="text" name="pa" class="form-control" value="{{ !$nephrology->pa ? '/' : $nephrology->pa }}">
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <label for="fc">FC</label>
        <input type="text" name="fc" class="form-control" value="{{ !$nephrology->fc ? '0' : $nephrology->fc }}">
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <label for="fr">FR</label>
        <input type="text" name="fr" class="form-control" value="{{ !$nephrology->fr ? '0' : $nephrology->fr }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="peso">PESO</label>
        <input type="text" name="peso" id="peso" class="form-control" value="{{ !$nephrology->peso ? '0' : $nephrology->peso }}" onchange="calcularIMC()">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="talla">TALLA</label>
        <input type="text" name="talla" id="talla" class="form-control" value="{{ !$nephrology->talla ? '1.' : $nephrology->talla }}" onchange="calcularIMC()">
    </div>


    <div class="form-group col-sm-12 col-lg-2">
        <label for="imc">IMC</label>
        <input type="text" name="imc" id="imc" class="form-control" value="{{ $nephrology->imc }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="diuresis">Diuresis</label>
        <input type="text" name="diuresis" class="form-control" value="{{ !$nephrology->diuresis ? '0' : $nephrology->diuresis }}">
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <label for="physical_observation">Observaciones</label>
        <input type="text" name="physical_observation" class="form-control" value="{{ !$nephrology->physical_observation ? 'En aparente regular estado general, lucida, edema (-)' : $nephrology->physical_observation }}">
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <label for="torax_pulmones">Torax y pulmones</label>
        <input type="text" name="torax_pulmones" class="form-control" value="{{ !$nephrology->torax_pulmones ? 'Buen pasaje del murmullo vesicular, no ruidos agregados' : $nephrology->torax_pulmones }}">
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <label for="cardio">Cardiovascular</label>
        <input type="text" name="cardio" class="form-control" value="{{ !$nephrology->cardio ? 'Ruidos cardiacos regulares, de buen tono' : $nephrology->cardio }}">
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <label for="dx1">DX1</label>
        <input type="text" name="dx1" class="form-control" value="{{ !$nephrology->dx1 ? 'INSUFICIENCIA RENAL TERMINAL' : $nephrology->dx1 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="cie1">CIE 10</label>
        <input type="text" name="cie1" class="form-control" value="{{ !$nephrology->cie1 ? 'N18.0' : $nephrology->cie1 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <label for="dx2">DX2</label>
        <input type="text" name="dx2" class="form-control" value="{{ !$nephrology->dx2 ? 'ANEMIA EN OTRAS ENFERMEDADES CRÓNICAS CLASIFICADAS EN OTRA PARTE' : $nephrology->dx2 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="cie2">CIE 10</label>
        <input type="text" name="cie2" class="form-control" value="{{ !$nephrology->cie2 ? 'D63.8' : $nephrology->cie2 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <label for="dx3">DX3</label>
        <input type="text" name="dx3" class="form-control" value="{{ !$nephrology->dx3 ? '' : $nephrology->dx3 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="cie3">CIE 10</label>
        <input type="text" name="cie3" class="form-control" value="{{ !$nephrology->cie3 ? '' : $nephrology->cie3 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <label for="dx4">DX4</label>
        <input type="text" name="dx4" class="form-control" value="{{ !$nephrology->dx4 ? '' : $nephrology->dx4 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="cie4">CIE 10</label>
        <input type="text" name="cie4" class="form-control" value="{{ !$nephrology->cie4 ? '' : $nephrology->cie4 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-3">
        <label for="preescripcion">PRESCRIPCION DE DIALISIS</label>
        <select name="preescripcion" id="preescripcion" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ $nephrology->preescripcion }}" disabled selected>{{ $nephrology->preescripcion }}</option>
            <option value="TRES VECES POR SEMANA">TRES VECES POR SEMANA</option>
            <option value="DOS VECES POR SEMANA">DOS VECES POR SEMANA</option>
            <option value="UNA VEZ POR SEMANA">UNA VEZ POR SEMANA</option>
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="tiempo_hd">TIEMPO HD</label>
        <select name="tiempo_hd" id="tiempo_hd" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ $nephrology->tiempo_hd }}" disabled selected>{{ $nephrology->tiempo_hd }}</option>
            <option value="3.5">3.5</option>
            <option value="3.25">3.25</option>
            <option value="3.75">3.75</option>
            <option value="3.00">3.00</option>
            <option value="4.00">4.00</option>
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="area_filtro">FILTRO</label>
        <select name="area_filtro" id="area_filtro" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ $nephrology->area_filtro }}" disabled selected>{{ $nephrology->area_filtro }}</option>
            <option value="1.8" >1.8</option>
            <option value="1.9" >1.9</option>
            <option value="2">2</option>
            <option value="2.1">2.1</option>
            <option value="1.4">1.4</option>
        </select>
    </div>
</div>
