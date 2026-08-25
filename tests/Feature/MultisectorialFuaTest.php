<?php

namespace Tests\Feature;

use App\Http\Controllers\FuaController;
use App\Models\Fua;
use App\Models\FuaConfiguration;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\User;
use App\Services\FuaNumberService;
use App\Support\ClinicalService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultisectorialFuaTest extends TestCase
{
    use RefreshDatabase;

    private Sede $sede;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sede = Sede::query()->create(['name' => 'Sede de prueba', 'code' => 'TST']);
        $this->patient = Patient::factory()->create(['sede_id' => $this->sede->id, 'insurance_type' => 'SIS']);
        $this->seed(RolesAndPermissionsSeeder::class);
        FuaConfiguration::global()->update([
            'hemodialysis_series' => 'ORD',
            'hemodialysis_next_number' => 145,
            'nephrology_next_number' => 145,
            'correction_series' => 'SUB-26',
            'correction_next_number' => 8,
            'number_length' => 6,
        ]);
    }

    public function test_all_ordinary_types_share_one_continuous_sequence_and_cpms_catalog(): void
    {
        $user = User::query()->where('username', 'admin.sede.de.prueba')->firstOrFail();
        $this->actingAs($user);
        $service = app(FuaNumberService::class);
        $types = [
            ClinicalService::HEMODIALYSIS,
            ClinicalService::NUTRITION,
            ClinicalService::PSYCHOLOGY,
            ClinicalService::NEPHROLOGY,
            ClinicalService::SOCIAL_WORK,
        ];

        $fuas = collect($types)->map(fn (string $type, int $index) => $service
            ->createForOrder($this->order($type, 'ORD-'.$index)));

        $this->assertSame(
            ['ORD-000145', 'ORD-000146', 'ORD-000147', 'ORD-000148', 'ORD-000149'],
            $fuas->pluck('number')->all()
        );
        $this->assertSame(150, FuaConfiguration::global()->hemodialysis_next_number);
        $this->assertSame(150, FuaConfiguration::global()->nephrology_next_number);
        $this->assertSame(['90937', '99209', '99207', '99215', '99210'], collect($types)
            ->map(fn (string $type) => ClinicalService::cpms($type))->all());
    }

    public function test_nutritionist_generates_only_nutrition_fua_and_duplicate_is_rejected(): void
    {
        $nutritionist = User::query()->where('username', 'nutricionista')->firstOrFail();
        $nutritionOrder = $this->order(ClinicalService::NUTRITION, 'NUT-1', $nutritionist);
        $psychologyOrder = $this->order(ClinicalService::PSYCHOLOGY, 'PSI-1');

        $this->actingAs($nutritionist)->withSession($this->sedeSession())
            ->post(route('fuas.orders.generate', $nutritionOrder))->assertRedirect();
        $this->assertDatabaseHas('fuas', [
            'order_id' => $nutritionOrder->id,
            'type' => ClinicalService::NUTRITION,
            'responsible_user_id' => $nutritionist->id,
            'generated_by' => $nutritionist->id,
        ]);
        $this->actingAs($nutritionist)->withSession($this->sedeSession())
            ->from(route('orders.multisectorial.index', ['type' => ClinicalService::NUTRITION]))
            ->post(route('fuas.orders.generate', $nutritionOrder))
            ->assertRedirect()->assertSessionHasErrors('order');
        $this->actingAs($nutritionist)->withSession($this->sedeSession())
            ->post(route('fuas.orders.generate', $psychologyOrder))->assertForbidden();
    }

    public function test_correction_uses_independent_number_and_keeps_original_link(): void
    {
        $admin = User::query()->where('username', 'admin.sede.de.prueba')->firstOrFail();
        $this->actingAs($admin);
        $numbers = app(FuaNumberService::class);
        $original = $numbers->createForOrder($this->order(ClinicalService::NUTRITION, 'NUT-SUB'));
        $ordinaryNext = FuaConfiguration::global()->hemodialysis_next_number;

        $response = $this->withSession($this->sedeSession())
            ->post(route('fuas.corrections.store', $original));

        $correction = Fua::query()->where('type', Fua::CORRECTION)->firstOrFail();
        $response->assertRedirect(route('fuas.pdf', $correction));
        $this->assertSame('SUB-26-000008', $correction->number);
        $this->assertSame($original->id, $correction->corrects_fua_id);
        $this->assertSame($original->order_id, $correction->order_id);
        $this->assertSame($ordinaryNext, FuaConfiguration::global()->hemodialysis_next_number);
        $this->assertSame(9, FuaConfiguration::global()->correction_next_number);
        $this->assertSame(ClinicalService::NUTRITION, $correction->fresh('correctedFua')->effectiveType());
    }

    public function test_admin_can_generate_mixed_multisectorial_fuas_in_one_batch(): void
    {
        $admin = User::query()->where('username', 'admin.sede.de.prueba')->firstOrFail();
        $orders = [
            $this->order(ClinicalService::NUTRITION, 'MAS-1'),
            $this->order(ClinicalService::PSYCHOLOGY, 'MAS-2'),
            $this->order(ClinicalService::SOCIAL_WORK, 'MAS-3'),
        ];

        $this->actingAs($admin)->withSession($this->sedeSession())
            ->post(route('fuas.multisectorial.generate-bulk'), [
                'orders' => collect($orders)->pluck('id')->all(),
            ])->assertRedirect();

        $this->assertSame(3, Fua::query()->whereIn('type', ClinicalService::MULTISECTORIAL_TYPES)->count());
        $this->assertSame(
            ['ORD-000145', 'ORD-000146', 'ORD-000147'],
            Fua::query()->orderBy('id')->pluck('number')->all()
        );
    }

    public function test_multisectorial_procedure_uses_configured_cpms_in_shared_pdf_data(): void
    {
        $fua = new Fua(['type' => ClinicalService::SOCIAL_WORK]);
        $method = new \ReflectionMethod(FuaController::class, 'procedures');
        $method->setAccessible(true);
        $procedures = $method->invoke(app(FuaController::class), $fua);

        $this->assertSame('99210', $procedures[0]['code']);
        $this->assertSame('TRABAJO SOCIAL', $procedures[0]['description']);
        $this->assertSame(1, $procedures[0]['quantity']);
    }

    public function test_shared_pdf_renders_multisectorial_cpms_and_original_number_for_correction(): void
    {
        $professional = User::query()->where('username', 'nutricionista')->firstOrFail();
        $this->actingAs($professional);
        $original = app(FuaNumberService::class)->createForOrder(
            $this->order(ClinicalService::NUTRITION, 'PDF-1', $professional)
        );
        $correction = app(FuaNumberService::class)->create(Fua::CORRECTION, $original->order, $original);
        $correction->load(['order.patient', 'order.sede', 'responsibleUser', 'correctedFua']);

        $html = view('fuas.pdf', [
            'fua' => $correction,
            'configuration' => FuaConfiguration::global(),
            'logoData' => null,
            'responsible' => $professional,
            'medications' => [],
            'procedures' => [['code' => '99209', 'description' => 'NUTRICIÓN', 'quantity' => 1]],
        ])->render();

        $this->assertStringContainsString($original->number, $html);
        $this->assertStringContainsString('99209', $html);
        $this->assertStringContainsString('NUTRICIONISTA', $html);
        $this->assertStringContainsString('COLEGIATURA', $html);
    }

    private function order(string $type, string $code, ?User $professional = null): Order
    {
        return Order::query()->create([
            'patient_id' => $this->patient->id,
            'sede_id' => $this->sede->id,
            'assigned_professional_id' => $professional?->id,
            'codigo_unico' => $code,
            'sala' => ClinicalService::label($type),
            'turno' => '1',
            'attention_type' => $type,
            'horas_dialisis' => 0.5,
            'fecha_orden' => now()->toDateString(),
        ]);
    }

    private function sedeSession(): array
    {
        return ['current_sede_id' => $this->sede->id, 'current_sede_name' => $this->sede->name];
    }
}
