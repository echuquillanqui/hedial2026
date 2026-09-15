<?php

namespace Tests\Feature;

use App\Models\Medical;
use App\Models\Nurse;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DailyHemodialysisSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_indexes_only_show_the_sequence_for_the_selected_day_and_orders_show_patient_module(): void
    {
        $user = User::factory()->create(['profession' => 'ADMINISTRATIVO']);
        $sede = Sede::create(['name' => 'Sede de prueba', 'code' => 'SEQ', 'is_active' => true]);
        $user->sedes()->attach($sede);

        foreach (['orders.view', 'medicals.view', 'nurses.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
        }

        $wednesday = $this->createAttention($sede, 'PACIENTE-MIERCOLES', 'L-M-V', '3', '2026-09-09');
        $otherSequence = $this->createAttention($sede, 'PACIENTE-OTRA-SECUENCIA', 'M-J-S', '4', '2026-09-09');

        foreach (['orders.index', 'medicals.index', 'nurses.index'] as $route) {
            $this->actingAs($user)
                ->withSession(['current_sede_id' => $sede->id])
                ->get(route($route, ['date' => '2026-09-09']))
                ->assertOk()
                ->assertSee($wednesday->patient->first_name)
                ->assertDontSee($otherSequence->patient->first_name);
        }

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('orders.index', ['date' => '2026-09-09']))
            ->assertSee('MÓDULO 3');
    }

    public function test_indexes_switch_to_the_other_sequence_on_thursday(): void
    {
        $user = User::factory()->create(['profession' => 'ADMINISTRATIVO']);
        $sede = Sede::create(['name' => 'Sede de prueba', 'code' => 'THU', 'is_active' => true]);
        $user->sedes()->attach($sede);

        foreach (['orders.view', 'medicals.view', 'nurses.view'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
        }

        $expected = $this->createAttention($sede, 'PACIENTE-JUEVES', 'M-J-S', '2', '2026-09-10');
        $excluded = $this->createAttention($sede, 'PACIENTE-LMV', 'L-M-V', '1', '2026-09-10');

        foreach (['orders.index', 'medicals.index', 'nurses.index'] as $route) {
            $this->actingAs($user)
                ->withSession(['current_sede_id' => $sede->id])
                ->get(route($route, ['date' => '2026-09-10']))
                ->assertOk()
                ->assertSee($expected->patient->first_name)
                ->assertDontSee($excluded->patient->first_name);
        }
    }

    private function createAttention(Sede $sede, string $name, string $sequence, string $module, string $date): Order
    {
        $patient = Patient::factory()->create([
            'sede_id' => $sede->id,
            'first_name' => $name,
            'secuencia' => $sequence,
            'modulo' => $module,
        ]);
        $order = Order::create([
            'sede_id' => $sede->id,
            'patient_id' => $patient->id,
            'codigo_unico' => 'ORD-'.uniqid(),
            'sala' => 'MODULO '.$module,
            'turno' => '1',
            'horas_dialisis' => 3,
            'fecha_orden' => $date,
        ]);
        Medical::create(['order_id' => $order->id]);
        Nurse::create(['order_id' => $order->id]);

        return $order->load('patient');
    }
}
