<div class="row text-center">
    <div class="form-group col-sm-12 col-lg-2">
        <label for="anemia">ANEMIA</label>
        <select name="anemia" id="anemia" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ $nephrology->anemia }}" disabled selected>{{ $nephrology->anemia }}</option>
            <option value="SI">SI</option>
            <option value="NO">NO</option>
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="hb">HEMOGLOBINA</label>
        <input type="text" name="hb" class="form-control" value="{{ !$nephrology->hb ? '-' : $nephrology->hb }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="epo2000">EPO 2000</label>
        <input type="text" name="epo2000" class="form-control" value="{{ !$nephrology->epo2000 ? '' : $nephrology->epo2000 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="epo4000">EPO 4000</label>
        <input type="text" name="epo4000" class="form-control" value="{{ !$nephrology->epo4000 ? '' : $nephrology->epo4000 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="vitb12">VITAMINA B12</label>
        <input type="text" name="vitb12" class="form-control" value="{{ !$nephrology->vitb12 ? '' : $nephrology->vitb12 }}">
    </div>
    <div class="form-group col-sm-12 col-lg-2">
        <label for="hierro">HIERRO</label>
        <input type="text" name="hierro" class="form-control" value="{{ !$nephrology->hierro ? '' : $nephrology->hierro }}">
    </div>

    <div class="form-group col-sm-12 col-lg-12">
        <label for="observacion">OBSERVACION</label>
        <input type="text" name="observacion" class="form-control" value="{{ !$nephrology->observacion ? '' : $nephrology->observacion }}">
    </div>

</div>

<div class="row text-center">
    <div class="form-group col-sm-12 col-lg-2">
        <label for="alteracion_meta">ALTERACION METABOLISMO</label>
        <select name="alteracion_meta" id="alteracion_meta" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ $nephrology->alteracion_meta }}" disabled selected>{{ $nephrology->alteracion_meta }}</option>
            <option value="SI">SI</option>
            <option value="NO">NO</option>
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-10">
        <label for="especificacion">ESPECIFICACION</label>
        <input type="text" name="especificacion" class="form-control" value="{{ !$nephrology->especificacion ? '' : $nephrology->especificacion }}">
    </div>

</div>

<div class="row text-center">
    <div class="form-group col-sm-12 col-lg-2">
        <label for="antihipertensivos">ANTIHIPERTENSIVOS</label>
        <select name="antihipertensivos" id="antihipertensivos" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ $nephrology->antihipertensivos }}" disabled selected>{{ $nephrology->antihipertensivos }}</option>
            <option value="SI">SI</option>
            <option value="NO">NO</option>
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-10">
        <label for="especificacion2">ESPECIFICACION</label>
        <input type="text" name="especificacion2" class="form-control" value="{{ !$nephrology->especificacion2 ? '' : $nephrology->especificacion2 }}">
    </div>
</div>

<div class="row">
    <div class="form-group col-sm-12 col-lg-12">
        <label for="otros_med">OTROS</label>

            <textarea class="form-control" id="" name="otros_med" rows="2">
                {{ $nephrology->order->recipe->code1  }}
            </textarea>


    </div>
</div>
