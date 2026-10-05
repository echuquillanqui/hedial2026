<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Sede;
use App\Models\User;
use App\Support\ClinicalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PatientActiveStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_patients_are_active_by_default_and_the_status_is_cast_to_boolean(): void
    {
        $patient = Patient::factory()->create();

        $this->assertTrue($patient->is_active);
        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'is_active' => 1,
        ]);
    }

    public function test_patient_status_can_be_updated_and_is_visible_in_the_patient_list(): void
    {
        [$user, $sede] = $this->userWithPatientPermissions();
        $patient = Patient::factory()->create([
            'sede_id' => $sede->id,
            'first_name' => 'Paciente',
            'surname' => 'Estado',
            'last_name' => 'Visible',
        ]);

        $updateResponse = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->put(route('patients.update', $patient), [
                'dni' => $patient->dni,
                'medical_history_number' => $patient->medical_history_number,
                'first_name' => $patient->first_name,
                'surname' => $patient->surname,
                'last_name' => $patient->last_name,
                'insurance_type' => 'SIS',
                'sede_id' => $sede->id,
                'is_active' => 0,
            ]);

        $updateResponse->assertSessionHasNoErrors();
        $this->assertFalse($patient->fresh()->is_active);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('patients.index'))
            ->assertOk()
            ->assertSee('ESTADO')
            ->assertSee('Inactivos');
    }

    public function test_an_inactive_patient_can_be_reactivated(): void
    {
        [$user, $sede] = $this->userWithPatientPermissions();
        $patient = Patient::factory()->create([
            'sede_id' => $sede->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->put(route('patients.update', $patient), [
                'dni' => $patient->dni,
                'medical_history_number' => $patient->medical_history_number,
                'first_name' => $patient->first_name,
                'surname' => $patient->surname,
                'last_name' => $patient->last_name,
                'insurance_type' => 'SIS',
                'sede_id' => $sede->id,
                'is_active' => 1,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success', 'Historial actualizado correctamente.');
        $this->assertTrue($patient->fresh()->is_active);
    }

    public function test_patient_form_submits_a_single_status_value_bound_to_the_switch(): void
    {
        $view = file_get_contents(resource_path('views/patients/modals/form.blade.php'));

        $this->assertSame(1, substr_count($view, 'name="is_active"'));
        $this->assertStringContainsString(
            'name="is_active" :value="currentPatient.is_active ? \'1\' : \'0\'"',
            $view
        );
        $this->assertStringContainsString('id="patient_is_active"', $view);
        $this->assertStringContainsString('x-model="currentPatient.is_active"', $view);
    }

    public function test_only_active_patients_are_available_in_every_order_generation_form(): void
    {
        $user = User::factory()->create(['profession' => 'NUTRICIONISTA']);
        Permission::findOrCreate('orders.create', 'web');
        $user->givePermissionTo('orders.create');
        $active = Patient::factory()->create(['is_active' => true, 'secuencia' => 'L-M-V']);
        $inactive = Patient::factory()->create(['is_active' => false, 'secuencia' => 'L-M-V']);

        $routes = [
            route('orders.create', ['filter_patients' => 1]),
            route('orders.nephrology.create'),
            route('orders.multisectorial.create', ['type' => ClinicalService::NUTRITION]),
            route('laboratory.orders.create', ['secuencia' => 'L-M-V']),
        ];

        foreach ($routes as $route) {
            $this->actingAs($user)->withoutMiddleware()->get($route)
                ->assertOk()
                ->assertViewHas('patients', fn ($patients) => $patients->contains('id', $active->id)
                    && ! $patients->contains('id', $inactive->id));
        }
    }

    public function test_inactive_patients_cannot_be_submitted_to_create_orders(): void
    {
        $user = User::factory()->create(['profession' => 'NUTRICIONISTA']);
        Permission::findOrCreate('orders.create', 'web');
        $user->givePermissionTo('orders.create');
        $inactive = Patient::factory()->create(['is_active' => false]);

        $this->actingAs($user)->withoutMiddleware()->from('/orders/create')->post(route('orders.store'), [
            'patient_id' => $inactive->id,
            'turno' => '1',
            'horas_dialisis' => 3,
            'fecha_orden' => today()->toDateString(),
        ])->assertSessionHasErrors('patient_id');

        $this->actingAs($user)->withoutMiddleware()->from('/orders/nephrology/create')->post(route('orders.nephrology.store'), [
            'patient_ids' => [$inactive->id],
            'fecha_orden' => today()->toDateString(),
        ])->assertSessionHasErrors('patient_ids.0');

        $this->actingAs($user)->withoutMiddleware()->from('/orders/multisectorial/create')->post(route('orders.multisectorial.store'), [
            'type' => ClinicalService::NUTRITION,
            'patient_id' => $inactive->id,
            'assigned_professional_id' => $user->id,
            'fecha_orden' => today()->toDateString(),
        ])->assertSessionHasErrors('patient_id');

        $this->actingAs($user)->withoutMiddleware()->from('/laboratory/orders/create')->post(route('laboratory.orders.store'), [
            'patient_ids' => [$inactive->id],
            'schedules' => [['sampled_at' => today()->toDateString(), 'period' => 'M']],
        ])->assertSessionHasErrors('patient_ids.0');
    }

    private function userWithPatientPermissions(): array
    {
        $user = User::factory()->create(['profession' => 'ADMINISTRATIVO']);
        $sede = Sede::create(['name' => 'Sede de prueba', 'code' => 'TEST', 'is_active' => true]);
        $user->sedes()->attach($sede);

        foreach (['patients.view', 'patients.edit'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
        }

        return [$user, $sede];
    }
}
