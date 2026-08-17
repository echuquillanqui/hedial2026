<?php

namespace Tests\Feature;

use App\Models\Nurse;
use App\Models\NurseModuleAssignment;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NurseModuleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_nursing_professional_can_select_the_module_for_today(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();

        $response = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->post(route('nurses.module-assignment.store'), ['module' => 3]);

        $response->assertRedirect(route('nurses.index'));
        $this->assertDatabaseHas('nurse_module_assignments', [
            'user_id' => $user->id,
            'sede_id' => $sede->id,
            'work_date' => today()->toDateString(),
            'module' => 3,
        ]);
    }

    public function test_nursing_view_automatically_uses_daily_module_and_ignores_query_override(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();
        $moduleOneNurse = $this->nurseForModule($sede, 1, 'PACIENTE-MODULO-UNO');
        $moduleTwoNurse = $this->nurseForModule($sede, 2, 'PACIENTE-MODULO-DOS');

        NurseModuleAssignment::create([
            'user_id' => $user->id,
            'sede_id' => $sede->id,
            'work_date' => today(),
            'module' => 2,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.index', ['modulo' => 1]));

        $response->assertOk()
            ->assertSee($moduleTwoNurse->order->patient->first_name)
            ->assertDontSee($moduleOneNurse->order->patient->first_name)
            ->assertSee('Módulo asignado para hoy: MÓDULO 2');
    }

    public function test_nursing_view_is_empty_until_a_module_is_selected(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();
        $nurse = $this->nurseForModule($sede, 1, 'PACIENTE-SIN-ASIGNACION');

        $response = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.index'));

        $response->assertOk()
            ->assertSee('Seleccione su módulo')
            ->assertSee('data-bs-backdrop="static"', false)
            ->assertSee('data-bs-keyboard="false"', false)
            ->assertSee('data-auto-show="true"', false)
            ->assertSee('openRequiredModuleModal', false)
            ->assertSee("backdrop: 'static'", false)
            ->assertDontSee($nurse->order->patient->first_name);
    }

    private function nursingUserAndSede(): array
    {
        $user = User::factory()->create([
            'profession' => 'ENFERMERA',
            'license_number' => 'CEP 12345',
        ]);
        $sede = Sede::create(['name' => 'Sede de prueba', 'code' => 'TEST', 'is_active' => true]);
        $user->sedes()->attach($sede);

        return [$user, $sede];
    }

    private function nurseForModule(Sede $sede, int $module, string $patientName): Nurse
    {
        $patient = Patient::factory()->create([
            'sede_id' => $sede->id,
            'first_name' => $patientName,
        ]);
        $order = Order::create([
            'sede_id' => $sede->id,
            'patient_id' => $patient->id,
            'codigo_unico' => 'ORD-'.$module.'-'.uniqid(),
            'sala' => 'MODULO '.$module,
            'turno' => '1',
            'horas_dialisis' => 3,
            'fecha_orden' => today(),
        ]);

        return Nurse::create(['order_id' => $order->id]);
    }
}
