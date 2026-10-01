<?php

namespace Tests\Unit;

use Tests\TestCase;

class NursingPrintSheetTest extends TestCase
{
    public function test_print_sheet_labels_the_vital_signs_section_correctly(): void
    {
        $view = file_get_contents(resource_path('views/atenciones/enfermeria/_print_sheet.blade.php'));

        $this->assertStringContainsString('FUNCIONES VITALES', $view);
        $this->assertStringNotContainsString('EXAMEN FISICO', $view);
    }

    public function test_print_sheet_only_shows_professional_names_in_signature_lines(): void
    {
        $view = file_get_contents(resource_path('views/atenciones/enfermeria/_print_sheet.blade.php'));

        $this->assertSame(4, substr_count($view, "'showDetails' => false"));
    }

    public function test_print_sheet_leaves_space_for_professional_stamps(): void
    {
        $view = file_get_contents(resource_path('views/atenciones/enfermeria/_print_sheet.blade.php'));

        $this->assertSame(2, substr_count($view, 'height: 100px; vertical-align: bottom'));
        $this->assertSame(2, substr_count($view, 'height: 110px; vertical-align: bottom'));
    }

    public function test_print_sheet_uses_the_default_dialyzer_membrane(): void
    {
        $view = file_get_contents(resource_path('views/atenciones/enfermeria/_print_sheet.blade.php'));

        $this->assertStringContainsString("dialyzer_membrane ?? 'PSF'", $view);
    }

    public function test_nursing_form_displays_the_dialyzer_membrane(): void
    {
        $view = file_get_contents(resource_path('views/atenciones/enfermeria/edit.blade.php'));

        $this->assertStringContainsString('<label>Membrana</label>', $view);
        $this->assertStringContainsString("dialyzer_membrane ?? 'PSF'", $view);
    }
}
