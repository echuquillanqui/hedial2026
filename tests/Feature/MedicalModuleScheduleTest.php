<?php

namespace Tests\Feature;

use App\Models\Medical;
use App\Models\MedicalModuleSchedule;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MedicalModuleScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_configure_dynamic_ranges_for_one_module(): void
    {
        [$user, $sede] = $this->userAndSede();
        $shifts = collect(range(1, 4))->mapWithKeys(fn ($shift) => [$shift => [
            'start_from' => '06:50', 'start_to' => '07:10',
            'finish_from' => '10:50', 'finish_to' => '11:20',
            'interval_minutes' => 5,
        ]])->all();

        $this->actingAs($user)->withSession(['current_sede_id' => $sede->id])
            ->put(route('medicals.schedules.update'), ['schedules' => ['2' => $shifts]])
            ->assertRedirect(route('medicals.schedules.edit'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('medical_module_schedules', 4);
        $this->assertDatabaseHas('medical_module_schedules', [
            'sede_id' => $sede->id, 'module' => '2', 'shift' => 1, 'interval_minutes' => 5,
        ]);
    }

    public function test_medical_form_shows_slots_and_only_finish_times_after_treatment(): void
    {
        [$user, $sede] = $this->userAndSede();
        MedicalModuleSchedule::create([
            'sede_id' => $sede->id, 'module' => '2', 'shift' => 1,
            'start_from' => '06:50', 'start_to' => '07:00',
            'finish_from' => '10:55', 'finish_to' => '11:15', 'interval_minutes' => 5,
        ]);
        $patient = Patient::factory()->create(['sede_id' => $sede->id, 'modulo' => '2']);
        $order = Order::create([
            'sede_id' => $sede->id, 'patient_id' => $patient->id, 'codigo_unico' => 'MED-SLOTS',
            'sala' => 'MODULO 2', 'turno' => '1', 'horas_dialisis' => 4, 'fecha_orden' => today(),
        ]);
        $medical = Medical::create(['order_id' => $order->id, 'hora_hd' => 4]);
        foreach (['07:00', '08:00', '09:00', '10:00', '11:00'] as $time) {
            Treatment::create(['order_id' => $order->id, 'hora' => $time]);
        }

        $response = $this->actingAs($user)->withSession(['current_sede_id' => $sede->id])
            ->get(route('medicals.edit', $medical));

        $response->assertOk()
            ->assertSee('data-group="start" data-target="hora_inicial" data-time="06:50"', false)
            ->assertSee('data-group="finish" data-target="hora_final" data-time="11:05"', false)
            ->assertDontSee('data-group="finish" data-target="hora_final" data-time="11:00"', false)
            ->assertSee('Última hora del tratamiento:');
    }

    public function test_schedule_generates_slots_across_midnight(): void
    {
        $schedule = new MedicalModuleSchedule([
            'start_from' => '23:50', 'start_to' => '00:10',
            'finish_from' => '03:00', 'finish_to' => '03:10', 'interval_minutes' => 10,
        ]);

        $this->assertSame(['23:50', '00:00', '00:10'], $schedule->startSlots()->all());
    }

    private function userAndSede(): array
    {
        $user = User::factory()->create(['profession' => 'MEDICO']);
        $sede = Sede::create(['name' => 'Sede de prueba', 'code' => 'MED-SCHEDULE', 'is_active' => true]);
        $user->sedes()->attach($sede);
        Permission::findOrCreate('medicals.edit');
        $user->givePermissionTo('medicals.edit');

        return [$user, $sede];
    }
}
