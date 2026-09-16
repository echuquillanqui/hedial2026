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
}
