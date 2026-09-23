<?php

namespace Tests\Feature;

use App\Models\Medical;
use App\Models\Nurse;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalMedicationModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_medications_can_be_updated_without_submitting_the_complete_medical_form(): void
    {
        $medical = $this->medical();
        $nurse = Nurse::create(['order_id' => $medical->order_id]);

        $this->actingAs(User::factory()->create())
            ->withoutMiddleware()
            ->from(route('medicals.index'))
            ->patch(route('medicals.medications.update', $medical), [
                'epo2000' => '2',
                'epo4000' => '1',
                'hierro' => '1 ampolla',
                'vitamina_b12' => '0',
                'calcitriol' => '1',
                'heparina' => '2500 UI',
            ])
            ->assertRedirect(route('medicals.index'))
            ->assertSessionHas('success');

        $this->assertSame('2', $medical->fresh()->epo2000);
        $this->assertSame('2500 UI', $medical->fresh()->heparina);
        $this->assertSame('2', $nurse->fresh()->epo2000);
        $this->assertSame('1 ampolla', $nurse->fresh()->hierro);
    }

    public function test_medication_values_are_limited_to_fifty_characters(): void
    {
        $medical = $this->medical();

        $this->actingAs(User::factory()->create())
            ->withoutMiddleware()
            ->patch(route('medicals.medications.update', $medical), [
                'epo2000' => str_repeat('1', 51),
            ])
            ->assertSessionHasErrors('epo2000');

        $this->assertNull($medical->fresh()->epo2000);
    }

    private function medical(): Medical
    {
        $sede = Sede::create(['name' => 'Sede de prueba', 'code' => 'MED', 'is_active' => true]);
        $patient = Patient::factory()->create(['sede_id' => $sede->id]);
        $order = Order::create([
            'sede_id' => $sede->id,
            'patient_id' => $patient->id,
            'codigo_unico' => 'MED-MODAL-001',
            'sala' => 'Sala A',
            'turno' => '1',
            'horas_dialisis' => 4,
            'fecha_orden' => '2026-09-23',
        ]);

        return Medical::create(['order_id' => $order->id, 'hora_hd' => 4]);
    }
}
