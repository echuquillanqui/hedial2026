<div class="row text-center mt--4">
    <div class="form-group col-sm-12 col-lg-3">
        <label for="med1">MEDICAMENTOS</label>
        <select name="med1" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->med1 ? 'Tiamina 100 mg Tab' : $recipe->med1 }}" selected>{{ !$recipe->med1 ? 'Tiamina 100 mg Tab' : $recipe->med1 }}</option>
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <label for="code1">CODIGO</label>
        <input type="text" name="code1" id="code1" class="form-control" value="{{ !$recipe->code1 ? '06127' : $recipe->code1}}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <label for="prescripcion1">PRESCRIPCION</label>
        <input type="text" name="prescripcion1" id="prescripcion1" class="form-control" value="{{ !$recipe->prescripcion1 ? '1 TAB VO C/24 horas a las 10am' : $recipe->prescripcion1}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="prescrita1">C. PRESCRITA</label>
        <input type="number" name="prescrita1" id="prescrita1" class="form-control" value="{{ !$recipe->prescrita1 ? '30' : $recipe->prescrita1}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="entregada1">C. ENTREGADA</label>
        <input type="number" name="entregada1" id="entregada1" class="form-control" value="{{ !$recipe->entregada1 ? '30' : $recipe->entregada1}}">
    </div>

</div>

