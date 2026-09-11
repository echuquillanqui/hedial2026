<?php

namespace Tests\Feature;

use App\Models\DisposableDiscard;
use App\Models\HemodialysisMaterial;
use App\Models\Nurse;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\User;
use App\Support\ClinicalService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NursingAnnexesTest extends TestCase
{
    use RefreshDatabase;
    private Sede $sede; private User $nurse; private Order $order;

    protected function setUp(): void
    {
        parent::setUp(); $this->sede = Sede::create(['name'=>'Sede HD','code'=>'SHD']); $this->seed(RolesAndPermissionsSeeder::class);
        $this->nurse = User::factory()->create(['profession'=>'ENFERMERA']); $this->nurse->assignRole('enfermeria'); $this->nurse->sedes()->attach($this->sede);
        $patient = Patient::factory()->create(['sede_id'=>$this->sede->id, 'modulo'=>'1']);
        $this->order = Order::create(['sede_id'=>$this->sede->id,'patient_id'=>$patient->id,'codigo_unico'=>'HD-001','attention_type'=>ClinicalService::HEMODIALYSIS,'fecha_orden'=>'2026-08-25','turno'=>'1','sala'=>'1']);
        Nurse::create(['order_id'=>$this->order->id,'puesto'=>'1','filtro'=>'FX80','aspecto_dializador'=>'Coagulado','acceso_arterial'=>'FAV','acceso_venoso'=>'FAV','transfusions'=>'No se realizó','dressings'=>'Curación de FAV','enfermero_que_inicia_id'=>$this->nurse->id]);
        $lines = HemodialysisMaterial::where('name','like','Líneas de sangre%')->firstOrFail();
        $this->order->hemodialysisMaterialConsumptions()->create(['hemodialysis_material_id'=>$lines->id,'patient_id'=>$patient->id,'consumed_at'=>'2026-08-25','quantity'=>1]);
    }

    public function test_daily_index_reuses_session_dialyzer_lines_and_nursing_data(): void
    {
        $this->actingAs($this->nurse)->withSession($this->session())->get(route('nursing-annexes.index',['date'=>'2026-08-25']))
            ->assertOk()->assertSee('FX80')->assertSee('Coagulado')->assertSee('Líneas registradas')->assertSee('HD-001');
    }

    public function test_discard_is_unique_per_session_and_category_and_keeps_audit_user(): void
    {
        $payload=['category'=>DisposableDiscard::DIALYZER,'discarded_at'=>'2026-08-25 12:00','lot_number'=>'LOT-1','discard_reason'=>'Coagulación','final_condition'=>'No reutilizable'];
        $this->actingAs($this->nurse)->withSession($this->session())->post(route('nursing-annexes.discards.store',$this->order),$payload)->assertRedirect();
        $this->post(route('nursing-annexes.discards.store',$this->order),$payload+['discard_reason'=>'Ruptura o fuga'])->assertSessionHasErrors('category');
        $this->assertSame(1,DisposableDiscard::count()); $this->assertDatabaseHas('disposable_discards',['order_id'=>$this->order->id,'recorded_by'=>$this->nurse->id,'discard_reason'=>'Coagulación']);
    }

    public function test_unrelated_sector_cannot_access_nursing_annexes(): void
    {
        $psychologist=User::factory()->create();$psychologist->assignRole('psicologo');$psychologist->sedes()->attach($this->sede);
        $this->actingAs($psychologist)->withSession($this->session())->get(route('nursing-annexes.index'))->assertForbidden();
    }

    public function test_annex_12_is_calculated_saved_with_id_and_can_be_filtered(): void
    {
        $this->order->nurse->update(['frecuencia_hd' => 'L-M-V', 'hierro' => '1 ampolla', 'epo2000' => '2000 UI']);
        $values = collect(\App\Services\DailyNursingAnnexService::ROWS)->mapWithKeys(fn ($row, $key) => [$key => ['quantity' => in_array($key, ['iron_ev', 'epo_sc']) ? 1 : 0, 'observations' => '']])->all();

        $response = $this->actingAs($this->nurse)->withSession($this->session())->post(route('nursing-annexes.care.store'), [
            'date' => '2026-08-25', 'frequency' => 'LMV', 'module' => (string) $this->order->patient->modulo, 'values' => $values,
        ]);

        $response->assertRedirect();
        $annex = \App\Models\DailyNursingAnnex::firstOrFail();
        $this->assertStringStartsWith('ANX12-20260825-LMV-M', $annex->code);
        $this->assertSame(1, $annex->automatic_values['iron_ev']['quantity']);
        $this->get(route('nursing-annexes.index', ['history_date' => '2026-08-25', 'history_frequency' => 'LMV']))->assertOk()->assertSee($annex->code);
        $this->get(route('nursing-annexes.care.generated-pdf', $annex))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    private function session(): array { return ['current_sede_id'=>$this->sede->id,'current_sede_name'=>$this->sede->name]; }
}
