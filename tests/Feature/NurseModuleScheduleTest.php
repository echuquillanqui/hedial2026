<?php

namespace Tests\Feature;

use App\Models\Medical;
use App\Models\Nurse;
use App\Models\NurseModuleSchedule;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NurseModuleScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_configure_five_start_times_for_each_module(): void
    {
        [$user, $sede] = $this->userAndSede();
        $payload = collect(Patient::MODULES)->mapWithKeys(fn ($module) => [
            $module => ['06:55', '07:00', '07:05', '07:10', '07:15'],
        ])->all();

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->put(route('nurses.schedules.update'), ['schedules' => $payload])
            ->assertRedirect(route('nurses.schedules.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('nurse_module_schedules', count(Patient::MODULES));
        $this->assertSame(
            $payload['1'],
            NurseModuleSchedule::where('sede_id', $sede->id)->where('module', '1')->firstOrFail()->start_times
        );
    }

    public function test_each_module_requires_exactly_five_distinct_valid_times(): void
    {
        [$user, $sede] = $this->userAndSede();
        $payload = collect(Patient::MODULES)->mapWithKeys(fn ($module) => [
            $module => ['07:00', '07:05', '07:10', '07:15', '07:20'],
        ])->all();
        $payload['2'] = ['07:00', '07:00', 'not-a-time'];

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->from(route('nurses.schedules.edit'))
            ->put(route('nurses.schedules.update'), ['schedules' => $payload])
            ->assertRedirect(route('nurses.schedules.edit'))
            ->assertSessionHasErrors(['schedules.2', 'schedules.2.2']);

        $this->assertDatabaseCount('nurse_module_schedules', 0);
    }

    public function test_nursing_form_uses_fixed_times_for_the_patients_module(): void
    {
        [$user, $sede] = $this->userAndSede();
        NurseModuleSchedule::create([
            'sede_id' => $sede->id,
            'module' => '2',
            'start_times' => ['13:55', '14:00', '14:05', '14:10', '14:15'],
        ]);
        $nurse = $this->nurseForModule($sede, '2');

        $response = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.edit', $nurse));

        $response->assertOk()
            ->assertSee('data-hora="13:55"', false)
            ->assertSee('data-hora="14:15"', false)
            ->assertDontSee('Este módulo todavía no tiene horas configuradas.');
    }

    public function test_used_time_is_only_blocked_for_another_patient_in_the_same_module(): void
    {
        [$user, $sede] = $this->userAndSede();
        NurseModuleSchedule::create([
            'sede_id' => $sede->id,
            'module' => '2',
            'start_times' => ['08:00', '08:05', '08:10', '08:15', '08:20'],
        ]);
        $nurse = $this->nurseForModule($sede, '2');
        $otherModule = $this->nurseForModule($sede, '1');
        Treatment::create(['order_id' => $otherModule->order_id, 'hora' => '08:00']);

        $response = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.edit', $nurse));

        $response->assertOk()
            ->assertSee('data-hora="08:00"', false)
            ->assertDontSee('data-hora="08:00" disabled', false);
    }

    private function userAndSede(): array
    {
        $user = User::factory()->create(['profession' => 'ENFERMERA']);
        $sede = Sede::create(['name' => 'Sede de prueba', 'code' => 'TEST', 'is_active' => true]);
        $user->sedes()->attach($sede);
        Permission::findOrCreate('nurses.edit');
        $user->givePermissionTo('nurses.edit');

        return [$user, $sede];
    }

    private function nurseForModule(Sede $sede, string $module): Nurse
    {
        $patient = Patient::factory()->create(['sede_id' => $sede->id, 'modulo' => $module]);
        $order = Order::create([
            'sede_id' => $sede->id,
            'patient_id' => $patient->id,
            'codigo_unico' => 'ORD-'.uniqid(),
            'sala' => 'MODULO '.$module,
            'turno' => '1',
            'horas_dialisis' => 4,
            'fecha_orden' => today(),
        ]);
        Medical::create(['order_id' => $order->id, 'hora_hd' => 4]);

        return Nurse::create(['order_id' => $order->id]);
    }
}
