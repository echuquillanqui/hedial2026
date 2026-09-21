<?php

namespace Tests\Feature;

use App\Models\Fua;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\User;
use App\Support\ClinicalService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkFuaResponsibleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_change_the_signing_doctor_for_a_hemodialysis_block(): void
    {
        $sede = Sede::query()->create(['name' => 'Sede de prueba', 'code' => 'TST']);
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::query()->where('username', 'admin.sede.de.prueba')->firstOrFail();
        $doctor = User::factory()->create(['profession' => 'MÉDICO NEFRÓLOGO']);
        $admin->sedes()->syncWithoutDetaching($sede);
        $doctor->sedes()->syncWithoutDetaching($sede);

        $fuas = collect([1, 2])->map(function (int $number) use ($sede) {
            $patient = Patient::factory()->create(['sede_id' => $sede->id]);
            $order = Order::query()->create([
                'patient_id' => $patient->id,
                'sede_id' => $sede->id,
                'codigo_unico' => 'HD-'.$number,
                'sala' => 'MODULO 1',
                'turno' => '1',
                'horas_dialisis' => 3.5,
                'attention_type' => ClinicalService::HEMODIALYSIS,
                'fecha_orden' => '2026-09-21',
            ]);

            return Fua::query()->create([
                'order_id' => $order->id,
                'type' => Fua::HEMODIALYSIS,
                'series' => 'HD',
                'correlative' => $number,
                'number' => 'HD-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
            ]);
        });

        $this->actingAs($admin)
            ->withSession(['current_sede_id' => $sede->id, 'current_sede_name' => $sede->name])
            ->put(route('fuas.hemodialysis.responsible.bulk-update'), [
                'fuas' => $fuas->pluck('id')->all(),
                'responsible_user_id' => $doctor->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(2, Fua::query()->where('responsible_user_id', $doctor->id)->count());
    }

    public function test_bulk_change_rejects_fuas_outside_the_active_sede_without_partial_updates(): void
    {
        $activeSede = Sede::query()->create(['name' => 'Sede activa', 'code' => 'ACT']);
        $otherSede = Sede::query()->create(['name' => 'Otra sede', 'code' => 'OTH']);
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::query()->where('username', 'admin.sede.activa')->firstOrFail();
        $doctor = User::factory()->create(['profession' => 'MEDICO']);
        $admin->sedes()->syncWithoutDetaching($activeSede);
        $patient = Patient::factory()->create(['sede_id' => $otherSede->id]);
        $order = Order::query()->create([
            'patient_id' => $patient->id,
            'sede_id' => $otherSede->id,
            'codigo_unico' => 'HD-OUT',
            'sala' => 'MODULO 1',
            'turno' => '1',
            'horas_dialisis' => 3.5,
            'attention_type' => ClinicalService::HEMODIALYSIS,
            'fecha_orden' => '2026-09-21',
        ]);
        $fua = Fua::query()->create([
            'order_id' => $order->id,
            'type' => Fua::HEMODIALYSIS,
            'series' => 'HD',
            'correlative' => 1,
            'number' => 'HD-OUT-1',
        ]);

        $this->actingAs($admin)
            ->withSession(['current_sede_id' => $activeSede->id, 'current_sede_name' => $activeSede->name])
            ->put(route('fuas.hemodialysis.responsible.bulk-update'), [
                'fuas' => [$fua->id],
                'responsible_user_id' => $doctor->id,
            ])
            ->assertForbidden();

        $this->assertNull($fua->fresh()->responsible_user_id);
    }
}
