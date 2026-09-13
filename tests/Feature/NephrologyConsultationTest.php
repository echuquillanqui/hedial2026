<?php

namespace Tests\Feature;

use App\Http\Controllers\NephrologyConsultationController;
use App\Models\NephrologyConsultation;
use App\Models\MedicationCatalog;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NephrologyConsultationTest extends TestCase
{
    use RefreshDatabase;

    public function test_medication_catalog_is_seeded_and_codes_can_be_completed_manually(): void
    {
        $user = User::factory()->create();
        $medication = MedicationCatalog::where('name', 'like', 'Hierro%')->firstOrFail();

        $payload = MedicationCatalog::all()->mapWithKeys(fn ($item) => [$item->id => [
            'code' => $item->is($medication) ? 'MED-001' : $item->code,
            'name' => $item->name,
            'reference_quantity' => $item->reference_quantity,
            'frequency' => $item->frequency,
        ]])->all();

        $this->actingAs($user)->withoutMiddleware()->put(route('medication-catalog.update'), [
            'medications' => $payload,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('medication_catalog', [
            'id' => $medication->id, 'code' => 'MED-001', 'reference_quantity' => 4, 'frequency' => 'Mensual',
        ]);
    }

    public function test_medication_search_matches_name_or_code_and_form_exposes_autocomplete(): void
    {
        $user = User::factory()->create();
        $medication = MedicationCatalog::where('name', 'like', 'Epoetina alfa%2000%')->firstOrFail();
        $medication->update(['code' => 'EPO-2000']);

        $this->actingAs($user)->withoutMiddleware()->getJson(route('medication-catalog.search', ['q' => 'EPO-2000']))
            ->assertOk()->assertJsonFragment(['name' => $medication->name, 'reference_quantity' => 12]);
        $this->actingAs($user)->withoutMiddleware()->getJson(route('medication-catalog.search', ['q' => 'Eritropoyetina']))
            ->assertOk()->assertJsonFragment(['code' => 'EPO-2000']);

        $patient = Patient::factory()->create();
        $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => [$patient->id], 'fecha_orden' => '2026-09-13',
        ]);
        $this->actingAs($user)->withoutMiddleware()->get(route('consultations.edit', NephrologyConsultation::firstOrFail()))
            ->assertOk()->assertSee('medication-search')->assertSee(route('medication-catalog.search'), false);
    }

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
            ->assertSee($patients[1]->full_name)
            ->assertSee(route('fuas.pdf', NephrologyConsultation::firstOrFail()->order->fua), false)
            ->assertSee('FUA');
    }

    public function test_empty_nephrology_consultation_uses_the_requested_default_medications(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => [$patient->id],
            'fecha_orden' => '2026-08-14',
        ])->assertRedirect(route('orders.index'));

        $consultation = NephrologyConsultation::firstOrFail();

        $this->actingAs($user)->withoutMiddleware()->get(route('consultations.edit', $consultation))
            ->assertOk()
            ->assertViewHas('medications', function ($medications): bool {
                return $medications->values()->all() === NephrologyConsultationController::DEFAULT_MEDICATIONS
                    && $medications->pluck('fua_code')->all() === ['06127', '05491', '04523', '00671', '00200']
                    && $medications->pluck('c')->all() === [
                        '1 tableta cada 24 horas en el desayuno',
                        '1 tableta cada 24 horas en el desayuno',
                        '1 tableta cada 12 horas, 8 AM y 8 PM',
                        '1 tableta cada 24 horas, 9 AM',
                        '1 tableta cada 24 horas en el desayuno',
                    ]
                    && $medications->pluck('prescribed_quantity')->all() === [30, 30, 60, 30, 30]
                    && $medications->pluck('delivered_quantity')->all() === [30, 30, 60, 30, 30];
            })
            ->assertSee('Tiamina clorhidrato 100 mg tableta')
            ->assertSee('Piridoxina clorhidrato 50 mg tableta')
            ->assertSee('Losartan 50 mg tableta')
            ->assertSee('Amlodipino (como Besilato) 10 mg tableta')
            ->assertSee('Ácido fólico 500 mcg (0.5 mg) tableta')
            ->assertDontSee('Epoetina alfa')
            ->assertDontSee('Vitamina B12')
            ->assertDontSee('Hierro sacarato');
    }

    public function test_second_nephrology_consultation_copies_the_previous_clinical_data_and_medications(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => [$patient->id],
            'fecha_orden' => '2026-08-14',
        ]);

        $previous = NephrologyConsultation::firstOrFail();
        $previous->update([
            'doctor_id' => $user->id,
            'consultation_time' => '09:35',
            'blood_pressure' => '120/80',
            'weight' => 68.5,
            'reason' => 'Control mensual',
            'current_illness' => 'Paciente estable',
            'diagnoses' => [['codigo' => 'N18.6', 'descripcion' => 'Enfermedad renal terminal']],
            'auxiliary_exams' => ['Mensual|Hemoglobina'],
            'treatment_plan' => 'Continuar hemodiálisis',
            'next_appointment_date' => '2026-09-14',
        ]);
        $previous->medications()->create([
            'fua_code' => 'MED-100',
            'description' => 'Medicamento personalizado',
            'c' => '1 tableta diaria',
            'prescribed_quantity' => 30,
            'delivered_quantity' => 25,
        ]);

        $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => [$patient->id],
            'fecha_orden' => '2026-09-14',
        ])->assertRedirect(route('orders.index'));

        $current = NephrologyConsultation::latest('id')->firstOrFail();

        $this->assertNotSame($previous->id, $current->id);
        $this->assertNotSame($previous->order_id, $current->order_id);
        $this->assertSame('2026-09-14', $current->consultation_date->toDateString());
        $this->assertSame($previous->doctor_id, $current->doctor_id);
        $this->assertSame('09:35', substr($current->consultation_time, 0, 5));
        $this->assertSame($previous->blood_pressure, $current->blood_pressure);
        $this->assertSame($previous->weight, $current->weight);
        $this->assertSame($previous->reason, $current->reason);
        $this->assertSame($previous->current_illness, $current->current_illness);
        $this->assertSame($previous->diagnoses, $current->diagnoses);
        $this->assertSame($previous->auxiliary_exams, $current->auxiliary_exams);
        $this->assertSame($previous->treatment_plan, $current->treatment_plan);
        $this->assertSame('2026-09-14', $current->next_appointment_date->toDateString());
        $this->assertDatabaseHas('medications', [
            'nephrology_consultation_id' => $current->id,
            'fua_code' => 'MED-100',
            'description' => 'Medicamento personalizado',
            'c' => '1 tableta diaria',
            'prescribed_quantity' => 30,
            'delivered_quantity' => 25,
        ]);
    }

    public function test_first_nephrology_consultation_remains_empty(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => [$patient->id],
            'fecha_orden' => '2026-08-14',
        ])->assertRedirect(route('orders.index'));

        $consultation = NephrologyConsultation::firstOrFail();

        $this->assertNull($consultation->reason);
        $this->assertNull($consultation->doctor_id);
        $this->assertTrue($consultation->medications()->doesntExist());
    }

    public function test_each_patient_can_receive_an_individual_date_during_bulk_generation(): void
    {
        $user = User::factory()->create();
        $patients = Patient::factory()->count(3)->create();

        $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => $patients->modelKeys(),
            'fecha_orden' => '2026-09-10',
            'patient_dates' => [
                $patients[0]->id => '2026-09-08',
                $patients[1]->id => '2026-09-12',
            ],
        ])->assertRedirect(route('orders.index'));

        foreach (['2026-09-08', '2026-09-12', '2026-09-10'] as $index => $date) {
            $consultation = NephrologyConsultation::where('patient_id', $patients[$index]->id)->firstOrFail();

            $this->assertSame($date, $consultation->consultation_date->toDateString());
            $this->assertSame($date, $consultation->order->fecha_orden->toDateString());
        }
    }

    public function test_consultations_can_only_be_generated_from_nephrology_orders(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();

        $orphan = NephrologyConsultation::create([
            'patient_id' => $patient->id,
            'consultation_date' => '2026-08-14',
        ]);

        $routes = collect(app('router')->getRoutes());
        $this->assertFalse($routes->contains(fn ($route) => $route->getName() === 'consultations.create'));
        $this->assertFalse($routes->contains(fn ($route) => $route->getName() === 'consultations.store'));

        $this->actingAs($user)->withoutMiddleware()->get(route('consultations.index'))
            ->assertOk()
            ->assertDontSee($orphan->patient->full_name);
    }

    public function test_consultation_index_filters_by_date_and_patient_schedule(): void
    {
        $user = User::factory()->create();
        $expected = Patient::factory()->create(['secuencia' => 'L-M-V', 'turno' => '2', 'modulo' => '3']);
        $hidden = Patient::factory()->create(['secuencia' => 'M-J-S', 'turno' => '1', 'modulo' => '1']);

        $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => [$expected->id, $hidden->id],
            'fecha_orden' => '2026-08-14',
        ]);

        $this->actingAs($user)->withoutMiddleware()->get(route('consultations.index', [
            'date' => '2026-08-14', 'sequence' => 'L-M-V', 'shift' => 2, 'module' => 3,
        ]))->assertOk()->assertSee($expected->full_name)->assertDontSee($hidden->full_name)
            ->assertSee('Imprimir bloque')->assertSee('Consulta')->assertSee('Receta')->assertSee('FUA');
    }

    public function test_consultation_filters_use_patient_data_and_search_all_identifiers(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create([
            'medical_history_number' => 'HC-FILTRO-99',
            'secuencia' => 'ESPECIAL',
            'turno' => 'NOCHE',
            'modulo' => 'MOD-8',
        ]);
        $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => [$patient->id], 'fecha_orden' => '2026-08-14',
        ]);

        $this->actingAs($user)->withoutMiddleware()->get(route('consultations.index', [
            'search' => 'HC-FILTRO-99', 'sequence' => 'ESPECIAL', 'shift' => 'NOCHE', 'module' => 'MOD-8',
        ]))->assertOk()->assertSee($patient->full_name)
            ->assertSee('<option value="ESPECIAL" selected>', false)
            ->assertSee('<option value="NOCHE" selected>', false)
            ->assertSee('<option value="MOD-8" selected>', false);
    }

    public function test_editing_consultation_date_also_updates_the_date_printed_on_the_fua(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => [$patient->id], 'fecha_orden' => '2026-08-14',
        ]);
        $consultation = NephrologyConsultation::firstOrFail();

        $this->actingAs($user)->withoutMiddleware()->patch(route('consultations.date.update', $consultation), [
            'consultation_date' => '2026-09-10',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('2026-09-10', $consultation->fresh()->consultation_date->toDateString());
        $this->assertSame('2026-09-10', $consultation->order->fresh()->fecha_orden->toDateString());
    }

    public function test_dates_can_be_updated_in_bulk_for_consultations_and_their_orders(): void
    {
        $user = User::factory()->create();
        $patients = Patient::factory()->count(2)->create();
        $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => $patients->modelKeys(), 'fecha_orden' => '2026-08-14',
        ]);
        $consultations = NephrologyConsultation::all();

        $this->actingAs($user)->withoutMiddleware()->patch(route('consultations.dates.update'), [
            'consultations' => $consultations->modelKeys(),
            'consultation_date' => '2026-09-15',
        ])->assertRedirect()->assertSessionHas('success');

        foreach ($consultations as $consultation) {
            $this->assertSame('2026-09-15', $consultation->fresh()->consultation_date->toDateString());
            $this->assertSame('2026-09-15', $consultation->order->fresh()->fecha_orden->toDateString());
        }
    }

    public function test_consultations_and_prescriptions_can_be_printed_in_bulk(): void
    {
        $user = User::factory()->create();
        $patients = Patient::factory()->count(2)->create();
        $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => $patients->modelKeys(), 'fecha_orden' => '2026-08-14',
        ]);
        $ids = NephrologyConsultation::pluck('id')->all();

        foreach (['consultation', 'prescription'] as $type) {
            $response = $this->actingAs($user)->withoutMiddleware()->post(route('consultations.bulk-pdf'), [
                'consultations' => $ids, 'document_type' => $type,
            ]);
            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertSame(2, preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()));
        }
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
        $this->assertSame(
            1,
            preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()),
            'Las dos copias de la receta deben generarse en una sola hoja A4.'
        );

        $consultation->load(['patient', 'doctor', 'sede', 'medications']);
        $configuration = \App\Models\FuaConfiguration::global();
        $configuration->company_name = 'Nombre empresarial que no debe mostrarse';
        $logoData = 'data:image/png;base64,logo-prueba';
        $document = view('consultations.prescription_pdf', compact('consultation', 'configuration', 'logoData'))->render();
        $this->assertSame(2, substr_count($document, 'class="prescription"'));
        $this->assertSame(2, substr_count($document, 'RECETA MÉDICA'));
        $this->assertSame(2, substr_count($document, 'alt="Logo de la empresa"'));
        $this->assertSame(2, substr_count($document, 'Firma y huella del paciente / responsable'));
        $this->assertSame(2, substr_count($document, 'Sello y firma del médico'));
        $this->assertSame(4, substr_count($document, 'class="signature-space"'));
        $this->assertStringNotContainsString('overflow: hidden', $document);
        $this->assertStringNotContainsString('Receta generada el', $document);
        $this->assertStringNotContainsString($configuration->company_name, $document);
        $this->assertStringNotContainsString('Consulta nefrológica', $document);
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

    public function test_validation_preserves_consultation_fields_and_accepts_database_time_format(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $this->actingAs($user)->withoutMiddleware()->post(route('orders.nephrology.store'), [
            'patient_ids' => [$patient->id],
            'fecha_orden' => '2026-08-14',
        ]);
        $consultation = NephrologyConsultation::firstOrFail();

        $response = $this->actingAs($user)->withoutMiddleware()->from(route('consultations.edit', $consultation))
            ->put(route('consultations.update', $consultation), [
                'patient_id' => $patient->id,
                'consultation_date' => '2026-08-14',
                'consultation_time' => '09:35:00',
                'reason' => 'Control mensual que debe conservarse',
                'medications' => [[
                    'description' => '',
                    'prescribed_quantity' => 1,
                    'delivered_quantity' => 1,
                ]],
            ]);

        $response->assertRedirect(route('consultations.edit', $consultation))
            ->assertSessionHasErrors('medications.0.description')
            ->assertSessionDoesntHaveErrors('consultation_time')
            ->assertSessionHasInput('reason', 'Control mensual que debe conservarse')
            ->assertSessionHasInput('consultation_time', '09:35');
    }

    public function test_form_marks_server_validation_errors_and_formats_saved_time(): void
    {
        $consultation = new NephrologyConsultation(['consultation_time' => '09:35:00']);
        $consultation->id = 1;
        $consultation->exists = true;
        $patients = collect();
        $doctors = collect();
        $medications = collect(NephrologyConsultationController::DEFAULT_MEDICATIONS);
        $examGroups = NephrologyConsultationController::AUXILIARY_EXAMS;
        $errors = new \Illuminate\Support\ViewErrorBag();
        $errors->put('default', new \Illuminate\Support\MessageBag([
            'medications.0.description' => ['El campo medicamento es obligatorio.'],
        ]));

        $document = view('consultations.form', compact('consultation', 'patients', 'doctors', 'medications', 'examGroups', 'errors'))->render();

        $this->assertStringContainsString('value="09:35"', $document);
        $this->assertStringContainsString('const validationErrors = ["medications.0.description"]', $document);
        $this->assertStringContainsString("element.classList.add('is-invalid')", $document);
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
