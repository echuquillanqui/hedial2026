<?php

namespace Tests\Feature;

use App\Models\Fua;
use App\Models\Medical;
use App\Models\Order;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDuplicatePreventionTest extends TestCase
{
    use RefreshDatabase;

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
        $response->assertSessionHas('warning', fn (string $message) => str_contains($message, 'ORD-CONSERVAR'));
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
