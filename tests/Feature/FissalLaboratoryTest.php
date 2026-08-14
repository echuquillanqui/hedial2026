<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\LaboratoryOrder;
use App\Models\Fua;
use App\Models\Medical;
use App\Models\Nurse;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Profile;
use App\Models\Test;
use App\Models\User;
use App\Models\Treatment;
use App\Http\Controllers\NephrologyConsultationController;
use Database\Seeders\FissalLaboratorySeeder;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FissalLaboratoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_has_cumulative_fissal_frequencies(): void
    {
        $area = Area::create(['name' => 'Catálogo anterior']);
        Test::create(['area_id' => $area->id, 'name' => 'Examen FISSAL obsoleto', 'type' => 'text', 'is_fissal' => true]);
        $this->seed(FissalLaboratorySeeder::class);

        $this->assertSame(24, Test::where('is_fissal', true)->count());
        $this->assertFalse(Test::where('name', 'Examen FISSAL obsoleto')->value('is_fissal'));
        $this->assertEqualsCanonicalizing(['B', 'M', 'S', 'T'], Test::where('is_fissal', true)->pluck('frequency')->unique()->all());
        $this->assertSame('M', Test::where('name', 'Fósforo inorgánico (fosfato)')->value('frequency'));
    }

    public function test_nephrology_auxiliary_exam_blocks_match_the_fissal_catalog(): void
    {
        $groups = NephrologyConsultationController::AUXILIARY_EXAMS;

        $this->assertSame(['Mensual', 'Bimestral', 'Trimestral', 'Semestral'], array_keys($groups));
        $this->assertContains('Nitrógeno ureico (urea pre y post diálisis)', $groups['Mensual']);
        $this->assertContains('Perfil de electrolitos (cloro, sodio y potasio)', $groups['Mensual']);
        $this->assertSame(21, collect($groups)->flatten()->count());
    }

    public function test_nephrology_form_can_select_each_complete_exam_block(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('consultations.create'));

        $response->assertOk();
        $response->assertSeeInOrder(['Mensual', 'Bimestral', 'Trimestral', 'Semestral']);
        preg_match_all('/<input[^>]+data-select-exam-group/', $response->getContent(), $blockToggles);
        $this->assertCount(4, $blockToggles[0]);
    }

    public function test_catalog_only_displays_the_24_fissal_tests(): void
    {
        $this->seed(FissalLaboratorySeeder::class);
        $user = User::factory()->create();
        $fissalTest = Test::where('is_fissal', true)->firstOrFail();
        $nonFissalTest = Test::create([
            'area_id' => $fissalTest->area_id,
            'name' => 'Examen que no pertenece al catálogo',
            'type' => 'text',
            'is_fissal' => false,
        ]);

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('catalog.index'));

        $response->assertOk();
        $response->assertViewHas('tests', fn ($tests): bool => $tests->count() === 24
            && $tests->every(fn (Test $test): bool => $test->is_fissal)
            && ! $tests->contains($nonFissalTest));
        $response->assertDontSee($nonFissalTest->name);
        $response->assertDontSee('Agregar examen');
    }

    public function test_all_fissal_catalog_fields_can_be_updated(): void
    {
        $this->seed(FissalLaboratorySeeder::class);
        $user = User::factory()->create();
        $tests = Test::where('is_fissal', true)->get();
        $target = $tests->first();
        $payload = $tests->mapWithKeys(fn (Test $test) => [$test->id => [
            'area' => $test->is($target) ? 'Área actualizada' : $test->area->name,
            'name' => $test->is($target) ? 'Examen actualizado' : $test->name,
            'unit' => $test->is($target) ? 'u/mL' : $test->unit,
            'reference_value' => $test->is($target) ? '1 - 10' : $test->reference_value,
            'type' => $test->is($target) ? 'select' : $test->type,
            'frequency' => $test->is($target) ? 'S' : $test->frequency,
        ]])->all();

        $response = $this->actingAs($user)->withoutMiddleware()->put(route('catalog.update'), ['tests' => $payload]);

        $response->assertRedirect(route('catalog.index'));
        $target->refresh();
        $this->assertSame('Área actualizada', $target->area->name);
        $this->assertSame('Examen actualizado', $target->name);
        $this->assertSame('u/mL', $target->unit);
        $this->assertSame('1 - 10', $target->reference_value);
        $this->assertSame('select', $target->type);
        $this->assertSame('S', $target->frequency);
        $this->assertTrue($target->is_fissal);
    }

    public function test_multiple_patient_orders_can_be_generated(): void
    {
        $this->seed(FissalLaboratorySeeder::class);
        $user = User::factory()->create();
        $patients = Patient::factory()->count(2)->create();
        $testIds = Test::whereIn('frequency', ['M', 'B'])->pluck('id')->all();

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('laboratory.orders.store'), [
            'patient_ids' => $patients->pluck('id')->all(),
            'schedules' => [
                ['period' => 'M', 'sampled_at' => '2026-08-14'],
                ['period' => 'B', 'sampled_at' => '2026-10-14'],
            ],
        ]);

        $response->assertRedirect(route('laboratory.results.index'));
        $this->assertSame(4, LaboratoryOrder::count());
        $expectedItems = Test::where('frequency', 'M')->count() + count($testIds);
        $this->assertSame($expectedItems * 2, LaboratoryOrder::withCount('items')->get()->sum('items_count'));
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

    public function test_laboratory_generation_defaults_to_the_sequence_for_the_current_day(): void
    {
        Carbon::setTestNow('2026-08-14 09:00:00'); // Viernes
        $user = User::factory()->create();
        $fridayPatient = Patient::factory()->create(['secuencia' => 'L-M-V', 'turno' => '1']);
        $otherPatient = Patient::factory()->create(['secuencia' => 'M-J-S', 'turno' => '1']);

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('laboratory.orders.create'));

        $response->assertOk();
        $response->assertViewHas('sequence', 'L-M-V');
        $response->assertViewHas('shift', null);
        $response->assertViewHas('patients', fn ($patients): bool => $patients->contains($fridayPatient)
            && ! $patients->contains($otherPatient));
        $response->assertSee('value="2026-08-14"', false);

        Carbon::setTestNow();
    }

    public function test_laboratory_patients_can_be_filtered_by_sequence_and_shift(): void
    {
        $user = User::factory()->create();
        $expectedPatient = Patient::factory()->create(['secuencia' => 'M-J-S', 'turno' => '3']);
        Patient::factory()->create(['secuencia' => 'M-J-S', 'turno' => '2']);
        Patient::factory()->create(['secuencia' => 'L-M-V', 'turno' => '3']);

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('laboratory.orders.create', [
            'secuencia' => 'M-J-S',
            'turno' => '3',
        ]));

        $response->assertOk();
        $response->assertViewHas('sequence', 'M-J-S');
        $response->assertViewHas('shift', '3');
        $response->assertViewHas('patients', fn ($patients): bool => $patients->count() === 1
            && $patients->first()->is($expectedPatient));
    }

    public function test_individual_dialysis_order_keeps_laboratory_period_for_fua_without_generating_laboratory_records(): void
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
        $order = Order::with('fua')->firstOrFail();
        $this->assertSame('T', $order->laboratory_period);
        $this->assertSame(Fua::HEMODIALYSIS, $order->attention_type);
        $this->assertSame(Fua::HEMODIALYSIS, $order->fua->type);
        $this->assertSame(0, LaboratoryOrder::count());
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
        $this->assertEqualsCanonicalizing(['M', 'S'], Order::pluck('laboratory_period')->all());
        $this->assertSame(0, LaboratoryOrder::count());
        $this->assertSame(2, Fua::where('type', Fua::HEMODIALYSIS)->count());
    }

    public function test_nephrology_orders_generate_only_orders_and_fuas(): void
    {
        $user = User::factory()->create();
        $patients = Patient::factory()->count(2)->create();

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => $patients->pluck('id')->all(),
            'fecha_orden' => '2026-08-14',
        ]);

        $response->assertRedirect(route('orders.index'));
        $this->assertSame(2, Order::where('attention_type', Fua::NEPHROLOGY)->count());
        $this->assertSame(2, Fua::where('type', Fua::NEPHROLOGY)->count());
        $this->assertSame(0, Medical::count());
        $this->assertSame(0, Nurse::count());
        $this->assertSame(0, Treatment::count());
        $this->assertSame(0, LaboratoryOrder::count());
    }
}
