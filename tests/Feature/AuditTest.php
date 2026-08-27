<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Fua;
use App\Models\HemodialysisConsent;
use App\Models\LaboratoryOrder;
use App\Models\LaboratoryOrderItem;
use App\Models\Medical;
use App\Models\NephrologyConsultation;
use App\Models\Nurse;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\Test;
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

    public function test_audit_lists_are_grouped_by_module_and_filters_submit_automatically(): void
    {
        [$user, $sede] = $this->auditScenario();
        $firstModulePatient = Patient::factory()->create([
            'sede_id' => $sede->id,
            'first_name' => 'PACIENTE MODULO UNO',
            'surname' => 'ZAPATA',
        ]);
        Order::create([
            'sede_id' => $sede->id,
            'patient_id' => $firstModulePatient->id,
            'codigo_unico' => 'ORD-AUD-M1',
            'sala' => 'MODULO 1',
            'turno' => '4',
            'fecha_orden' => today(),
            'attention_type' => 'HEMODIALYSIS',
        ]);

        foreach (['audit.histories' => 'Corrección', 'audit.fissal' => 'Nefrólogo'] as $route => $previousColumn) {
            $this->actingAs($user)
                ->withSession(['current_sede_id' => $sede->id])
                ->get(route($route, ['date' => today()->toDateString()]))
                ->assertOk()
                ->assertSeeInOrder(['PACIENTE MODULO UNO', 'PACIENTE AUDITADO'])
                ->assertSee('data-audit-filters', false)
                ->assertSee('form.requestSubmit()', false)
                ->assertSeeInOrder([$previousColumn, 'Módulo']);
        }
    }

    public function test_pending_documents_lists_only_missing_records_that_apply_to_each_patient(): void
    {
        [$user, $sede, $hemodialysisOrder] = $this->auditScenario();
        $hemodialysisOrder->update(['laboratory_period' => 'T']);
        $nephrologyPatient = Patient::factory()->create([
            'sede_id' => $sede->id,
            'first_name' => 'SIN CONSULTA NEFROLOGICA',
            'secuencia' => 'M-J-S',
            'turno' => '2',
            'modulo' => '3',
        ]);
        Order::create([
            'sede_id' => $sede->id,
            'patient_id' => $nephrologyPatient->id,
            'codigo_unico' => 'ORD-NEF-PENDING',
            'fecha_orden' => today(),
            'attention_type' => 'NEPHROLOGY',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('audit.pending-documents', ['month' => today()->format('Y-m')]));

        $response->assertOk()
            ->assertSee('PACIENTE AUDITADO')
            ->assertSee('SIN CONSULTA NEFROLOGICA')
            ->assertSee('Consentimiento')
            ->assertSee('Laboratorio')
            ->assertSee('Consulta nefrológica')
            ->assertSee(route('consents.create', ['patient_id' => $hemodialysisOrder->patient_id]), false)
            ->assertSee(route('orders.nephrology.create', ['patient_id' => $nephrologyPatient->id]), false);

        HemodialysisConsent::create([
            'patient_id' => $hemodialysisOrder->patient_id,
            'sede_id' => $sede->id,
            'created_by' => $user->id,
            'consented_at' => now(),
            'version' => '1.0',
            'accepted' => true,
        ]);
        LaboratoryOrder::create([
            'patient_id' => $hemodialysisOrder->patient_id,
            'order_id' => $hemodialysisOrder->id,
            'patient_name' => $hemodialysisOrder->patient->full_name,
            'period' => 'T',
            'sampled_at' => today(),
        ]);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('audit.pending-documents', ['month' => today()->format('Y-m'), 'missing' => 'laboratory']))
            ->assertOk()
            ->assertDontSee('PACIENTE AUDITADO')
            ->assertSee('SIN CONSULTA NEFROLOGICA');
    }

    public function test_ktv_uses_laboratory_sample_date_results_and_same_day_hemodialysis(): void
    {
        [$user, $sede, $order] = $this->auditScenario();
        $order->medical->update(['hora_hd' => 4, 'peso_inicial' => 68, 'peso_seco' => 65, 'uf' => 2]);
        $order->nurse->update(['peso_inicial' => 68, 'peso_final' => 65]);
        $laboratory = LaboratoryOrder::create(['patient_id' => $order->patient_id, 'patient_name' => $order->patient->full_name, 'period' => 'M', 'sampled_at' => today()]);
        $area = Area::create(['name' => 'Bioquímica']);
        foreach (['Urea pre diálisis' => '100', 'Urea post diálisis' => '30', 'Albúmina' => '4.2'] as $name => $value) {
            $test = Test::create(['area_id' => $area->id, 'name' => $name, 'type' => 'number']);
            LaboratoryOrderItem::create(['laboratory_order_id' => $laboratory->id, 'test_id' => $test->id, 'result_value' => $value]);
        }

        $this->actingAs($user)->withSession(['current_sede_id' => $sede->id])
            ->get(route('audit.ktv', ['date' => today()->toDateString()]))
            ->assertOk()->assertSee('Auditoría KTV')->assertSee('PACIENTE AUDITADO')
            ->assertSee('30')->assertSee('100')->assertSee('4.2')->assertSee('68')->assertSee('65');
    }

    public function test_ktv_does_not_divide_by_zero_when_no_valid_weight_is_available(): void
    {
        [$user, $sede, $order] = $this->auditScenario();
        $order->medical->update(['hora_hd' => 4, 'peso_seco' => 0, 'uf' => 2]);
        $order->nurse->update(['peso_final' => 0]);
        $laboratory = LaboratoryOrder::create(['patient_id' => $order->patient_id, 'patient_name' => $order->patient->full_name, 'period' => 'M', 'sampled_at' => today()]);
        $area = Area::create(['name' => 'Bioquímica']);

        foreach (['Urea pre diálisis' => '100', 'Urea post diálisis' => '30'] as $name => $value) {
            $test = Test::create(['area_id' => $area->id, 'name' => $name, 'type' => 'number']);
            LaboratoryOrderItem::create(['laboratory_order_id' => $laboratory->id, 'test_id' => $test->id, 'result_value' => $value]);
        }

        $this->actingAs($user)->withSession(['current_sede_id' => $sede->id])
            ->get(route('audit.ktv', ['date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee('PACIENTE AUDITADO')
            ->assertSee('<td class="fw-bold text-success">—</td>', false);
    }

    public function test_pending_documents_uses_monthly_documents_for_all_patients(): void
    {
        [$user, $sede] = $this->auditScenario();
        $patient = Patient::factory()->create([
            'sede_id' => $sede->id,
            'first_name' => 'CONTROL MENSUAL',
        ]);
        Order::create([
            'sede_id' => $sede->id,
            'patient_id' => $patient->id,
            'codigo_unico' => 'ORD-MONTHLY-HD',
            'fecha_orden' => today()->startOfMonth(),
            'attention_type' => 'HEMODIALYSIS',
        ]);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('audit.pending-documents', ['month' => today()->format('Y-m')]))
            ->assertOk()
            ->assertSee('CONTROL MENSUAL')
            ->assertSee('Consulta nefrológica')
            ->assertSee('Consentimiento')
            ->assertSee('Laboratorio');

        HemodialysisConsent::create(['patient_id' => $patient->id, 'sede_id' => $sede->id, 'created_by' => $user->id, 'consented_at' => today()->startOfMonth(), 'version' => '1.0', 'accepted' => true]);
        LaboratoryOrder::create(['patient_id' => $patient->id, 'patient_name' => $patient->full_name, 'period' => 'M', 'sampled_at' => today()->startOfMonth()]);
        $nephrologyOrder = Order::create(['sede_id' => $sede->id, 'patient_id' => $patient->id, 'codigo_unico' => 'ORD-MONTHLY-NEF', 'fecha_orden' => today()->endOfMonth(), 'attention_type' => 'NEPHROLOGY']);
        NephrologyConsultation::create(['order_id' => $nephrologyOrder->id, 'patient_id' => $patient->id, 'sede_id' => $sede->id, 'consultation_date' => today()->endOfMonth()]);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('audit.pending-documents', ['month' => today()->format('Y-m'), 'search' => 'CONTROL MENSUAL']))
            ->assertOk()
            ->assertDontSee('CONTROL MENSUAL');

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('audit.pending-documents', ['month' => today()->subMonth()->format('Y-m'), 'search' => 'CONTROL MENSUAL']))
            ->assertOk()
            ->assertSee('CONTROL MENSUAL');
    }

    public function test_pending_documents_includes_patients_without_any_generated_records(): void
    {
        [$user, $sede] = $this->auditScenario();
        $patient = Patient::factory()->create([
            'sede_id' => $sede->id,
            'first_name' => 'SIN NINGUN DOCUMENTO',
        ]);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('audit.pending-documents', [
                'month' => today()->format('Y-m'),
                'search' => 'SIN NINGUN DOCUMENTO',
            ]))
            ->assertOk()
            ->assertSee('SIN NINGUN DOCUMENTO')
            ->assertSee('Consentimiento')
            ->assertSee('Consulta nefrológica')
            ->assertSee('Laboratorio')
            ->assertSee(route('consents.create', ['patient_id' => $patient->id]), false)
            ->assertSee(route('orders.nephrology.create', ['patient_id' => $patient->id]), false);
    }

    public function test_pending_documents_can_filter_patients_missing_every_document(): void
    {
        [$user, $sede, $order] = $this->auditScenario();
        $missingEverything = Patient::factory()->create([
            'sede_id' => $sede->id,
            'first_name' => 'FALTA TODO',
        ]);
        Order::create([
            'sede_id' => $sede->id,
            'patient_id' => $missingEverything->id,
            'codigo_unico' => 'ORD-MISSING-ALL',
            'fecha_orden' => today(),
            'attention_type' => 'HEMODIALYSIS',
        ]);
        HemodialysisConsent::create([
            'patient_id' => $order->patient_id,
            'sede_id' => $sede->id,
            'created_by' => $user->id,
            'consented_at' => now(),
            'version' => '1.0',
            'accepted' => true,
        ]);

        $this->actingAs($user)
            ->withSession(['current_sede_id' => $sede->id])
            ->get(route('audit.pending-documents', [
                'month' => today()->format('Y-m'),
                'missing' => 'all',
            ]))
            ->assertOk()
            ->assertSee('Todos los documentos')
            ->assertSee('FALTA TODO')
            ->assertDontSee('PACIENTE AUDITADO');
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
