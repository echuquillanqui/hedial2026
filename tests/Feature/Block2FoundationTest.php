<?php

namespace Tests\Feature;

use App\Models\Sede;
use App\Models\User;
use App\Support\ClinicalService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Block2FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cpms_catalog_has_the_homologated_codes(): void
    {
        $this->assertSame('90937', ClinicalService::cpms(ClinicalService::HEMODIALYSIS));
        $this->assertSame('99215', ClinicalService::cpms(ClinicalService::NEPHROLOGY));
        $this->assertSame('99209', ClinicalService::cpms(ClinicalService::NUTRITION));
        $this->assertSame('99207', ClinicalService::cpms(ClinicalService::PSYCHOLOGY));
        $this->assertSame('99210', ClinicalService::cpms(ClinicalService::SOCIAL_WORK));
    }

    public function test_sector_roles_are_idempotent_and_keep_exclusive_permissions(): void
    {
        Sede::query()->create(['name' => 'Sede de prueba', 'code' => 'TST']);
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $nutritionist = User::query()->where('username', 'nutricionista')->firstOrFail();
        $psychologist = User::query()->where('username', 'psicologo')->firstOrFail();
        $socialWorker = User::query()->where('username', 'trabajo.social')->firstOrFail();

        $this->assertTrue($nutritionist->can('nutrition.create'));
        $this->assertTrue($nutritionist->can('laboratory.results.view'));
        $this->assertFalse($nutritionist->can('laboratory.results.update'));
        $this->assertFalse($nutritionist->can('psychology.create'));
        $this->assertFalse($nutritionist->can('social_work.create'));
        $this->assertFalse($nutritionist->can('fua.configuration.manage'));

        $this->assertTrue($psychologist->can('psychology.eq5d.create'));
        $this->assertFalse($psychologist->can('nutrition.create'));
        $this->assertFalse($psychologist->can('social_work.create'));

        $this->assertTrue($socialWorker->can('social_work.create'));
        $this->assertFalse($socialWorker->can('nutrition.create'));
        $this->assertFalse($socialWorker->can('psychology.create'));

        $this->assertSame(1, User::query()->where('username', 'nutricionista')->count());
        $this->assertSame(1, User::query()->where('username', 'psicologo')->count());
        $this->assertSame(1, User::query()->where('username', 'trabajo.social')->count());
    }

    public function test_nutritionist_can_read_laboratory_but_cannot_manage_it_or_fua_configuration(): void
    {
        $sede = Sede::query()->create(['name' => 'Sede de prueba', 'code' => 'TST']);
        $this->seed(RolesAndPermissionsSeeder::class);
        $nutritionist = User::query()->where('username', 'nutricionista')->firstOrFail();

        $session = ['current_sede_id' => $sede->id, 'current_sede_name' => $sede->name];

        $this->actingAs($nutritionist)->withSession($session)
            ->get(route('laboratory.results.index'))->assertOk();
        $this->actingAs($nutritionist)->withSession($session)
            ->get(route('laboratory.orders.create'))->assertForbidden();
        $this->actingAs($nutritionist)->withSession($session)
            ->get(route('catalog.index'))->assertForbidden();
        $this->actingAs($nutritionist)->withSession($session)
            ->get(route('fuas.configuration.edit'))->assertForbidden();
        $this->actingAs($nutritionist)->withSession($session)
            ->get(route('medicals.index'))->assertForbidden();
        $this->actingAs($nutritionist)->withSession($session)
            ->get(route('nurses.index'))->assertForbidden();
    }
}
