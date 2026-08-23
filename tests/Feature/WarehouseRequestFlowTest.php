<?php

namespace Tests\Feature;

use App\Models\OperationalArea;
use App\Models\Sede;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseMaterial;
use App\Models\WarehouseRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WarehouseRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_roles_seeder_creates_a_general_logistics_user_for_every_active_sede(): void
    {
        $activeSede = Sede::create(['name' => 'Activa', 'code' => 'ACT', 'is_active' => true]);
        $inactiveSede = Sede::create(['name' => 'Inactiva', 'code' => 'INA', 'is_active' => false]);

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::query()->where('username', 'logistica')->firstOrFail();

        $this->assertSame(1, User::query()->where('username', 'logistica')->count());
        $this->assertSame('Usuario General de Logística', $user->name);
        $this->assertSame('logistica@hemodial.local', $user->email);
        $this->assertTrue(Hash::check('Logistica@123456', $user->password));
        $this->assertTrue($user->hasRole('logistica'));
        $this->assertTrue($user->sedes->contains($activeSede));
        $this->assertFalse($user->sedes->contains($inactiveSede));
    }

    public function test_logistics_sees_requests_from_all_sedes_while_admin_only_sees_assigned_areas(): void
    {
        [$north, $south, $principal, $northWarehouse, $southWarehouse] = $this->structure();
        $northArea = OperationalArea::create(['sede_id' => $north->id, 'name' => 'Farmacia', 'code' => 'FAR', 'is_active' => true]);
        $southArea = OperationalArea::create(['sede_id' => $south->id, 'name' => 'Enfermería', 'code' => 'ENF', 'is_active' => true]);
        $first = $this->warehouseRequest('SOL-NORTE', $northWarehouse, $principal, $northArea);
        $second = $this->warehouseRequest('SOL-SUR', $southWarehouse, $principal, $southArea);

        $logistics = User::factory()->create();
        $logistics->assignRole('logistica');
        $logistics->sedes()->attach($north);

        $this->actingAs($logistics)->withSession(['current_sede_id' => $north->id])
            ->get(route('warehouse.requests.index'))
            ->assertOk()->assertSee($first->request_code)->assertSee($second->request_code);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->sedes()->attach($north);
        $admin->operationalAreas()->attach($northArea);

        $this->actingAs($admin)->withSession(['current_sede_id' => $north->id])
            ->get(route('warehouse.requests.index'))
            ->assertOk()->assertSee($first->request_code)->assertDontSee($second->request_code);
    }

    public function test_only_a_user_assigned_to_requesting_area_can_confirm_receipt(): void
    {
        [$north, , $principal, $northWarehouse] = $this->structure();
        $area = OperationalArea::create(['sede_id' => $north->id, 'name' => 'Farmacia', 'code' => 'FAR', 'is_active' => true]);
        $requestModel = $this->warehouseRequest('SOL-RECEPCION', $northWarehouse, $principal, $area, 'dispatched');
        $material = WarehouseMaterial::create(['code' => 'MAT-01', 'name' => 'Guantes', 'unit' => 'caja']);
        $item = $requestModel->items()->create([
            'warehouse_material_id' => $material->id,
            'qty_requested' => 2, 'qty_approved' => 2, 'qty_sent' => 2,
            'dispatch_status' => 'complete', 'receive_status' => 'pending',
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->sedes()->attach($north);
        $payload = ['items' => [['id' => $item->id, 'receive_status' => 'complete', 'qty_received' => 2]]];

        $this->actingAs($admin)->withSession(['current_sede_id' => $north->id])
            ->post(route('warehouse.requests.receive', $requestModel), $payload)->assertForbidden();

        $admin->operationalAreas()->attach($area);
        $this->actingAs($admin)->withSession(['current_sede_id' => $north->id])
            ->post(route('warehouse.requests.receive', $requestModel), $payload)->assertRedirect();

        $this->assertSame('received', $requestModel->fresh()->status);
        $this->assertSame(2.0, (float) $item->fresh()->qty_received);
    }

    public function test_configuration_selects_exactly_one_principal_warehouse(): void
    {
        [$north, $south] = $this->structure();
        $user = User::factory()->create();
        $user->assignRole('logistica');
        $user->sedes()->attach($north);

        $this->actingAs($user)->withSession(['current_sede_id' => $north->id])
            ->put(route('warehouse.configuration.update'), ['principal_sede_id' => $south->id])
            ->assertRedirect();

        $this->assertSame(1, Warehouse::query()->where('is_principal', true)->count());
        $this->assertTrue((bool) $south->warehouse->fresh()->is_principal);
    }

    private function structure(): array
    {
        $north = Sede::create(['name' => 'Norte', 'code' => 'NOR', 'is_active' => true, 'is_principal' => false]);
        $south = Sede::create(['name' => 'Sur', 'code' => 'SUR', 'is_active' => true, 'is_principal' => false]);
        $principalSede = Sede::create(['name' => 'Central', 'code' => 'CEN', 'is_active' => true, 'is_principal' => true]);
        $northWarehouse = Warehouse::create(['sede_id' => $north->id, 'name' => 'Almacén Norte']);
        $southWarehouse = Warehouse::create(['sede_id' => $south->id, 'name' => 'Almacén Sur']);
        $principal = Warehouse::create(['sede_id' => $principalSede->id, 'name' => 'Almacén Central', 'is_principal' => true]);

        return [$north, $south, $principal, $northWarehouse, $southWarehouse];
    }

    private function warehouseRequest(string $code, Warehouse $from, Warehouse $to, OperationalArea $area, string $status = 'submitted'): WarehouseRequest
    {
        return WarehouseRequest::create([
            'request_code' => $code, 'from_warehouse_id' => $from->id, 'to_warehouse_id' => $to->id,
            'operational_area_id' => $area->id, 'status' => $status,
        ]);
    }
}