<div class="row text-center mt--3">
    <div class="form-group col-sm-12 col-lg-3">
        <select name="med2" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->med2 ? 'Piridoxina 50mg TAB' : $recipe->med2 }}" selected>{{ !$recipe->med2 ? 'Piridoxina 50mg TAB' : $recipe->med2 }}</option>
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <input type="text" name="code2" id="code2" class="form-control" value="{{ !$recipe->code2 ? '05491' : $recipe->code2}}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="prescripcion2" id="prescripcion2" class="form-control" value="{{ !$recipe->prescripcion2 ? '1 TAB VO C/24 horas a las 10am' : $recipe->prescripcion2}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="prescrita2" id="prescrita2" class="form-control" value="{{ !$recipe->prescrita2 ? '30' : $recipe->prescrita2}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="entregada2" id="entregada2" class="form-control" value="{{ !$recipe->entregada2 ? '30' : $recipe->entregada2}}">
    </div>

</div>

<div class="row text-center mt--3">
    <div class="form-group col-sm-12 col-lg-3">
        <select name="med3" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->med3 ? 'Ácido fólico 500 mcg (0,5 mg) TAB' : $recipe->med3 }}" selected>{{ !$recipe->med3 ? 'Ácido fólico 500 mcg (0,5 mg) TAB' : $recipe->med3 }}</option>
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <input type="text" name="code3" id="code3" class="form-control" value="{{ !$recipe->code3 ? '00200' : $recipe->code3}}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="prescripcion3" id="prescripcion3" class="form-control" value="{{ !$recipe->prescripcion3 ? '1 TAB VO C/24 horas a las 10am' : $recipe->prescripcion3}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="prescrita3" id="prescrita3" class="form-control" value="{{ !$recipe->prescrita3 ? '30' : $recipe->prescrita3}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="entregada3" id="entregada3" class="form-control" value="{{ !$recipe->entregada3 ? '30' : $recipe->entregada3}}">
    </div>

</div>

<div class="row text-center mt--3">
    <div class="form-group col-sm-12 col-lg-3">
        <select name="type" id="obtenerMed4" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->code4 ? '[SELECIONE]' : $recipe->code4 }}" selected disabled>{{ !$recipe->med4 ? '[SELECCIONE]' : $recipe->med4 }}</option>
            @foreach($recipesall as $item)
                <option value="{{ $item->codigo }}">{{ $item->descripcion }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <input type="text" name="code4" id="code4" class="form-control" value="{{ !$recipe->code4 ? '0' : $recipe->code4}}" readonly>
    </div>

    <input type="hidden" name="med4" id="med4" class="form-control" value="{{ !$recipe->med4 ? '0' : $recipe->med4}}" readonly>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="prescripcion4" id="prescripcion4" class="form-control" value="{{ !$recipe->prescripcion4 ? '' : $recipe->prescripcion4}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="prescrita4" id="prescrita4" class="form-control" value="{{ !$recipe->prescrita4 ? '0' : $recipe->prescrita4}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="entregada4" id="entregada4" class="form-control" value="{{ !$recipe->entregada4 ? '0' : $recipe->entregada4}}">
    </div>

</div>

<div class="row text-center mt--3">
    <div class="form-group col-sm-12 col-lg-3">
        <select name="type" id="obtenerMed5" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->code5 ? '[SELECIONE]' : $recipe->code5 }}" selected disabled>{{ !$recipe->med5 ? '[SELECCIONE]' : $recipe->med5 }}</option>
            @foreach($recipesall as $item)
                <option value="{{ $item->codigo }}">{{ $item->descripcion }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <input type="text" name="code5" id="code5" class="form-control" value="{{ !$recipe->code5 ? '0' : $recipe->code5}}" readonly>
    </div>

    <input type="hidden" name="med5" id="med5" class="form-control" value="{{ !$recipe->med5 ? '0' : $recipe->med5}}" readonly>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="prescripcion5" id="prescripcion5" class="form-control" value="{{ !$recipe->prescripcion5 ? '' : $recipe->prescripcion5}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="prescrita5" id="prescrita5" class="form-control" value="{{ !$recipe->prescrita5 ? '0' : $recipe->prescrita5}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="entregada5" id="entregada5" class="form-control" value="{{ !$recipe->entregada5 ? '0' : $recipe->entregada5}}">
    </div>

</div>

<div class="row text-center mt--3">
    <div class="form-group col-sm-12 col-lg-3">
        <select name="type" id="obtenerMed6" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->code6 ? '[SELECIONE]' : $recipe->code6 }}" selected disabled>{{ !$recipe->med6 ? '[SELECCIONE]' : $recipe->med6 }}</option>
            @foreach($recipesall as $item)
                <option value="{{ $item->codigo }}">{{ $item->descripcion }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <input type="text" name="code6" id="code6" class="form-control" value="{{ !$recipe->code6 ? '0' : $recipe->code6}}" readonly>
    </div>

    <input type="hidden" name="med6" id="med6" class="form-control" value="{{ !$recipe->med6 ? '0' : $recipe->med6}}" readonly>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="prescripcion6" id="prescripcion6" class="form-control" value="{{ !$recipe->prescripcion6 ? '' : $recipe->prescripcion6}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="prescrita6" id="prescrita6" class="form-control" value="{{ !$recipe->prescrita6 ? '0' : $recipe->prescrita6}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="entregada6" id="entregada6" class="form-control" value="{{ !$recipe->entregada6 ? '0' : $recipe->entregada6}}">
    </div>

</div>

<div class="row text-center mt--3">
    <div class="form-group col-sm-12 col-lg-3">
        <select name="type" id="obtenerMed7" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->code7 ? '[SELECIONE]' : $recipe->code7 }}" selected disabled>{{ !$recipe->med7 ? '[SELECCIONE]' : $recipe->med7 }}</option>
            @foreach($recipesall as $item)
                <option value="{{ $item->codigo }}">{{ $item->descripcion }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <input type="text" name="code7" id="code7" class="form-control" value="{{ !$recipe->code7 ? '0' : $recipe->code7}}" readonly>
    </div>

    <input type="hidden" name="med7" id="med7" class="form-control" value="{{ !$recipe->med7 ? '0' : $recipe->med7}}" readonly>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="prescripcion7" id="prescripcion7" class="form-control" value="{{ !$recipe->prescripcion7 ? '' : $recipe->prescripcion7}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="prescrita7" id="prescrita7" class="form-control" value="{{ !$recipe->prescrita7 ? '0' : $recipe->prescrita7}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="entregada7" id="entregada7" class="form-control" value="{{ !$recipe->entregada7 ? '0' : $recipe->entregada7}}">
    </div>

</div>

<div class="row text-center mt--3">
    <div class="form-group col-sm-12 col-lg-3">
        <select name="type" id="obtenerMed8" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->code8 ? '[SELECIONE]' : $recipe->code8 }}" selected disabled>{{ !$recipe->med8 ? '[SELECCIONE]' : $recipe->med8 }}</option>
            @foreach($recipesall as $item)
                <option value="{{ $item->codigo }}">{{ $item->descripcion }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <input type="text" name="code8" id="code8" class="form-control" value="{{ !$recipe->code8 ? '0' : $recipe->code8}}" readonly>
    </div>

    <input type="hidden" name="med8" id="med8" class="form-control" value="{{ !$recipe->med8 ? '0' : $recipe->med8}}" readonly>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="prescripcion8" id="prescripcion8" class="form-control" value="{{ !$recipe->prescripcion8 ? '' : $recipe->prescripcion8}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="prescrita8" id="prescrita8" class="form-control" value="{{ !$recipe->prescrita8 ? '0' : $recipe->prescrita8}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="entregada8" id="entregada8" class="form-control" value="{{ !$recipe->entregada8 ? '0' : $recipe->entregada8}}">
    </div>

</div>

<div class="row text-center mt--3">
    <div class="form-group col-sm-12 col-lg-3">
        <select name="type" id="obtenerMed9" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->code9 ? '[SELECIONE]' : $recipe->code9 }}" selected disabled>{{ !$recipe->med9 ? '[SELECCIONE]' : $recipe->med9 }}</option>
            @foreach($recipesall as $item)
                <option value="{{ $item->codigo }}">{{ $item->descripcion }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <input type="text" name="code9" id="code9" class="form-control" value="{{ !$recipe->code9 ? '0' : $recipe->code9}}" readonly>
    </div>

    <input type="hidden" name="med9" id="med9" class="form-control" value="{{ !$recipe->med9 ? '0' : $recipe->med9}}" readonly>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="prescripcion9" id="prescripcion9" class="form-control" value="{{ !$recipe->prescripcion9 ? '' : $recipe->prescripcion9}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="prescrita9" id="prescrita9" class="form-control" value="{{ !$recipe->prescrita9 ? '0' : $recipe->prescrita9}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="entregada9" id="entregada9" class="form-control" value="{{ !$recipe->entregada9 ? '0' : $recipe->entregada9}}">
    </div>

</div>

<div class="row text-center mt--3">
    <div class="form-group col-sm-12 col-lg-3">
        <select name="type" id="obtenerMed10" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->code10 ? '[SELECIONE]' : $recipe->code10 }}" selected disabled>{{ !$recipe->med10 ? '[SELECCIONE]' : $recipe->med10 }}</option>
            @foreach($recipesall as $item)
                <option value="{{ $item->codigo }}">{{ $item->descripcion }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <input type="text" name="code10" id="code10" class="form-control" value="{{ !$recipe->code10 ? '0' : $recipe->code10}}" readonly>
    </div>

    <input type="hidden" name="med10" id="med10" class="form-control" value="{{ !$recipe->med10 ? '0' : $recipe->med10}}" readonly>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="prescripcion10" id="prescripcion10" class="form-control" value="{{ !$recipe->prescripcion10 ? '' : $recipe->prescripcion10}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="prescrita10" id="prescrita10" class="form-control" value="{{ !$recipe->prescrita10 ? '0' : $recipe->prescrita10}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="entregada10" id="entregada10" class="form-control" value="{{ !$recipe->entregada10 ? '0' : $recipe->entregada10}}">
    </div>

</div>

<div class="row text-center mt--3">
    <div class="form-group col-sm-12 col-lg-3">
        <select name="type" id="obtenerMed11" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->code11 ? '[SELECIONE]' : $recipe->code11 }}" selected disabled>{{ !$recipe->med11 ? '[SELECCIONE]' : $recipe->med11 }}</option>
            @foreach($recipesall as $item)
                <option value="{{ $item->codigo }}">{{ $item->descripcion }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <input type="text" name="code11" id="code11" class="form-control" value="{{ !$recipe->code11 ? '0' : $recipe->code11}}" readonly>
    </div>

    <input type="hidden" name="med11" id="med11" class="form-control" value="{{ !$recipe->med11 ? '0' : $recipe->med11}}" readonly>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="prescripcion11" id="prescripcion11" class="form-control" value="{{ !$recipe->prescripcion11 ? '' : $recipe->prescripcion11}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="prescrita11" id="prescrita11" class="form-control" value="{{ !$recipe->prescrita11 ? '0' : $recipe->prescrita11}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="entregada11" id="entregada11" class="form-control" value="{{ !$recipe->entregada11 ? '0' : $recipe->entregada11}}">
    </div>

</div>

<div class="row text-center mt--3">
    <div class="form-group col-sm-12 col-lg-3">
        <select name="type" id="obtenerMed12" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->code12 ? '[SELECIONE]' : $recipe->code12 }}" selected disabled>{{ !$recipe->med12 ? '[SELECCIONE]' : $recipe->med12 }}</option>
            @foreach($recipesall as $item)
                <option value="{{ $item->codigo }}">{{ $item->descripcion }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <input type="text" name="code12" id="code12" class="form-control" value="{{ !$recipe->code12 ? '0' : $recipe->code12}}" readonly>
    </div>

    <input type="hidden" name="med12" id="med12" class="form-control" value="{{ !$recipe->med12 ? '0' : $recipe->med12}}" readonly>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="prescripcion12" id="prescripcion12" class="form-control" value="{{ !$recipe->prescripcion12 ? '' : $recipe->prescripcion12}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="prescrita12" id="prescrita12" class="form-control" value="{{ !$recipe->prescrita12 ? '0' : $recipe->prescrita12}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="entregada12" id="entregada12" class="form-control" value="{{ !$recipe->entregada12 ? '0' : $recipe->entregada12}}">
    </div>

</div>

<div class="row text-center mt--3">
    <div class="form-group col-sm-12 col-lg-3">
        <select name="type" id="obtenerMed13" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->code13 ? '[SELECIONE]' : $recipe->code13 }}" selected disabled>{{ !$recipe->med13 ? '[SELECCIONE]' : $recipe->med13 }}</option>
            @foreach($recipesall as $item)
                <option value="{{ $item->codigo }}">{{ $item->descripcion }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <input type="text" name="code13" id="code13" class="form-control" value="{{ !$recipe->code13 ? '0' : $recipe->code13}}" readonly>
    </div>

    <input type="hidden" name="med13" id="med13" class="form-control" value="{{ !$recipe->med13 ? '0' : $recipe->med13}}" readonly>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="prescripcion13" id="prescripcion13" class="form-control" value="{{ !$recipe->prescripcion13 ? '' : $recipe->prescripcion13}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="prescrita13" id="prescrita13" class="form-control" value="{{ !$recipe->prescrita13 ? '0' : $recipe->prescrita13}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="entregada13" id="entregada13" class="form-control" value="{{ !$recipe->entregada13 ? '0' : $recipe->entregada13}}">
    </div>

</div>

<div class="row text-center mt--3">
    <div class="form-group col-sm-12 col-lg-3">
        <select name="type" id="obtenerMed14" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value="{{ !$recipe->code14 ? '[SELECIONE]' : $recipe->code14 }}" selected disabled>{{ !$recipe->med14 ? '[SELECCIONE]' : $recipe->med14 }}</option>
            @foreach($recipesall as $item)
                <option value="{{ $item->codigo }}">{{ $item->descripcion }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <input type="text" name="code14" id="code14" class="form-control" value="{{ !$recipe->code14 ? '0' : $recipe->code14}}" readonly>
    </div>

    <input type="hidden" name="med14" id="med14" class="form-control" value="{{ !$recipe->med14 ? '0' : $recipe->med14}}" readonly>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="prescripcion14" id="prescripcion14" class="form-control" value="{{ !$recipe->prescripcion14 ? '' : $recipe->prescripcion14}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="prescrita14" id="prescrita14" class="form-control" value="{{ !$recipe->prescrita14 ? '0' : $recipe->prescrita14}}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="number" name="entregada14" id="entregada14" class="form-control" value="{{ !$recipe->entregada14 ? '0' : $recipe->entregada14}}">
    </div>

</div>
