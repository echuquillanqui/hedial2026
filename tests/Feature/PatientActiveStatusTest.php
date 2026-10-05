<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Sede;
use App\Models\User;
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
