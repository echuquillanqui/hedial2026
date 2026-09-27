<?php

namespace Tests\Unit;

use Tests\TestCase;

class MonitoringAutocalculationViewTest extends TestCase
{
    public function test_autocalculation_reuses_monitoring_rows_instead_of_erasing_clinical_values(): void
    {
        $view = file_get_contents(resource_path('views/atenciones/enfermeria/edit.blade.php'));

        $this->assertStringNotContainsString("tbody.innerHTML = '';", $view);
        $this->assertStringContainsString('let fila = tbody.rows[index];', $view);
        $this->assertStringContainsString("fila.querySelector('.hora-input').value = hora;", $view);
    }

    public function test_nursing_form_refreshes_an_expired_csrf_token_and_retries_once(): void
    {
        $view = file_get_contents(resource_path('views/atenciones/enfermeria/edit.blade.php'));

        $this->assertStringContainsString('response.status === 419 && reintentar', $view);
        $this->assertStringContainsString("route('nurses.csrf-token')", $view);
        $this->assertStringContainsString('return guardarAtencion(form, false);', $view);
    }

    public function test_nursing_form_synchronizes_initial_values_and_negative_arterial_pressure(): void
    {
        $view = file_get_contents(resource_path('views/atenciones/enfermeria/edit.blade.php'));

        $this->assertStringContainsString('function sincronizarPaInicial()', $view);
        $this->assertStringContainsString('pesoInicial - (ufMililitros / 1000)', $view);
        $this->assertStringContainsString('-Math.abs(Number.parseInt(input.value, 10))', $view);
    }

    public function test_closure_values_are_derived_and_final_weight_is_visually_compared_with_dry_weight(): void
    {
        $view = file_get_contents(resource_path('views/atenciones/enfermeria/edit.blade.php'));

        $this->assertStringContainsString('function sincronizarPaFinal()', $view);
        $this->assertStringContainsString("filter(isFilled).pop()", $view);
        $this->assertStringContainsString('function actualizarEstadoPesoFinal()', $view);
        $this->assertStringContainsString("'final-weight-match'", $view);
        $this->assertStringContainsString("'final-weight-warning'", $view);
    }

    public function test_monitoring_times_show_the_correct_twelve_hour_period_across_noon(): void
    {
        $view = file_get_contents(resource_path('views/atenciones/enfermeria/edit.blade.php'));
        $row = file_get_contents(resource_path('views/atenciones/enfermeria/partials/row.blade.php'));

        $this->assertStringContainsString("const periodo = hora24 >= 12 ? 'PM' : 'AM';", $view);
        $this->assertStringContainsString('const hora12 = hora24 % 12 || 12;', $view);
        $this->assertStringContainsString('actualizarHoraFormateada(fila.querySelector', $view);
        $this->assertStringContainsString('hora-formateada', $row);
    }

    public function test_manual_monitoring_times_are_inferred_as_afternoon_hours_after_noon(): void
    {
        $view = file_get_contents(resource_path('views/atenciones/enfermeria/edit.blade.php'));

        $this->assertStringContainsString('function normalizarHoraPosterior(input)', $view);
        $this->assertStringContainsString('normalizarHoraPosterior(event.target);', $view);
        $this->assertStringContainsString('const alternativaPm = horaIngresada + 12 * 60;', $view);
        $this->assertStringContainsString('if (avancePm <= avanceAm)', $view);
        $this->assertStringContainsString('data-start-time=', $view);
    }

    public function test_five_available_start_times_replace_the_old_explanatory_text(): void
    {
        $view = file_get_contents(resource_path('views/atenciones/enfermeria/edit.blade.php'));

        $this->assertStringNotContainsString('Horas programadas:', $view);
        $this->assertStringContainsString('@foreach($horasSugeridas as $horaSugerida)', $view);
        $this->assertStringContainsString('@disabled($horaOcupada)', $view);
        $this->assertStringContainsString('function seleccionarHoraSugerida(button)', $view);
        $this->assertStringContainsString('primeraHora.value = button.dataset.hora;', $view);
    }
}
