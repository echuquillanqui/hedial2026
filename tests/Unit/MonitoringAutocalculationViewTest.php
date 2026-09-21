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
}
