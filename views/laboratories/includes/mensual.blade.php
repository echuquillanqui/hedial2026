<div class="row text-center">

    <div class="form-group col-sm-12 col-lg-4">
        <label for="description1">DESCRIPCION</label>
        <input type="text" name="description1" id="description1" class="form-control" value="{{ !$laboratory->description1 ? 'Nitrógeno ureico; cuantitativo (Urea sérica)' : $laboratory->description1 }}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="code1">CODIGO</label>
        <input type="text" name="code1" id="code1" class="form-control" value="{{ !$laboratory->code1 ? '84520' : $laboratory->code1 }}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="cant1">CANTIDAD</label>
        <input type="text" name="cant1" class="form-control" value="{{ !$laboratory->cant1 ? '2' : $laboratory->cant1 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="pre">PRE</label>
        <input type="text" name="pre" class="form-control" value="{{ !$laboratory->pre ? '0' : $laboratory->pre }}" tabindex="1">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="post">POST</label>
        <input type="text" name="post" class="form-control" value="{{ !$laboratory->post ? '0' : $laboratory->post }}" tabindex="2">
    </div>

</div>

<div class="row text-center">

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="description2" id="description2" class="form-control" value="{{ !$laboratory->description2 ? 'Hematocrito' : $laboratory->description2 }}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="text" name="code2" id="code2" class="form-control" value="{{ !$laboratory->code2 ? '85014' : $laboratory->code2 }}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="text" name="cant2" class="form-control" value="{{ !$laboratory->cant2 ? '1' : $laboratory->cant2 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="result2" class="form-control" value="{{ !$laboratory->result2 ? '0' : $laboratory->result2 }}" tabindex="3">
    </div>

</div>

<div class="row text-center">

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="description3" class="form-control" value="{{ !$laboratory->description3 ? 'Hemoglobina' : $laboratory->description3 }}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="text" name="code3" class="form-control" value="{{ !$laboratory->code3 ? '85018' : $laboratory->code3 }}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="text" name="cant3" class="form-control" value="{{ !$laboratory->cant3 ? '1' : $laboratory->cant3 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="result3" class="form-control" value="{{ !$laboratory->result3 ? '0' : $laboratory->result3 }}" tabindex="4">
    </div>

</div>

<div class="row text-center mt--3">

    <div class="form-group col-sm-12 col-lg-3">
        <label for="description4">EXAMENES</label>
        <input type="text" name="description4" id="description4" class="form-control" value="{{ !$laboratory->description4 ? 'Perfil de electrolitos (Cloro, Sodio y Potasio).' : $laboratory->description4 }}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-1">
        <label for="code4">CODIGO</label>
        <input type="text" name="code4" id="code4" class="form-control" value="{{ !$laboratory->code4 ? '80051' : $laboratory->code4 }}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="cant4">CANTIDAD</label>
        <input type="number" name="cant4" class="form-control" value="{{ !$laboratory->cant4 ? '1' : $laboratory->cant4 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="cloro">CLORO</label>
        <input type="text" name="cloro" class="form-control" value="{{ !$laboratory->cloro ? '0' : $laboratory->cloro }}" tabindex="5">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="sodio">SODIO</label>
        <input type="text" name="sodio" class="form-control" value="{{ !$laboratory->sodio ? '0' : $laboratory->sodio }}" tabindex="6">
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <label for="potasio">POTASIO</label>
        <input type="text" name="potasio" class="form-control" value="{{ !$laboratory->potasio ? '0' : $laboratory->potasio }}" tabindex="7">
    </div>

</div>

<div class="row text-center">

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="description5" id="description5" class="form-control" value="{{ !$laboratory->description5 ? 'Dosaje de Fósforo inorgánico (fosfato)' : $laboratory->description5 }}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="text" name="code5" id="code5" class="form-control" value="{{ !$laboratory->code5 ? '84100' : $laboratory->code5 }}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="text" name="cant5" class="form-control" value="{{ !$laboratory->cant5 ? '1' : $laboratory->cant5 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="result5" class="form-control" value="{{ !$laboratory->result5 ? '0' : $laboratory->result5 }}" tabindex="8">
    </div>

</div>

<div class="row text-center">

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="description6" id="description6" class="form-control" value="{{ !$laboratory->description6 ? 'Dosaje de Calcio; total' : $laboratory->description6 }}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="text" name="code6" id="code6" class="form-control" value="{{ !$laboratory->code6 ? '82310' : $laboratory->code6 }}" readonly>
    </div>

    <div class="form-group col-sm-12 col-lg-2">
        <input type="text" name="cant6" class="form-control" value="{{ !$laboratory->cant6 ? '1' : $laboratory->cant6 }}">
    </div>

    <div class="form-group col-sm-12 col-lg-4">
        <input type="text" name="result6" class="form-control" value="{{ !$laboratory->result6 ? '0' : $laboratory->result6 }}" tabindex="9">
    </div>

</div>
