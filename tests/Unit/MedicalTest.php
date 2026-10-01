<?php

namespace Tests\Unit;

use App\Models\Medical;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MedicalTest extends TestCase
{
    #[DataProvider('emptyMembranes')]
    public function test_dialyzer_membrane_defaults_to_psf(?string $membrane): void
    {
        $medical = new Medical(['membrana' => $membrane]);

        $this->assertSame('PSF', $medical->dialyzer_membrane);
    }

    public static function emptyMembranes(): array
    {
        return [[null], [''], ['   ']];
    }

    public function test_dialyzer_membrane_keeps_the_recorded_value(): void
    {
        $medical = new Medical(['membrana' => '  PES  ']);

        $this->assertSame('PES', $medical->dialyzer_membrane);
    }
}
