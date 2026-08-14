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
        $this->assertSame(5, $consultation->medications->count());
        $this->assertSame('3107', $consultation->medications->first()->fua_code);
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
}
