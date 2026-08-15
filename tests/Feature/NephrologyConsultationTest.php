<?php

namespace Tests\Feature;

use App\Http\Controllers\NephrologyConsultationController;
use App\Models\NephrologyConsultation;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NephrologyConsultationTest extends TestCase
{
    use RefreshDatabase;

    public function test_nephrology_order_form_filters_patients_by_schedule_and_search(): void
    {
        $user = User::factory()->create();
        $expected = Patient::factory()->create([
            'surname' => 'QUISPE',
            'secuencia' => 'L-M-V',
            'turno' => '2',
            'modulo' => '3',
        ]);
        Patient::factory()->create(['surname' => 'QUISPE', 'secuencia' => 'M-J-S', 'turno' => '2', 'modulo' => '3']);
        Patient::factory()->create(['surname' => 'RAMOS', 'secuencia' => 'L-M-V', 'turno' => '2', 'modulo' => '3']);

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('orders.nephrology.create', [
            'secuencia' => 'L-M-V',
            'turno' => '2',
            'modulo' => '3',
            'search' => 'QUISPE',
        ]));

        $response->assertOk();
        $response->assertViewHas('patients', fn ($patients): bool => $patients->count() === 1
            && $patients->first()->is($expected));
    }

    public function test_generating_nephrology_orders_adds_them_to_the_consultation_index(): void
    {
        $user = User::factory()->create();
        $patients = Patient::factory()->count(2)->create();

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => $patients->modelKeys(),
            'fecha_orden' => '2026-08-14',
        ]);

        $response->assertRedirect(route('orders.index'));
        $this->assertDatabaseCount('nephrology_consultations', 2);
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('fuas', 2);

        foreach ($patients as $patient) {
            $this->assertDatabaseHas('nephrology_consultations', [
                'patient_id' => $patient->id,
                'consultation_date' => '2026-08-14',
            ]);
        }

        $this->actingAs($user)->withoutMiddleware()->get(route('consultations.index'))
            ->assertOk()
            ->assertSee($patients[0]->full_name)
            ->assertSee($patients[1]->full_name);
    }

    public function test_consultation_stores_default_prescription_rows(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('consultations.store'), [
            'patient_id' => $patient->id,
            'consultation_date' => '2026-08-14',
            'diagnosis' => 'Enfermedad renal crónica',
            'medications' => array_map(fn ($item) => $item + [
                'prescribed_quantity' => 1,
                'delivered_quantity' => 0,
            ], NephrologyConsultationController::DEFAULT_MEDICATIONS),
        ]);

        $response->assertRedirect(route('consultations.index'));
        $consultation = NephrologyConsultation::with('medications')->firstOrFail();
        $this->assertSame(6, $consultation->medications->count());
        $this->assertSame('06127', $consultation->medications->first()->fua_code);
        $this->assertSame('13.00', $consultation->medications->firstWhere('fua_code', '3107')->prescribed_quantity);
    }

    public function test_prescription_pdf_is_available(): void
    {
        $user = User::factory()->create();
        $consultation = NephrologyConsultation::create([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $user->id,
            'consultation_date' => '2026-08-14',
        ]);
        $consultation->medications()->create(NephrologyConsultationController::DEFAULT_MEDICATIONS[0] + [
            'prescribed_quantity' => 2, 'delivered_quantity' => 1,
        ]);

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('consultations.prescription.pdf', $consultation));
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_nephrology_consultation_pdf_is_available(): void
    {
        $user = User::factory()->create();
        $consultation = NephrologyConsultation::create([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $user->id,
            'consultation_date' => '2026-08-14',
            'reason' => 'Control mensual',
        ]);

        $response = $this->actingAs($user)->withoutMiddleware()->get(route('consultations.pdf', $consultation));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame(
            1,
            preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()),
            'El formato de consulta debe generarse en una sola hoja.'
        );
    }

    public function test_consultation_document_lists_only_selected_exam_names_in_three_columns_without_prescription(): void
    {
        $user = User::factory()->create();
        $consultation = NephrologyConsultation::create([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => $user->id,
            'consultation_date' => '2026-08-14',
            'auxiliary_exams' => [
                'Mensual|Hematocrito',
                'Mensual|Hemoglobina',
                'Bimestral|Aspartato aminotransferasa (AST/TGO)',
                'Trimestral|Albúmina',
            ],
        ]);
        $consultation->medications()->create(NephrologyConsultationController::DEFAULT_MEDICATIONS[0]);
        $consultation->load(['patient', 'doctor', 'sede']);

        $document = view('consultations.consultation_pdf', compact('consultation'))->render();

        $this->assertStringContainsString('<table class="exam-grid">', $document);
        $this->assertStringNotContainsString('height: 270mm', $document);
        $this->assertStringNotContainsString('overflow: hidden', $document);
        $this->assertStringContainsString('table-layout: fixed', $document);
        $this->assertStringContainsString('<col style="width:42%"><col style="width:20%">', $document);
        $this->assertStringContainsString('class="patient-name"', $document);
        $this->assertStringContainsString('.patient-data .patient-name { white-space: nowrap; }', $document);
        $this->assertSame(4, substr_count($document, '( X )'));
        $this->assertStringContainsString('( X ) Hematocrito', $document);
        $this->assertStringNotContainsString('Mensual|', $document);
        $this->assertStringNotContainsString('Tratamiento prescrito', $document);
        $this->assertStringNotContainsString('Tiamina 100 mg tableta', $document);
    }
}
