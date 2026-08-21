<?php

namespace Tests\Feature;

use App\Models\Fua;
use App\Models\Medical;
use App\Models\Nurse;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_histories_filters_daily_attentions_and_offers_correction_links(): void
    {
        [$user, $sede, $order] = $this->auditScenario();

        $response = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('audit.histories', ['date' => today()->toDateString(), 'modulo' => 2, 'turno' => 1]));

        $response->assertOk()
            ->assertSee('PACIENTE AUDITADO')
            ->assertSee(route('medicals.edit', $order->medical), false)
            ->assertSee(route('nurses.edit', $order->nurse), false)
            ->assertSee('Enfermería y tratamiento');
    }

    public function test_fissal_view_uses_treatment_times_and_unpadded_fua_correlative(): void
    {
        [$user, $sede] = $this->auditScenario();

        $response = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('audit.fissal', ['date' => today()->toDateString()]));

        $response->assertOk()
            ->assertSee('07:15')
            ->assertSee('11:30')
            ->assertSee('LICENCIADA INICIO')
            ->assertSee('LICENCIADA FINAL')
            ->assertSee('NEFROLOGO RESPONSABLE')
            ->assertSee('>1</td>', false)
            ->assertDontSee('0000001');
    }

    private function auditScenario(): array
    {
        $user = User::factory()->create();
        $nurseStart = User::factory()->create(['name' => 'LICENCIADA INICIO']);
        $nurseEnd = User::factory()->create(['name' => 'LICENCIADA FINAL']);
        $doctor = User::factory()->create(['name' => 'NEFROLOGO RESPONSABLE']);
        $sede = Sede::create(['name' => 'Sede auditoría', 'code' => 'AUD', 'is_active' => true]);
        $user->sedes()->attach($sede);
        $patient = Patient::factory()->create(['sede_id' => $sede->id, 'first_name' => 'PACIENTE AUDITADO']);
        $order = Order::create([
            'sede_id' => $sede->id,
            'patient_id' => $patient->id,
            'codigo_unico' => 'ORD-AUD-1',
            'sala' => 'MODULO 2',
            'turno' => '1',
            'horas_dialisis' => 4,
            'fecha_orden' => today(),
            'attention_type' => 'HEMODIALYSIS',
        ]);
        Medical::create(['order_id' => $order->id, 'hora_hd' => 4, 'uf' => '2', 'usuario_que_inicia_hd' => $doctor->id]);
        Nurse::create([
            'order_id' => $order->id,
            'epo2000' => '1',
            'epo4000' => '2',
            'vitamina_b12' => '3',
            'hierro' => '4',
            'calcitriol' => '5',
            'enfermero_que_inicia_id' => $nurseStart->id,
            'enfermero_que_finaliza_id' => $nurseEnd->id,
        ]);
        Treatment::create(['order_id' => $order->id, 'hora' => '11:30']);
        Treatment::create(['order_id' => $order->id, 'hora' => '07:15']);
        Fua::create(['order_id' => $order->id, 'type' => Fua::HEMODIALYSIS, 'series' => '0000247', 'correlative' => 1, 'number' => '0000247-0000001']);

        return [$user, $sede, $order];
    }
}
