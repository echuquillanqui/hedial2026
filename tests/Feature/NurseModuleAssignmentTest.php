<?php

namespace Tests\Feature;

use App\Http\Controllers\NurseController;
use App\Models\Medical;
use App\Models\Nurse;
use App\Models\NurseModuleAssignment;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NurseModuleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_nursing_staff_selectors_only_include_nursing_professionals(): void
    {
        $nursingProfessional = User::factory()->create([
            'name' => 'Profesional de Enfermería',
            'profession' => 'ENFERMERA',
        ]);
        User::factory()->create([
            'name' => 'Profesional Médico',
            'profession' => 'MEDICO',
        ]);
        User::factory()->create([
            'name' => 'Personal Administrativo',
            'profession' => 'ADMINISTRATIVO',
        ]);
        $sede = Sede::create(['name' => 'Sede de prueba', 'code' => 'TEST', 'is_active' => true]);
        $nurse = $this->nurseForModule($sede, 1, 'PACIENTE-SELECTORES');

        $view = app(NurseController::class)->edit($nurse);
        $availableStaff = $view->getData()['enfermeros'];

        $this->assertTrue($availableStaff->contains($nursingProfessional));
        $this->assertSame(
            [$nursingProfessional->id],
            $availableStaff->pluck('id')->all()
        );
    }

    public function test_closing_nurse_starts_with_select_option_instead_of_authenticated_user(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();
        $nurse = $this->nurseForModule($sede, 1, 'PACIENTE-CIERRE-VACIO');
        Permission::findOrCreate('nurses.edit');
        $user->givePermissionTo('nurses.edit');

        $response = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.edit', $nurse));

        $response->assertOk()
            ->assertSee('<option value="">-- Seleccione --</option>', false)
            ->assertDontSee('<option value="'.$user->id.'" selected', false);
    }

    public function test_opening_a_nursing_attention_does_not_assign_a_session_number(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();
        $nurse = $this->nurseForModule($sede, 1, 'PACIENTE-SIN-CIERRE');
        Permission::findOrCreate('nurses.edit');
        $user->givePermissionTo('nurses.edit');

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.edit', $nurse))
            ->assertOk();

        $this->assertNull($nurse->fresh()->numero_hd);
    }

    public function test_session_number_only_increments_for_finalized_nursing_attentions(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();
        Permission::findOrCreate('nurses.edit');
        $user->givePermissionTo('nurses.edit');

        $firstNurse = $this->nurseForModule($sede, 1, 'PACIENTE-CORRELATIVO');
        $patient = $firstNurse->order->patient;
        $draftNurse = $this->nurseForPatient($sede, $patient, '2026-09-08');
        $secondNurse = $this->nurseForPatient($sede, $patient, '2026-09-10');

        $draftNurse->update(['numero_hd' => 99]);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->putJson(route('nurses.update', $draftNurse), [])
            ->assertOk();

        $this->assertNull($draftNurse->fresh()->numero_hd);

        $this->putJson(route('nurses.update', $firstNurse), $this->closurePayload($user))
            ->assertOk();
        $this->putJson(route('nurses.update', $secondNurse), $this->closurePayload($user))
            ->assertOk();

        $this->assertSame(1, $firstNurse->fresh()->numero_hd);
        $this->assertSame(2, $secondNurse->fresh()->numero_hd);
        $this->assertNull($draftNurse->fresh()->numero_hd);
    }

    public function test_incomplete_monitoring_can_be_saved_as_a_draft(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();
        $nurse = $this->nurseForModule($sede, 1, 'PACIENTE-BORRADOR');
        Permission::findOrCreate('nurses.edit');
        $user->givePermissionTo('nurses.edit');

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->putJson(route('nurses.update', $nurse), [
                't_hora' => [null],
                't_fc' => [72],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('treatments', [
            'order_id' => $nurse->order_id,
            'hora' => null,
            'fc' => 72,
        ]);
    }

    public function test_selecting_closing_nurse_requires_completion_fields(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();
        $nurse = $this->nurseForModule($sede, 1, 'PACIENTE-CIERRE-INCOMPLETO');
        Permission::findOrCreate('nurses.edit');
        $user->givePermissionTo('nurses.edit');

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->putJson(route('nurses.update', $nurse), [
                'enfermero_que_finaliza_id' => $user->id,
                't_hora' => ['08:00'],
                't_fc' => [72],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'puesto',
                'numero_maquina',
                'acceso_arterial',
                'acceso_venoso',
                'enfermero_que_inicia_id',
                'pa_final',
                'peso_final',
                'observacion_final',
            ]);
    }

    public function test_medical_detail_modal_is_inside_each_nursing_attention(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();
        $nurse = $this->nurseForModule($sede, 1, 'PACIENTE-PARTE-MEDICO');
        $doctor = User::factory()->create(['name' => 'Médico de prueba', 'profession' => 'MEDICO']);
        Medical::create([
            'order_id' => $nurse->order_id,
            'hora_hd' => 4,
            'problemas_clinicos' => 'Hipertensión controlada',
            'indicaciones' => 'Controlar presión cada hora',
            'usuario_que_inicia_hd' => $doctor->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.edit', $nurse))
            ->assertOk()
            ->assertSee('VER PARTE MÉDICO')
            ->assertSee('id="medicalDetailModal"', false)
            ->assertSee('Hipertensión controlada')
            ->assertSee('Controlar presión cada hora')
            ->assertSee('Médico de prueba');

        NurseModuleAssignment::create([
            'user_id' => $user->id,
            'sede_id' => $sede->id,
            'work_date' => today(),
            'module' => 1,
        ]);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.index'))
            ->assertOk()
            ->assertDontSee('VER PARTE MÉDICO');
    }

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

    public function test_all_modules_option_is_available_on_september_ninth_and_shows_every_patient(): void
    {
        Carbon::setTestNow('2026-09-09 09:00:00');
        [$user, $sede] = $this->nursingUserAndSede();
        $moduleOneNurse = $this->nurseForModule($sede, 1, 'PACIENTE-MODULO-UNO');
        $moduleFourNurse = $this->nurseForModule($sede, 4, 'PACIENTE-MODULO-CUATRO');

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.index'))
            ->assertOk()
            ->assertSee('<option value="0"', false)
            ->assertSee('TODOS');

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->post(route('nurses.module-assignment.store'), ['module' => NurseModuleAssignment::ALL_MODULES])
            ->assertRedirect(route('nurses.index'));

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.index'))
            ->assertOk()
            ->assertSee('Módulo asignado para hoy: TODOS')
            ->assertSee($moduleOneNurse->order->patient->first_name)
            ->assertSee($moduleFourNurse->order->patient->first_name);
    }

    public function test_all_modules_option_is_rejected_after_september_ninth(): void
    {
        Carbon::setTestNow('2026-09-10 09:00:00');
        [$user, $sede] = $this->nursingUserAndSede();

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.index'))
            ->assertOk()
            ->assertDontSee('<option value="0"', false);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->post(route('nurses.module-assignment.store'), ['module' => NurseModuleAssignment::ALL_MODULES])
            ->assertSessionHasErrors('module');

        $this->assertDatabaseCount('nurse_module_assignments', 0);
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

    public function test_nursing_index_offers_filtered_bulk_print_preview_in_a_modal(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();

        NurseModuleAssignment::create([
            'user_id' => $user->id,
            'sede_id' => $sede->id,
            'work_date' => today(),
            'module' => 1,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.index'));

        $response->assertOk()
            ->assertSee('Imprimir en bloque')
            ->assertSee('id="bulkPrintModal"', false)
            ->assertSee('id="bulkPrintFrame"', false)
            ->assertSee(route('enfermeria.print.bulk'), false)
            ->assertSee(route('enfermeria.print.bulk.check'), false)
            ->assertSee('No hay nada para imprimir con los filtros seleccionados.')
            ->assertSee('new URLSearchParams(new FormData(form))', false);
    }

    public function test_nursing_index_keeps_filters_selected_after_navigation(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();

        NurseModuleAssignment::create([
            'user_id' => $user->id,
            'sede_id' => $sede->id,
            'work_date' => today(),
            'module' => 1,
        ]);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.index', [
                'search' => 'Paciente filtrado',
                'date' => '2026-09-01',
                'turno' => '3',
                'estado' => 'finalizado',
            ]))
            ->assertOk()
            ->assertSee('value="Paciente filtrado"', false)
            ->assertSee('value="2026-09-01"', false)
            ->assertSee('<option value="3" selected>3º TURNO</option>', false)
            ->assertSee('<option value="finalizado" selected>', false);
    }

    public function test_bulk_print_check_reports_when_there_is_nothing_to_print(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();

        NurseModuleAssignment::create([
            'user_id' => $user->id,
            'sede_id' => $sede->id,
            'work_date' => today(),
            'module' => 1,
        ]);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->getJson(route('enfermeria.print.bulk.check'))
            ->assertOk()
            ->assertExactJson(['has_records' => false]);
    }

    public function test_bulk_print_check_reports_matching_records(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();
        $this->nurseForModule($sede, 2, 'PACIENTE-PARA-IMPRIMIR');

        NurseModuleAssignment::create([
            'user_id' => $user->id,
            'sede_id' => $sede->id,
            'work_date' => today(),
            'module' => 2,
        ]);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->getJson(route('enfermeria.print.bulk.check'))
            ->assertOk()
            ->assertExactJson(['has_records' => true]);
    }

    public function test_nursing_attention_can_show_every_value_entered_by_the_doctor(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();
        $nurse = $this->nurseForModule($sede, 1, 'PACIENTE-PARTE-MEDICO');
        $doctor = User::factory()->create(['name' => 'Médico de prueba', 'profession' => 'MEDICO']);

        Medical::create([
            'order_id' => $nurse->order_id,
            'hora_hd' => 4,
            'pa_inicial' => '130/80',
            'problemas_clinicos' => 'Hipertensión controlada',
            'indicaciones' => 'Controlar presión cada hora',
            'heparina' => '5000 UI',
            'perfil_uf' => 'Perfil escalonado',
            'evaluacion_final' => 'Sesión sin complicaciones',
            'usuario_que_inicia_hd' => $doctor->id,
        ]);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.show', $nurse))
            ->assertOk()
            ->assertSee('Hipertensión controlada')
            ->assertSee('Controlar presión cada hora')
            ->assertSee('5000 UI')
            ->assertSee('Perfil escalonado')
            ->assertSee('Sesión sin complicaciones')
            ->assertSee('Médico de prueba');
    }

    public function test_nursing_index_includes_the_medical_detail_modal_and_action(): void
    {
        [$user, $sede] = $this->nursingUserAndSede();
        $nurse = $this->nurseForModule($sede, 1, 'PACIENTE-CON-MODAL');
        NurseModuleAssignment::create([
            'user_id' => $user->id,
            'sede_id' => $sede->id,
            'work_date' => today(),
            'module' => 1,
        ]);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('nurses.index'))
            ->assertOk()
            ->assertSee('id="medicalDetailModal"', false)
            ->assertSee('Ver parte médico')
            ->assertSee(route('nurses.show', $nurse), false);
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
            'modulo' => (string) $module,
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

    private function nurseForPatient(Sede $sede, Patient $patient, string $date): Nurse
    {
        $order = Order::create([
            'sede_id' => $sede->id,
            'patient_id' => $patient->id,
            'codigo_unico' => 'ORD-'.uniqid(),
            'sala' => 'MODULO '.$patient->modulo,
            'turno' => '1',
            'horas_dialisis' => 3,
            'fecha_orden' => $date,
        ]);

        return Nurse::create(['order_id' => $order->id]);
    }

    private function closurePayload(User $user): array
    {
        return [
            'puesto' => '1',
            'numero_maquina' => '1',
            'acceso_arterial' => 'FAV',
            'acceso_venoso' => 'FAV',
            'enfermero_que_inicia_id' => $user->id,
            'enfermero_que_finaliza_id' => $user->id,
            'pa_final' => '120/80',
            'peso_final' => 70,
            'observacion_final' => 'Sesión finalizada sin complicaciones.',
        ];
    }
}
