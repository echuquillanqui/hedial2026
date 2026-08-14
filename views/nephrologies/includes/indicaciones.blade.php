<div class="row text-center">
    <div class="form-group col-sm-12 col-lg-12">
        <label for="solicitud">INDICACIONES DE EXAMENES AUXILIARES - SE SOLICITA:</label>
        <textarea class="form-control" id="" name="solicitud" rows="2">
            {{ !$nephrology->solicitud ? 'Úrea sérica pre y post hemodiálisis, hemoglobina, hematocrito, perfil de electrolitos (cloro, sodio y potasio), fósforo inorgánico, calcio total,
Aspartato amino transferasa (AST) (SGOT), Alanina amino Transferasa (ALT) (SGPT).
                ' : $nephrology->solicitud }}
        </textarea>
    </div>

    <div class="form-group col-sm-12 col-lg-3">
        <label for="date_lab">FECHA PROXIMO LABORATORIO</label>
        <input type="date" name="date_lab" class="form-control" value="{{ !$nephrology->date_lab ? '//' : $nephrology->date_lab }}">
    </div>

    <div class="form-group col-sm-12 col-lg-3">
        <label for="date_appointment">FECHA PROXIMA CITA</label>
        <input type="date" name="date_appointment" class="form-control" value="{{ !$nephrology->date_appointment ? '//' : $nephrology->date_appointment }}">
    </div>

    <div class="form-group col-sm-12 col-lg-3">
        <label for="date_order">FECHA DE ORDEN</label>
        <input type="date" name="date_order" class="form-control" value="{{ !$nephrology->date_order ? '//' : $nephrology->date_order }}" readonly>
    </div>
</div>
