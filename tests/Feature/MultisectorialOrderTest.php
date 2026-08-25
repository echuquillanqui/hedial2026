<?php

namespace Tests\Feature;

use App\Models\Fua;
use App\Models\Medical;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\User;
use App\Support\ClinicalService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultisectorialOrderTest extends TestCase
{
    use RefreshDatabase;

    private Sede $sede;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-24 09:00:00');
        $this->sede = Sede::query()->create(['name' => 'Sede de prueba', 'code' => 'TST']);
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_nutrition_order_reuses_orders_without_creating_attention_fua_or_dialysis_records(): void
    {
        $nutritionist = User::query()->where('username', 'nutricionista')->firstOrFail();
        $patient = Patient::factory()->create(['sede_id' => $this->sede->id, 'insurance_type' => 'SIS', 'created_at' => now()]);

        $response = $this->actingAs($nutritionist)->withSession($this->sedeSession())->post(
            route('orders.multisectorial.store'),
            [
                'type' => ClinicalService::NUTRITION,
                'patient_id' => $patient->id,
                'assigned_professional_id' => $nutritionist->id,
                'fecha_orden' => '2026-09-10',
            ]
        );

        $response->assertRedirect(route('orders.multisectorial.index', ['type' => ClinicalService::NUTRITION]));
        $this->assertDatabaseHas('orders', [
            'patient_id' => $patient->id,
            'attention_type' => ClinicalService::NUTRITION,
            'assigned_professional_id' => $nutritionist->id,
            'created_by' => $nutritionist->id,
            'due_date' => '2026-09-23',
            'period_key' => '2026-Q3',
            'status' => 'PENDING',
        ]);
        $this->assertSame(0, Fua::query()->count());
        $this->assertSame(0, Medical::query()->count());
    }

    public function test_follow_up_orders_are_due_every_three_months_and_periods_are_unique(): void
    {
        $nutritionist = User::query()->where('username', 'nutricionista')->firstOrFail();
        $patient = Patient::factory()->create(['sede_id' => $this->sede->id, 'insurance_type' => 'SIS', 'created_at' => now()]);
        $payload = [
            'type' => ClinicalService::NUTRITION,
            'patient_id' => $patient->id,
            'assigned_professional_id' => $nutritionist->id,
            'fecha_orden' => '2026-09-10',
        ];

        $this->actingAs($nutritionist)->withSession($this->sedeSession())
            ->post(route('orders.multisectorial.store'), $payload)->assertRedirect();
        $this->actingAs($nutritionist)->withSession($this->sedeSession())
            ->from(route('orders.multisectorial.create', ['type' => ClinicalService::NUTRITION]))
            ->post(route('orders.multisectorial.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasErrors('patient_id');
        $payload['fecha_orden'] = '2026-12-10';
        $this->actingAs($nutritionist)->withSession($this->sedeSession())
            ->post(route('orders.multisectorial.store'), $payload)->assertRedirect();

        $orders = Order::query()->where('attention_type', ClinicalService::NUTRITION)->orderBy('due_date')->get();
        $this->assertSame(['2026-09-23', '2026-12-23'], $orders->pluck('due_date')->map->format('Y-m-d')->all());
        $this->assertSame(['2026-Q3', '2026-Q4'], $orders->pluck('period_key')->all());

        $this->expectException(QueryException::class);
        Order::query()->create($orders->first()->only([
            'patient_id', 'assigned_professional_id', 'created_by', 'sede_id', 'sala', 'turno',
            'attention_type', 'status', 'horas_dialisis', 'fecha_orden', 'due_date', 'period_key',
        ]) + ['codigo_unico' => 'ORD-DUPLICADA']);
    }

    public function test_professionals_only_access_their_sector_and_current_sede(): void
    {
        $nutritionist = User::query()->where('username', 'nutricionista')->firstOrFail();
        $otherSede = Sede::query()->create(['name' => 'Otra sede', 'code' => 'OTR']);
        $otherPatient = Patient::factory()->create(['sede_id' => $otherSede->id, 'insurance_type' => 'SIS']);

        $this->actingAs($nutritionist)->withSession($this->sedeSession())
            ->get(route('orders.multisectorial.index', ['type' => ClinicalService::NUTRITION]))
            ->assertOk()->assertDontSee($otherPatient->full_name);
        $this->actingAs($nutritionist)->withSession($this->sedeSession())
            ->get(route('orders.multisectorial.index', ['type' => ClinicalService::PSYCHOLOGY]))
            ->assertForbidden();
        $this->actingAs($nutritionist)->withSession($this->sedeSession())
            ->get(route('orders.multisectorial.index', ['type' => ClinicalService::SOCIAL_WORK]))
            ->assertForbidden();
    }

    public function test_schedule_exposes_pending_upcoming_overdue_and_completed_states(): void
    {
        $order = new Order(['status' => 'PENDING', 'due_date' => '2026-10-30']);
        $this->assertSame('PENDIENTE', $order->schedule_status);
        $order->due_date = '2026-09-10';
        $this->assertSame('PRÓXIMA', $order->schedule_status);
        $order->due_date = '2026-08-20';
        $this->assertSame('VENCIDA', $order->schedule_status);
        $order->status = 'COMPLETED';
        $this->assertSame('REALIZADA', $order->schedule_status);
    }

    private function sedeSession(): array
    {
        return ['current_sede_id' => $this->sede->id, 'current_sede_name' => $this->sede->name];
    }
}
