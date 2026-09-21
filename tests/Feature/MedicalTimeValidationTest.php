<?php

namespace Tests\Feature;

use App\Models\Medical;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalTimeValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_and_final_medical_times_must_be_unique_within_a_shift(): void
    {
        $sede = Sede::create(['name' => 'Sede de prueba', 'code' => 'TIME', 'is_active' => true]);
        $existing = $this->medicalForShift($sede, 'TIME-EXISTING');
        $medical = $this->medicalForShift($sede, 'TIME-CANDIDATE');
        $existing->update(['hora_inicial' => '07:15', 'hora_final' => '11:15']);

        $this->actingAs(User::factory()->create())
            ->withoutMiddleware()
            ->from(route('medicals.edit', $medical))
            ->put(route('medicals.update', $medical), $this->payload([
                'hora_inicial' => '07:15',
                'hora_final' => '11:15',
            ]))
            ->assertRedirect(route('medicals.edit', $medical))
            ->assertSessionHasErrors(['hora_inicial', 'hora_final']);
    }

    public function test_medical_final_time_must_be_after_the_last_treatment_time(): void
    {
        $sede = Sede::create(['name' => 'Sede de prueba', 'code' => 'TREATMENT', 'is_active' => true]);
        $medical = $this->medicalForShift($sede, 'TIME-TREATMENT');
        Treatment::create([
            'order_id' => $medical->order_id,
            'hora' => '14:30',
            'pa' => '120/80',
        ]);

        $this->actingAs(User::factory()->create())
            ->withoutMiddleware()
            ->from(route('medicals.edit', $medical))
            ->put(route('medicals.update', $medical), $this->payload([
                'hora_inicial' => '11:00',
                'hora_final' => '14:30',
            ]))
            ->assertRedirect(route('medicals.edit', $medical))
            ->assertSessionHasErrors(['hora_final']);
    }

    private function medicalForShift(Sede $sede, string $code): Medical
    {
        $patient = Patient::factory()->create(['sede_id' => $sede->id]);
        $order = Order::create([
            'sede_id' => $sede->id,
            'patient_id' => $patient->id,
            'codigo_unico' => $code,
            'sala' => 'Sala A',
            'turno' => '1',
            'horas_dialisis' => 4,
            'fecha_orden' => '2026-09-21',
        ]);

        return Medical::create(['order_id' => $order->id, 'hora_hd' => 4]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'hora_hd' => 4,
            'uf' => '2.0',
        ], $overrides);
    }
}
