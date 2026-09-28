<?php

namespace Tests\Unit;

use Tests\TestCase;

class MedicalInitialWeightInputTest extends TestCase
{
    public function test_initial_weight_accepts_values_with_two_decimal_places(): void
    {
        $view = file_get_contents(resource_path('views/atenciones/medicina/edit.blade.php'));

        $this->assertMatchesRegularExpression(
            '/<input[^>]+step="0\.01"[^>]+name="peso_inicial"/',
            $view
        );
    }
}
