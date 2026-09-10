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

class PatientModuleFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_medical_index_filters_and_displays_the_module_assigned_to_the_patient(): void
    {
        [$user, $sede] = $this->userWithPermission('medicals.view');
        $matching = $this->recordsWithDifferentOrderModule($sede, 'PACIENTE-MEDICINA');

        $response = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('medicals.index', ['modulo' => 2]));

        $response->assertOk()
            ->assertSee($matching->patient->first_name)
            ->assertSee('MÓDULO 2')
            ->assertViewHas('medicals', fn ($medicals) => $medicals->count() === 1
                && $medicals->first()->order_id === $matching->id);
    }

    public function test_nursing_index_filters_and_displays_the_module_assigned_to_the_patient(): void
    {
        [$user, $sede] = $this->userWithPermission('nurses.view');
        $matching = $this->recordsWithDifferentOrderModule($sede, 'PACIENTE-ENFERMERIA');

        $response = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.index', ['modulo' => 2]));

        $response->assertOk()
            ->assertSee($matching->patient->first_name)
            ->assertSee('MÓDULO 2')
            ->assertViewHas('nurses', fn ($nurses) => $nurses->count() === 1
                && $nurses->first()->order_id === $matching->id);
    }

    private function userWithPermission(string $permission): array
    {
        $user = User::factory()->create(['profession' => 'ADMINISTRATIVO']);
        $sede = Sede::create(['name' => 'Sede de prueba', 'code' => 'TEST', 'is_active' => true]);
        $user->sedes()->attach($sede);
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);

        return [$user, $sede];
    }

    private function recordsWithDifferentOrderModule(Sede $sede, string $patientName): Order
    {
        $patient = Patient::factory()->create([
            'sede_id' => $sede->id,
            'first_name' => $patientName,
            'modulo' => '2',
            'secuencia' => 'M-J-S',
        ]);
        $order = Order::create([
            'sede_id' => $sede->id,
            'patient_id' => $patient->id,
            'codigo_unico' => 'ORD-'.uniqid(),
            'sala' => 'MODULO 1',
            'turno' => '1',
            'horas_dialisis' => 3,
            'fecha_orden' => today(),
        ]);
        Medical::create(['order_id' => $order->id]);
        Nurse::create(['order_id' => $order->id]);

        return $order->load('patient');
    }
}
