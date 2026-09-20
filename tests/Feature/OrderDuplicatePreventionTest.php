<?php

namespace Tests\Feature;

use App\Models\Fua;
use App\Models\Medical;
use App\Models\Order;
use App\Models\Patient;
use App\Models\User;
use App\Support\ClinicalService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDuplicatePreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_attention_from_the_previous_day_does_not_prevent_a_new_order_in_any_shift(): void
    {
        Carbon::setTestNow('2026-09-16 18:00:00');
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['turno' => '1']);
        $this->dailyOrder($patient, '2026-09-16', 'ORD-20260916-ANTERIOR');

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('orders.store'), [
            'patient_id' => $patient->id,
            'turno' => '4',
            'horas_dialisis' => 3.5,
            'fecha_orden' => '2026-09-17',
            'laboratory_period' => null,
        ]);

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('toastr', fn (array $message) => $message['type'] === 'success');
        $this->assertDatabaseHas('orders', [
            'patient_id' => $patient->id,
            'fecha_orden' => '2026-09-17',
            'turno' => '4',
        ]);
        $this->assertStringStartsWith(
            'ORD-20260917-',
            Order::query()->whereDate('fecha_orden', '2026-09-17')->sole()->codigo_unico,
        );
    }

    public function test_individual_generation_preserves_an_existing_daily_hemodialysis_order(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $order = $this->dailyOrder($patient, '2026-09-10', 'ORD-CONSERVAR');
        Medical::create([
            'order_id' => $order->id,
            'problemas_clinicos' => 'Dato clínico ya registrado',
        ]);

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('orders.store'), [
            'patient_id' => $patient->id,
            'turno' => '2',
            'horas_dialisis' => 4,
            'fecha_orden' => '2026-09-10',
            'laboratory_period' => null,
        ]);

        $response->assertRedirect(route('orders.index', ['date' => '2026-09-10']));
        $response->assertSessionHas('warning', fn (string $message) => str_contains($message, 'ORD-CONSERVAR')
            && str_contains($message, '10/09/2026')
            && str_contains($message, 'turno 1'));
        $this->assertSame(1, Order::query()->where('patient_id', $patient->id)->count());
        $this->assertSame('Dato clínico ya registrado', $order->medical->problemas_clinicos);
    }

    public function test_bulk_generation_skips_existing_daily_orders_without_modifying_them(): void
    {
        $user = User::factory()->create();
        $patients = Patient::factory()->count(2)->create();

        foreach ($patients as $index => $patient) {
            $this->dailyOrder($patient, '2026-09-10', 'ORD-EXISTENTE-'.$index);
        }

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('orders.store_bulk'), [
            'patient_ids' => $patients->pluck('id')->all(),
            'fecha_orden' => '2026-09-10',
            'horas_individual' => $patients->mapWithKeys(fn (Patient $patient) => [$patient->id => 3.5])->all(),
            'laboratory_periods' => $patients->mapWithKeys(fn (Patient $patient) => [$patient->id => null])->all(),
        ]);

        $response->assertRedirect(route('orders.index', ['date' => '2026-09-10']));
        $response->assertSessionHas('warning', fn (string $message) => str_contains($message, 'Se omitieron 2 pacientes'));
        $this->assertSame(2, Order::query()->count());
    }

    public function test_duplicate_list_identifies_which_order_contains_clinical_data(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $recordedOrder = $this->dailyOrder($patient, '2026-09-10', 'ORD-CON-DATOS');
        $emptyOrder = $this->dailyOrder($patient, '2026-09-10', 'ORD-VACIA');
        Medical::create([
            'order_id' => $recordedOrder->id,
            'evaluacion' => 'Evaluación que debe recuperarse',
        ]);

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('orders.index', [
            'date' => '2026-09-10',
        ]));

        $response->assertOk();
        $response->assertSee('CON DATOS: CONSERVAR');
        $response->assertSee('VACÍA: PUEDE ELIMINARSE');
        $response->assertSee('ID '.$recordedOrder->id);
        $response->assertSee('ID '.$emptyOrder->id);
    }

    public function test_duplicate_filter_shows_counts_and_excludes_unique_orders(): void
    {
        $user = User::factory()->create();
        $duplicatePatient = Patient::factory()->create();
        $uniquePatient = Patient::factory()->create();
        $this->dailyOrder($duplicatePatient, '2026-09-10', 'ORD-DUPLICADA-1');
        $this->dailyOrder($duplicatePatient, '2026-09-10', 'ORD-DUPLICADA-2');
        $unique = $this->dailyOrder($uniquePatient, '2026-09-10', 'ORD-UNICA');

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('orders.index', [
            'date' => '2026-09-10',
            'duplicates_only' => 1,
        ]));

        $response->assertOk()
            ->assertSee('2</strong> registros', false)
            ->assertSee('1</strong> duplicados adicionales', false)
            ->assertSee('ORD-DUPLICADA-1')
            ->assertSee('ORD-DUPLICADA-2')
            ->assertDontSee($unique->codigo_unico)
            ->assertSee('id="selectPageDuplicates"', false);
    }

    public function test_order_control_only_shows_hemodialysis_orders(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $hemodialysisOrder = $this->dailyOrder($patient, '2026-09-10', 'ORD-HEMODIALISIS');
        $nephrologyOrder = Order::create([
            'patient_id' => $patient->id,
            'sede_id' => $patient->sede_id,
            'codigo_unico' => 'ORD-NEFROLOGIA',
            'attention_type' => ClinicalService::NEPHROLOGY,
            'fecha_orden' => '2026-09-10',
        ]);

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('orders.index', [
            'date' => '2026-09-10',
        ]));

        $response->assertOk()
            ->assertSee($hemodialysisOrder->codigo_unico)
            ->assertDontSee($nephrologyOrder->codigo_unico)
            ->assertSee('1</strong> registros', false)
            ->assertSee('1</strong> pacientes', false);
    }

    public function test_order_control_marks_dialysis_orders_outside_the_patients_daily_sequence(): void
    {
        $user = User::factory()->create();
        $scheduledPatient = Patient::factory()->create(['secuencia' => 'L-M-V']);
        $offSequencePatient = Patient::factory()->create(['secuencia' => 'M-J-S']);
        $scheduledOrder = $this->dailyOrder($scheduledPatient, '2026-09-16', 'ORD-EN-SECUENCIA');
        $offSequenceOrder = $this->dailyOrder($offSequencePatient, '2026-09-16', 'ORD-FUERA-SECUENCIA');

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('orders.index', [
            'date' => '2026-09-16',
        ]));

        $response->assertOk()
            ->assertSee($scheduledOrder->codigo_unico)
            ->assertSee($offSequenceOrder->codigo_unico)
            ->assertSee('1</strong> fuera de secuencia (día L-M-V)', false)
            ->assertSee('FUERA DE SECUENCIA')
            ->assertSee('Paciente M-J-S / día L-M-V')
            ->assertSee('2</strong> registros', false);
    }

    public function test_out_of_sequence_filter_is_detected_from_the_selected_date_and_shows_data_status(): void
    {
        $user = User::factory()->create();
        $scheduledPatient = Patient::factory()->create(['secuencia' => 'L-M-V']);
        $emptyPatient = Patient::factory()->create(['secuencia' => 'M-J-S']);
        $recordedPatient = Patient::factory()->create(['secuencia' => 'M-J-S']);
        $scheduled = $this->dailyOrder($scheduledPatient, '2026-09-16', 'ORD-EN-SECUENCIA');
        $empty = $this->dailyOrder($emptyPatient, '2026-09-16', 'ORD-FUERA-VACIA');
        $recorded = $this->dailyOrder($recordedPatient, '2026-09-16', 'ORD-FUERA-CON-DATOS');
        Medical::create(['order_id' => $recorded->id, 'evaluacion' => 'Atención registrada']);

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('orders.index', [
            'date' => '2026-09-16',
            'out_of_sequence_only' => 1,
        ]));

        $response->assertOk()
            ->assertDontSee($scheduled->codigo_unico)
            ->assertSee($empty->codigo_unico)
            ->assertSee($recorded->codigo_unico)
            ->assertSee('2</strong> fuera de secuencia (día L-M-V)', false)
            ->assertSee('VACÍA: PUEDE ELIMINARSE')
            ->assertSee('CON DATOS: CONSERVAR')
            ->assertSee('deletable-order-checkbox', false);
    }

    public function test_an_order_with_clinical_data_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $order = $this->dailyOrder($patient, '2026-09-10', 'ORD-PROTEGIDA');
        Medical::create([
            'order_id' => $order->id,
            'pa_inicial' => '120/80',
        ]);

        $response = $this->actingAs($user)->withoutMiddleware()->delete(route('orders.destroy', $order));

        $response->assertSessionHas('toastr', fn (array $message) => $message['type'] === 'warning');
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('medicals', ['order_id' => $order->id, 'pa_inicial' => '120/80']);
    }

    public function test_bulk_deletion_removes_only_empty_duplicates(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $recordedOrder = $this->dailyOrder($patient, '2026-09-10', 'ORD-CONSERVADA');
        $emptyOrder = $this->dailyOrder($patient, '2026-09-10', 'ORD-ELIMINADA');
        Medical::create([
            'order_id' => $recordedOrder->id,
            'evaluacion' => 'Información clínica importante',
        ]);

        $response = $this->actingAs($user)->withoutMiddleware()->delete(route('orders.destroy-bulk'), [
            'order_ids' => [$recordedOrder->id, $emptyOrder->id],
        ]);

        $response->assertSessionHas('toastr', function (array $message) {
            return $message['type'] === 'success'
                && str_contains($message['message'], '1 orden(es) vacía(s) eliminada(s)')
                && str_contains($message['message'], 'Se conservaron 1 orden(es)');
        });
        $this->assertDatabaseHas('orders', ['id' => $recordedOrder->id]);
        $this->assertDatabaseMissing('orders', ['id' => $emptyOrder->id]);
        $this->assertDatabaseHas('medicals', [
            'order_id' => $recordedOrder->id,
            'evaluacion' => 'Información clínica importante',
        ]);
    }

    public function test_bulk_deletion_always_leaves_one_order_when_all_duplicates_are_empty(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $first = $this->dailyOrder($patient, '2026-09-10', 'ORD-PRIMERA');
        $second = $this->dailyOrder($patient, '2026-09-10', 'ORD-SEGUNDA');

        $this->actingAs($user)->withoutMiddleware()->delete(route('orders.destroy-bulk'), [
            'order_ids' => [$first->id, $second->id],
        ])->assertSessionHas('toastr', fn (array $message) => $message['type'] === 'success');

        $this->assertSame(1, Order::query()->where('patient_id', $patient->id)->count());
        $this->assertDatabaseHas('orders', ['id' => $first->id]);
    }

    public function test_bulk_deletion_removes_an_empty_out_of_sequence_order_but_protects_one_with_data(): void
    {
        $user = User::factory()->create();
        $emptyPatient = Patient::factory()->create(['secuencia' => 'M-J-S']);
        $recordedPatient = Patient::factory()->create(['secuencia' => 'M-J-S']);
        $empty = $this->dailyOrder($emptyPatient, '2026-09-16', 'ORD-FUERA-VACIA');
        $recorded = $this->dailyOrder($recordedPatient, '2026-09-16', 'ORD-FUERA-CON-DATOS');
        Medical::create(['order_id' => $recorded->id, 'evaluacion' => 'No eliminar']);

        $this->actingAs($user)->withoutMiddleware()->delete(route('orders.destroy-bulk'), [
            'order_ids' => [$empty->id, $recorded->id],
        ])->assertSessionHas('toastr', fn (array $message) => $message['type'] === 'success'
            && str_contains($message['message'], '1 orden(es) vacía(s) eliminada(s)')
            && str_contains($message['message'], 'Se conservaron 1 orden(es)'));

        $this->assertDatabaseMissing('orders', ['id' => $empty->id]);
        $this->assertDatabaseHas('orders', ['id' => $recorded->id]);
        $this->assertDatabaseHas('medicals', ['order_id' => $recorded->id, 'evaluacion' => 'No eliminar']);
    }

    private function dailyOrder(Patient $patient, string $date, string $code): Order
    {
        return Order::create([
            'patient_id' => $patient->id,
            'sede_id' => $patient->sede_id,
            'codigo_unico' => $code,
            'sala' => 'MODULO 1',
            'turno' => '1',
            'es_covid' => false,
            'attention_type' => Fua::HEMODIALYSIS,
            'horas_dialisis' => 3.5,
            'fecha_orden' => $date,
        ]);
    }
}
