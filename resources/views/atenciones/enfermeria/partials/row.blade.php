<tr>
    <td>
        <input type="time" name="t_hora[]" class="hora-input"
               value="{{ isset($t) ? substr($t->hora, 0, 5) : '' }}">
        <small class="hora-formateada d-block text-muted mt-1" aria-live="polite"></small>
    </td>
    <td style="width: 85px;">
        <input type="text" name="t_pa[]" value="{{ ($isFirst ?? false) ? ($nurse->pa_inicial ?? $order->medical->pa_inicial ?? $t->pa ?? '') : ($t->pa ?? '') }}" placeholder="120/80">
    </td>
    <td style="width: 65px;">
        <input type="number" name="t_fc[]" value="{{ $t->fc ?? '' }}">
    </td>
    <td style="width: 65px;">
        <input type="text" name="t_qb[]" value="{{ $t->qb ?? '' }}">
    </td>
    <td style="width: 65px;">
        <input type="number" step="0.1" name="t_cnd[]" value="{{ $t->cnd ?? '' }}">
    </td>
    <td style="width: 65px;">
        <input type="number" name="t_ra[]" max="0" value="{{ isset($t) && filled($t->ra) ? -abs((int) $t->ra) : '' }}">
    </td>
    <td style="width: 65px;">
        <input type="number" name="t_rv[]" value="{{ $t->rv ?? '' }}">
    </td>
    <td style="width: 65px;">
        <input type="number" name="t_ptm[]" value="{{ $t->ptm ?? '' }}">
    </td>
    <td>
        <input type="text" name="t_obs[]" value="{{ $t->observacion ?? '' }}" 
               class="text-start ps-2" style="width: 100% !important; text-align: left !important;">
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-sm text-danger" onclick="confirmDeleteRow(this)">
            <i class="bi bi-trash3-fill"></i>
        </button>
    </td>
</tr>
