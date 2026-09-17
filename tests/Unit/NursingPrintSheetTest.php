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
}
