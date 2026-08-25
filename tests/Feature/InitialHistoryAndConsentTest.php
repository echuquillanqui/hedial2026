<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\HemodialysisConsent;
use App\Models\InitialClinicalHistory;
use App\Models\LaboratoryOrder;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\Test;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InitialHistoryAndConsentTest extends TestCase
{
    use RefreshDatabase;

    private Sede $sede;
    private User $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sede = Sede::query()->create(['name' => 'Sede clínica', 'code' => 'CLI']);
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->doctor = User::factory()->create(['profession' => 'MEDICO']);
        $this->doctor->assignRole('medico');
        $this->doctor->sedes()->attach($this->sede);
        $this->patient = Patient::factory()->create([
            'sede_id' => $this->sede->id, 'insurance_type' => 'SIS',
            'medical_history_number' => 'HC-500', 'birth_date' => '1980-05-10',
        ]);
    }

    public function test_initial_history_reuses_patient_and_snapshots_latest_valid_laboratory_references(): void
    {
        [$oldResult, $validResult, $futureResult] = $this->laboratoryTimeline();

        $response = $this->actingAs($this->doctor)->withSession($this->session())->post(route('initial-histories.store'), [
            'patient_id' => $this->patient->id,
            'nephrologist_id' => $this->doctor->id,
            'recorded_at' => '2026-08-15',
            'ckd_etiology' => 'Nefropatía diabética',
            'comorbidities' => ['Diabetes mellitus'],
            'immunizations' => ['Hepatitis B'],
        ]);

        $history = InitialClinicalHistory::firstOrFail();
        $response->assertRedirect(route('initial-histories.show', $history));
        $this->assertSame([$validResult->id], $history->laboratoryResults()->pluck('laboratory_order_items.id')->all());
        $this->assertNotContains($oldResult->id, $history->laboratoryResults()->pluck('laboratory_order_items.id')->all());
        $this->assertNotContains($futureResult->id, $history->laboratoryResults()->pluck('laboratory_order_items.id')->all());
        $this->assertFalse(Schema::hasColumn('initial_clinical_histories', 'dni'));
        $this->assertFalse(Schema::hasColumn('initial_clinical_histories', 'patient_name'));
    }

    public function test_editing_history_does_not_replace_historical_laboratory_references(): void
    {
        [, $validResult] = $this->laboratoryTimeline();
        $this->actingAs($this->doctor)->withSession($this->session())->post(route('initial-histories.store'), [
            'patient_id' => $this->patient->id, 'recorded_at' => '2026-08-15',
        ]);
        $history = InitialClinicalHistory::firstOrFail();

        $this->actingAs($this->doctor)->withSession($this->session())->put(route('initial-histories.update', $history), [
            'clinical_exam' => 'Paciente estable',
        ])->assertRedirect(route('initial-histories.show', $history));

        $this->assertSame([$validResult->id], $history->laboratoryResults()->pluck('laboratory_order_items.id')->all());
        $this->assertSame('2026-08-15', $history->fresh()->recorded_at->format('Y-m-d'));
    }

    public function test_consents_are_append_only_and_keep_version_date_sede_and_responsible(): void
    {
        foreach ([['2026-08-20 09:00', '1.0', 1], ['2027-08-20 09:00', '2.0', 0]] as [$date, $version, $accepted]) {
            $this->actingAs($this->doctor)->withSession($this->session())->post(route('consents.store'), [
                'patient_id' => $this->patient->id, 'physician_id' => $this->doctor->id,
                'consented_at' => $date, 'version' => $version, 'accepted' => $accepted,
            ])->assertRedirect();
        }

        $this->assertSame(2, HemodialysisConsent::query()->count());
        $this->assertDatabaseHas('hemodialysis_consents', [
            'patient_id' => $this->patient->id, 'sede_id' => $this->sede->id,
            'physician_id' => $this->doctor->id, 'created_by' => $this->doctor->id,
            'version' => '1.0', 'accepted' => true,
        ]);
        $this->assertFalse(collect(app('router')->getRoutes())->contains(fn ($route) => $route->getName() === 'consents.update'));
    }

    public function test_sector_professional_cannot_modify_history_or_create_consents(): void
    {
        $nutritionist = User::query()->where('username', 'nutricionista')->firstOrFail();
        $this->actingAs($nutritionist)->withSession($this->session())
            ->get(route('initial-histories.index'))->assertForbidden();
        $this->actingAs($nutritionist)->withSession($this->session())
            ->get(route('consents.create'))->assertForbidden();
    }

    private function laboratoryTimeline(): array
    {
        $area = Area::query()->create(['name' => 'Bioquímica']);
        $test = Test::query()->create(['area_id' => $area->id, 'name' => 'Albúmina', 'unit' => 'g/dL', 'type' => 'number']);
        $results = [];
        foreach ([['2026-07-05', '3.7'], ['2026-08-05', '3.9'], ['2026-08-25', '4.0']] as [$date, $value]) {
            $order = LaboratoryOrder::query()->create([
                'patient_id' => $this->patient->id, 'patient_name' => $this->patient->full_name,
                'sampled_at' => $date, 'period' => 'M', 'status' => 'completed',
            ]);
            $results[] = $order->items()->create([
                'test_id' => $test->id, 'result_value' => $value, 'completed_at' => $date.' 12:00:00',
            ]);
        }

        return $results;
    }

    private function session(): array
    {
        return ['current_sede_id' => $this->sede->id, 'current_sede_name' => $this->sede->name];
    }
}
