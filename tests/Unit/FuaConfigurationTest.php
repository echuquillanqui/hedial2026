<?php

namespace Tests\Unit;

use App\Models\FuaConfiguration;
use PHPUnit\Framework\TestCase;

class FuaConfigurationTest extends TestCase
{
    public function test_referral_facility_name_prefers_ipress_name(): void
    {
        $configuration = new FuaConfiguration([
            'ipress_name' => 'IPRESS configurada',
            'company_name' => 'Razón social configurada',
        ]);

        $this->assertSame('IPRESS configurada', $configuration->referralFacilityName());
    }

    public function test_referral_facility_name_falls_back_to_company_name(): void
    {
        $configuration = new FuaConfiguration([
            'ipress_name' => '',
            'company_name' => 'Razón social configurada',
        ]);

        $this->assertSame('Razón social configurada', $configuration->referralFacilityName());
    }
}
