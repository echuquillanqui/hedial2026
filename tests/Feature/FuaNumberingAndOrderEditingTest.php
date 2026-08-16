<?php

namespace Tests\Feature;

use App\Models\Fua;
use App\Models\FuaConfiguration;
use App\Models\NephrologyConsultation;
use App\Models\Order;
use App\Models\Patient;
use App\Models\User;
use App\Services\FuaNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuaNumberingAndOrderEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_hemodialysis_and_nephrology_use_one_consecutive_sequence(): void
    {
        $configuration = FuaConfiguration::global();
        $configuration->update([
            'hemodialysis_series' => 'SERIE',
            'hemodialysis_next_number' => 20,
            'nephrology_series' => 'ANTIGUA',
            'nephrology_next_number' => 90,
            'number_length' => 4,
        ]);

        $patient = Patient::factory()->create();
        $hemodialysis = $this->order($patient, Fua::HEMODIALYSIS, 'HD-1');
        $nephrology = $this->order($patient, Fua::NEPHROLOGY, 'NEFRO-1');
        $service = app(FuaNumberService::class);

        $this->assertSame('SERIE-0020', $service->createForOrder($hemodialysis)->number);
        $this->assertSame('SERIE-0021', $service->createForOrder($nephrology)->number);
        $this->assertSame(22, FuaConfiguration::global()->hemodialysis_next_number);
        $this->assertSame(22, FuaConfiguration::global()->nephrology_next_number);
    }

    public function test_generated_nephrology_order_can_be_edited_and_updates_its_consultation_date(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $order = $this->order($patient, Fua::NEPHROLOGY, 'NEFRO-2');
        $consultation = NephrologyConsultation::create([
            'order_id' => $order->id,
            'patient_id' => $patient->id,
            'sede_id' => $patient->sede_id,
            'consultation_date' => '2026-08-16',
        ]);

        $response = $this->actingAs($user)->withoutMiddleware()->put(route('orders.update', $order), [
            'sala' => 'CONSULTA NEFROLÓGICA',
            'turno' => 'N/A',
            'horas_dialisis' => 0.5,
            'fecha_orden' => '2026-08-20',
        ]);

        $response->assertRedirect(route('orders.index'));
        $this->assertSame('2026-08-20', $order->fresh()->fecha_orden);
        $this->assertSame('2026-08-20', $consultation->fresh()->consultation_date->format('Y-m-d'));
    }

    private function order(Patient $patient, string $type, string $code): Order
    {
        return Order::create([
            'patient_id' => $patient->id,
            'sede_id' => $patient->sede_id,
            'codigo_unico' => $code,
            'sala' => $type === Fua::NEPHROLOGY ? 'CONSULTA NEFROLÓGICA' : 'MODULO 1',
            'turno' => '1',
            'attention_type' => $type,
            'laboratory_period' => $type === Fua::HEMODIALYSIS ? 'M' : null,
            'horas_dialisis' => 0.5,
            'fecha_orden' => '2026-08-16',
        ]);
    }
}
