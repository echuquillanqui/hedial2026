<?php

namespace Tests\Feature;

use App\Models\LaboratoryOrder;
use App\Models\Patient;
use App\Models\Profile;
use App\Models\Test;
use App\Models\User;
use Database\Seeders\FissalLaboratorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FissalLaboratoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_has_cumulative_fissal_frequencies(): void
    {
        $this->seed(FissalLaboratorySeeder::class);

        $this->assertSame(24, Test::where('is_fissal', true)->count());
        $this->assertEqualsCanonicalizing(['B', 'M', 'S', 'T'], Test::where('is_fissal', true)->pluck('frequency')->unique()->all());
    }

    public function test_multiple_patient_orders_can_be_generated(): void
    {
        $this->seed(FissalLaboratorySeeder::class);
        $user = User::factory()->create();
        $patients = Patient::factory()->count(2)->create();
        $testIds = Test::whereIn('frequency', ['M', 'B'])->pluck('id')->all();

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('laboratory.orders.store'), [
            'patient_ids' => $patients->pluck('id')->all(), 'period' => 'B',
            'sampled_at' => '2026-08-14', 'test_ids' => $testIds,
        ]);

        $response->assertRedirect(route('laboratory.results.index'));
        $this->assertSame(2, LaboratoryOrder::count());
        $this->assertSame(count($testIds) * 2, LaboratoryOrder::withCount('items')->get()->sum('items_count'));
    }

    public function test_create_order_page_receives_profiles_with_fissal_tests(): void
    {
        $this->seed(FissalLaboratorySeeder::class);
        $user = User::factory()->create();
        $fissalTest = Test::where('is_fissal', true)->firstOrFail();
        $nonFissalTest = Test::create([
            'area_id' => $fissalTest->area_id,
            'name' => 'Examen fuera de FISSAL',
            'unit' => 'mg/dL',
            'type' => 'number',
            'is_fissal' => false,
        ]);
        $profile = Profile::create(['name' => 'Perfil de prueba']);
        $profile->tests()->attach([$fissalTest->id, $nonFissalTest->id]);

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('laboratory.orders.create'));

        $response->assertOk();
        $response->assertViewHas('profiles', function ($profiles) use ($profile, $fissalTest): bool {
            $loadedProfile = $profiles->firstWhere('id', $profile->id);

            return $loadedProfile !== null
                && $loadedProfile->tests->count() === 1
                && $loadedProfile->tests->contains($fissalTest);
        });
    }

    public function test_individual_dialysis_order_generates_the_selected_laboratory_sheet(): void
    {
        $this->seed(FissalLaboratorySeeder::class);
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('orders.store'), [
            'patient_id' => $patient->id,
            'sala' => 'MODULO 1',
            'turno' => '1',
            'horas_dialisis' => 3.5,
            'fecha_orden' => '2026-08-14',
            'laboratory_period' => 'T',
        ]);

        $response->assertRedirect(route('orders.index'));
        $laboratoryOrder = LaboratoryOrder::with('items.test')->firstOrFail();
        $this->assertSame('T', $laboratoryOrder->period);
        $this->assertSame($patient->id, $laboratoryOrder->patient_id);
        $this->assertNotNull($laboratoryOrder->order_id);
        $this->assertNotEmpty($laboratoryOrder->items);
        $this->assertTrue($laboratoryOrder->items->every(fn ($item) => $item->test->frequency === 'T'));
    }

    public function test_bulk_dialysis_orders_allow_a_different_laboratory_period_per_patient(): void
    {
        $this->seed(FissalLaboratorySeeder::class);
        $user = User::factory()->create();
        $patients = Patient::factory()->count(2)->create(['turno' => '1']);

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('orders.store_bulk'), [
            'patient_ids' => $patients->pluck('id')->all(),
            'sala' => 'MODULO 1',
            'fecha_orden' => '2026-08-14',
            'horas_individual' => $patients->mapWithKeys(fn ($patient) => [$patient->id => 3.5])->all(),
            'laboratory_periods' => [
                $patients[0]->id => 'M',
                $patients[1]->id => 'S',
            ],
        ]);

        $response->assertRedirect(route('orders.index'));
        $this->assertEqualsCanonicalizing(['M', 'S'], LaboratoryOrder::pluck('period')->all());
        $this->assertSame(2, LaboratoryOrder::whereNotNull('order_id')->count());
    }
}
