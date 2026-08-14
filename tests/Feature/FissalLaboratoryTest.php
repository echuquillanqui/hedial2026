<?php

namespace Tests\Feature;

use App\Models\LaboratoryOrder;
use App\Models\Patient;
use App\Models\Test;
use App\Models\User;
use Database\Seeders\FissalLaboratorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
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

    public function test_laboratory_navigation_exposes_results_and_fua_options_without_manual_order_creation(): void
    {
        $user = User::factory()->create();
        Permission::create(['name' => 'orders.view']);
        $user->givePermissionTo('orders.view');

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('laboratory.results.index'));
        $response->assertOk();
        $response->assertSee('Resultados');
        $response->assertSee('FUA generadas');
        $response->assertSee('Configuración FUA');
        $response->assertDontSee('Nueva orden de laboratorio');
        $response->assertDontSee('Nueva orden');
        $this->assertFalse(Route::has('laboratory.orders.create'));
        $this->assertFalse(Route::has('laboratory.orders.store'));
    }

    public function test_individual_dialysis_order_generates_cumulative_exams_for_selected_period(): void
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
        $this->assertEqualsCanonicalizing(
            ['M', 'B', 'T'],
            $laboratoryOrder->items->pluck('test.frequency')->unique()->all()
        );
        $this->assertSame(
            Test::where('is_fissal', true)->whereIn('frequency', ['M', 'B', 'T'])->count(),
            $laboratoryOrder->items->count()
        );
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

        $monthlyOrder = LaboratoryOrder::with('items.test')->where('period', 'M')->firstOrFail();
        $semesterOrder = LaboratoryOrder::with('items.test')->where('period', 'S')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            ['M'],
            $monthlyOrder->items->pluck('test.frequency')->unique()->all()
        );
        $this->assertEqualsCanonicalizing(
            ['M', 'B', 'T', 'S'],
            $semesterOrder->items->pluck('test.frequency')->unique()->all()
        );
        $this->assertSame(Test::where('is_fissal', true)->count(), $semesterOrder->items->count());
    }
}
