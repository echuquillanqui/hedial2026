<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\LaboratoryOrder;
use App\Models\MisAssessment;
use App\Models\NutritionAssessment;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\Test;
use App\Models\User;
use App\Support\ClinicalService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NutritionAndMisTest extends TestCase
{
    use RefreshDatabase;
    private Sede $sede; private User $user; private Patient $patient; private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sede=Sede::create(['name'=>'Sede nutrición','code'=>'NUT']); $this->seed(RolesAndPermissionsSeeder::class);
        $this->user=User::where('username','nutricionista')->firstOrFail(); $this->user->sedes()->syncWithoutDetaching([$this->sede->id]);
        $this->patient=Patient::factory()->create(['sede_id'=>$this->sede->id]);
        $this->order=Order::create(['sede_id'=>$this->sede->id,'patient_id'=>$this->patient->id,'assigned_professional_id'=>$this->user->id,
            'created_by'=>$this->user->id,'codigo_unico'=>'NUT-1','attention_type'=>ClinicalService::NUTRITION,'status'=>'PENDING','fecha_orden'=>'2026-08-01','due_date'=>'2026-08-30','period_key'=>'2026-Q3']);
    }

    public function test_nutrition_uses_latest_result_not_future_and_keeps_historical_references(): void
    {
        $albumin=$this->test('Albúmina','g/dL','M'); $old=$this->result($albumin,'2026-07-05','3.7'); $valid=$this->result($albumin,'2026-08-05','3.9'); $future=$this->result($albumin,'2026-08-25','4.0');
        $ferritin=$this->test('Ferritina','ng/mL','T'); $quarterly=$this->result($ferritin,'2026-07-10','250');
        $this->actingAs($this->user)->withSession($this->session())->post(route('nutrition.store',$this->order), $this->payload())->assertRedirect();
        $assessment=NutritionAssessment::firstOrFail(); $ids=$assessment->laboratoryResults()->pluck('laboratory_order_items.id')->all();
        $this->assertContains($valid->id,$ids); $this->assertContains($quarterly->id,$ids); $this->assertNotContains($old->id,$ids); $this->assertNotContains($future->id,$ids);
        $this->result($albumin,'2026-11-05','4.1');
        $this->assertSameCanonicalizing($ids,$assessment->laboratoryResults()->pluck('laboratory_order_items.id')->all());
        $this->assertFalse(Schema::hasColumn('nutrition_assessments','albumin')); $this->assertSame('COMPLETED',$this->order->fresh()->status);
    }

    public function test_missing_results_are_not_zero_and_display_as_sin_resultado(): void
    {
        $this->actingAs($this->user)->withSession($this->session())->post(route('nutrition.store',$this->order),$this->payload());
        $assessment=NutritionAssessment::firstOrFail();
        $this->actingAs($this->user)->withSession($this->session())->get(route('nutrition.show',$assessment))->assertOk()->assertSee('SIN RESULTADO');
        $this->assertCount(0,$assessment->laboratoryResults);
    }

    public function test_mis_automatically_uses_albumin_and_transferrin_without_copying_values(): void
    {
        $albumin=$this->result($this->test('Albúmina','g/dL','M'),'2026-08-05','3.9');
        $transferrin=$this->result($this->test('Transferrina','mg/dL','T'),'2026-07-05','180');
        $this->actingAs($this->user)->withSession($this->session())->post(route('nutrition.store',$this->order),$this->payload()); $nutrition=NutritionAssessment::firstOrFail();
        $payload=['assessed_at'=>'2026-08-15','weight_change_score'=>1,'dietary_intake_score'=>1,'gastrointestinal_score'=>0,'functional_capacity_score'=>0,'comorbidity_score'=>1,'fat_stores_score'=>1,'muscle_wasting_score'=>1];
        $this->actingAs($this->user)->withSession($this->session())->post(route('mis.store',$nutrition),$payload)->assertRedirect();
        $mis=MisAssessment::firstOrFail(); $this->assertSame($albumin->id,$mis->albumin_result_id); $this->assertSame($transferrin->id,$mis->transferrin_result_id);
        $this->assertSame(1,$mis->albumin_score); $this->assertSame(2,$mis->transferrin_score); $this->assertNull($mis->total_score);
        $this->assertFalse(Schema::hasColumn('mis_assessments','albumin_value'));
    }

    public function test_nutritionist_cannot_edit_laboratory_results(): void
    {
        $lab=LaboratoryOrder::create(['patient_id'=>$this->patient->id,'patient_name'=>'Paciente','sampled_at'=>'2026-08-01','period'=>'M','status'=>'pending']);
        $this->actingAs($this->user)->withSession($this->session())->put(route('laboratory.results.update',$lab),[])->assertForbidden();
    }

    private function payload(): array { return ['assessment_date'=>'2026-08-15','nutritional_diagnosis'=>'Riesgo nutricional','intervention_plan'=>'Seguimiento']; }
    private function test(string $name,string $unit,string $frequency): Test { $area=Area::firstOrCreate(['name'=>'Bioquímica']); return Test::create(['area_id'=>$area->id,'name'=>$name,'unit'=>$unit,'type'=>'number','frequency'=>$frequency]); }
    private function result(Test $test,string $date,string $value) { $order=LaboratoryOrder::create(['patient_id'=>$this->patient->id,'patient_name'=>'Paciente','sampled_at'=>$date,'period'=>$test->frequency,'status'=>'completed']); return $order->items()->create(['test_id'=>$test->id,'result_value'=>$value,'completed_at'=>$date.' 12:00:00']); }
    private function session(): array { return ['current_sede_id'=>$this->sede->id,'current_sede_name'=>$this->sede->name]; }
}
