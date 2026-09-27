<?php

namespace Tests\Feature;

use App\Models\DisposableDiscard;
use App\Models\DialysisSupplyLot;
use App\Models\Medical;
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
        Nurse::create(['order_id'=>$this->order->id,'puesto'=>'1','filtro'=>'FX80','aspecto_dializador'=>'Coagulado','acceso_arterial'=>'FAV','acceso_venoso'=>'FAV','transfusions'=>'No se realizó','dressings'=>'Curación de FAV','enfermero_que_inicia_id'=>$this->nurse->id,'enfermero_que_finaliza_id'=>$this->nurse->id]);
        $lines = HemodialysisMaterial::where('name','like','Líneas de sangre%')->firstOrFail();
        $this->order->hemodialysisMaterialConsumptions()->create(['hemodialysis_material_id'=>$lines->id,'patient_id'=>$patient->id,'consumed_at'=>'2026-08-25','quantity'=>1]);
    }

    public function test_daily_index_reuses_session_dialyzer_lines_and_nursing_data(): void
    {
        $this->actingAs($this->nurse)->withSession($this->session())->get(route('nursing-annexes.index',['date'=>'2026-08-25']))
            ->assertOk()
            ->assertSee('id="annex-12-tab"', false)
            ->assertSee('id="history-tab"', false)
            ->assertSee('id="discards-tab"', false)
            ->assertSee('FX80')->assertSee('Coagulado')->assertSee('Líneas registradas')->assertSee('HD-001');
    }

    public function test_discard_is_unique_per_session_and_category_and_keeps_audit_user(): void
    {
        $payload=['category'=>DisposableDiscard::DIALYZER,'discarded_at'=>'2026-08-25 12:00','lot_number'=>'LOT-1','valid_from'=>'2026-08-01','valid_until'=>'2027-08-01','discard_reason'=>'Coagulación','final_condition'=>'No reutilizable'];
        $this->actingAs($this->nurse)->withSession($this->session())->post(route('nursing-annexes.discards.store',$this->order),$payload)->assertRedirect();
        $this->post(route('nursing-annexes.discards.store',$this->order),$payload+['discard_reason'=>'Ruptura o fuga'])->assertSessionHasErrors('category');
        $this->assertSame(1,DisposableDiscard::count()); $this->assertDatabaseHas('disposable_discards',['order_id'=>$this->order->id,'recorded_by'=>$this->nurse->id,'discard_reason'=>'Coagulación']);
    }

    public function test_annex_screen_explains_where_to_enter_summary_lots_and_displays_saved_values(): void
    {
        $response = $this->actingAs($this->nurse)->withSession($this->session())->get(route('nursing-annexes.index', [
            'date' => '2026-08-25', 'tab' => 'discards',
        ]));

        $response->assertOk()
            ->assertSee('¿Dónde ingreso los datos del cuadro?')
            ->assertSee('Lote del dializador')
            ->assertSee('Lote del set de líneas');

        $this->post(route('nursing-annexes.discards.store', $this->order), [
            'category' => DisposableDiscard::BLOOD_LINES,
            'discarded_at' => '2026-08-25 23:59',
            'lot_number' => 'LINEAS-LOT-99',
            'valid_from' => '2026-08-01',
            'valid_until' => '2027-08-01',
            'discard_reason' => 'Descarte posterior a sesión',
        ])->assertRedirect();

        $this->get(route('nursing-annexes.index', ['date' => '2026-08-25', 'tab' => 'discards']))
            ->assertOk()
            ->assertSee('Líneas: LINEAS-LOT-99')
            ->assertSee('Vigencia: 01/08/2026 al 01/08/2027');
    }

    public function test_lot_is_required_when_registering_summary_data(): void
    {
        $this->actingAs($this->nurse)->withSession($this->session())->post(route('nursing-annexes.discards.store', $this->order), [
            'category' => DisposableDiscard::DIALYZER,
            'discarded_at' => '2026-08-25 23:59',
            'discard_reason' => 'Coagulación',
        ])->assertSessionHasErrors('lot_number');
    }

    public function test_lot_validity_dates_are_required_and_must_be_ordered(): void
    {
        $route = route('nursing-annexes.discards.store', $this->order);
        $payload = [
            'category' => DisposableDiscard::DIALYZER,
            'discarded_at' => '2026-08-25 23:59',
            'lot_number' => 'LOT-DATES',
            'discard_reason' => 'Coagulación',
        ];

        $this->actingAs($this->nurse)->withSession($this->session())->post($route, $payload)
            ->assertSessionHasErrors(['valid_from', 'valid_until']);
        $this->post($route, $payload + ['valid_from' => '2027-01-01', 'valid_until' => '2026-01-01'])
            ->assertSessionHasErrors('valid_until');
        $this->post($route, $payload + ['valid_from' => '2026-01-01', 'valid_until' => '2027-01-01'])
            ->assertRedirect();

        $this->assertDatabaseHas('disposable_discards', [
            'order_id' => $this->order->id,
            'lot_number' => 'LOT-DATES',
            'valid_from' => '2026-01-01',
            'valid_until' => '2027-01-01',
        ]);
    }

    public function test_global_dialyzer_lot_is_recognized_from_medical_measurement(): void
    {
        Medical::create(['order_id' => $this->order->id, 'hora_hd' => 4, 'area_filtro' => '1.8']);
        DialysisSupplyLot::create([
            'category' => DisposableDiscard::DIALYZER, 'measurement' => '1.8', 'lot_number' => 'GLOBAL-18',
            'valid_from' => '2026-08-01', 'valid_until' => '2027-08-01', 'is_active' => true,
        ]);

        $this->actingAs($this->nurse)->withSession($this->session())->get(route('nursing-annexes.index', [
            'date' => '2026-08-25', 'tab' => 'discards',
        ]))->assertOk()->assertSee('1.8 m²')->assertSee('GLOBAL-18')->assertSee('(global)');
    }

    public function test_global_lot_configuration_can_be_registered(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->sedes()->attach($this->sede);

        $this->actingAs($admin)->withSession($this->session())->post(route('dialysis-supply-lots.store'), [
            'category' => DisposableDiscard::DIALYZER, 'measurement' => '2.1', 'lot_number' => 'LOT-21',
            'valid_from' => '2026-09-01', 'valid_until' => '2027-09-01',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('dialysis_supply_lots', ['measurement' => '2.1', 'lot_number' => 'LOT-21', 'is_active' => true]);
    }

    public function test_annex_11_counts_only_one_finalized_session_per_patient_and_day(): void
    {
        $duplicate = Order::create([
            'sede_id' => $this->sede->id, 'patient_id' => $this->order->patient_id,
            'codigo_unico' => 'HD-DUPLICATE', 'attention_type' => ClinicalService::HEMODIALYSIS,
            'fecha_orden' => '2026-08-25', 'turno' => '1', 'sala' => '1',
        ]);
        Nurse::create([
            'order_id' => $duplicate->id,
            'enfermero_que_inicia_id' => $this->nurse->id,
            'enfermero_que_finaliza_id' => $this->nurse->id,
        ]);

        $this->actingAs($this->nurse)->withSession($this->session())->get(route('nursing-annexes.index', [
            'date' => '2026-08-25', 'tab' => 'discards',
        ]))->assertOk()->assertSee('1 finalizadas')->assertSee('HD-DUPLICATE')->assertDontSee('HD-001');
    }

    public function test_annex_11_filters_finalized_sessions_by_shift_module_and_sequence(): void
    {
        $this->order->patient->update(['secuencia' => 'M-J-S']);
        $unfinishedPatient = Patient::factory()->create(['sede_id'=>$this->sede->id, 'modulo'=>'1', 'secuencia'=>'M-J-S']);
        $unfinished = Order::create(['sede_id'=>$this->sede->id,'patient_id'=>$unfinishedPatient->id,'codigo_unico'=>'HD-PENDING','attention_type'=>ClinicalService::HEMODIALYSIS,'fecha_orden'=>'2026-08-25','turno'=>'1','sala'=>'1']);
        Nurse::create(['order_id'=>$unfinished->id,'enfermero_que_inicia_id'=>$this->nurse->id]);

        $this->actingAs($this->nurse)->withSession($this->session())->get(route('nursing-annexes.index', [
            'date'=>'2026-08-25', 'tab'=>'discards', 'discard_shift'=>'1', 'discard_module'=>'1', 'discard_sequence'=>'M-J-S',
        ]))->assertOk()->assertSee('HD-001')->assertDontSee('HD-PENDING')->assertSee('1 finalizadas');
    }

    public function test_annex_11_pdf_and_excel_apply_the_selected_month_and_sequence(): void
    {
        $this->order->patient->update(['secuencia' => 'M-J-S']);
        $otherPatient = Patient::factory()->create(['sede_id' => $this->sede->id, 'modulo' => '1', 'secuencia' => 'L-M-V']);
        $otherOrder = Order::create(['sede_id' => $this->sede->id, 'patient_id' => $otherPatient->id, 'codigo_unico' => 'HD-LMV', 'attention_type' => ClinicalService::HEMODIALYSIS, 'fecha_orden' => '2026-08-26', 'turno' => '1', 'sala' => '1']);
        Nurse::create(['order_id' => $otherOrder->id, 'puesto' => '1', 'filtro' => 'LMV-FILTER', 'enfermero_que_inicia_id' => $this->nurse->id, 'enfermero_que_finaliza_id' => $this->nurse->id]);

        $params = ['category' => DisposableDiscard::DIALYZER, 'month' => '2026-08', 'discard_sequence' => 'M-J-S'];
        $pdf = $this->actingAs($this->nurse)->withSession($this->session())->get(route('nursing-annexes.discards.pdf', $params));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');

        $xlsx = $this->get(route('nursing-annexes.discards.xlsx', $params));
        $xlsx->assertOk()->assertDownload('anexo-11-A-2026-08.xlsx');
        $content = $xlsx->streamedContent();
        $this->assertStringContainsString($this->order->patient->full_name, $content);
        $this->assertStringNotContainsString($otherPatient->full_name, $content);
        $this->assertStringContainsString('Secuencia M-J-S', $content);
    }

    public function test_an_unfinished_session_cannot_receive_a_discard(): void
    {
        $this->order->nurse->update(['enfermero_que_finaliza_id' => null]);

        $this->actingAs($this->nurse)->withSession($this->session())->post(route('nursing-annexes.discards.store',$this->order), [
            'category'=>DisposableDiscard::DIALYZER,'discarded_at'=>'2026-08-25 12:00','discard_reason'=>'Coagulación',
        ])->assertStatus(422);
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
        $this->get(route('nursing-annexes.index', ['history_date' => '2026-08-25', 'history_frequency' => 'LMV']))
            ->assertOk()
            ->assertSee('nav-link active text-nowrap" id="history-tab', false)
            ->assertSee($annex->code);
        $this->get(route('nursing-annexes.care.generated-pdf', $annex))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('nursing-annexes.care.generated-xlsx', $annex))
            ->assertOk()
            ->assertDownload('anexo-12-'.$annex->code.'.xlsx')
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function session(): array { return ['current_sede_id'=>$this->sede->id,'current_sede_name'=>$this->sede->name]; }
}
