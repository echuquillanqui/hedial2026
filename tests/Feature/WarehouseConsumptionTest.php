<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Patient;
use App\Models\Sede;
use App\Models\Warehouse;
use App\Models\WarehouseMaterial;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockMovement;
use App\Services\WarehouseConsumptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseConsumptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_automatic_consumption_is_sede_scoped_and_idempotent(): void
    {
        $sede = Sede::create(['name' => 'Sede Norte', 'code' => 'NOR', 'is_active' => true]);
        $otherSede = Sede::create(['name' => 'Sede Sur', 'code' => 'SUR', 'is_active' => true]);
        $warehouse = Warehouse::create(['sede_id' => $sede->id, 'name' => 'Almacén Norte']);
        $otherWarehouse = Warehouse::create(['sede_id' => $otherSede->id, 'name' => 'Almacén Sur']);
        $material = WarehouseMaterial::create([
            'code' => 'AUTO-01',
            'name' => 'Kit de sesión',
            'unit' => 'kit',
            'automatic_consumption' => true,
            'quantity_per_session' => 2,
        ]);
        $stock = WarehouseStock::create(['warehouse_id' => $warehouse->id, 'warehouse_material_id' => $material->id, 'current_qty' => 10, 'min_qty' => 2]);
        $otherStock = WarehouseStock::create(['warehouse_id' => $otherWarehouse->id, 'warehouse_material_id' => $material->id, 'current_qty' => 8, 'min_qty' => 2]);
        $patient = Patient::factory()->create(['sede_id' => $sede->id]);
        $order = Order::create([
            'sede_id' => $sede->id,
            'patient_id' => $patient->id,
            'codigo_unico' => 'SES-001',
            'sala' => 'Módulo 1',
            'turno' => '1',
            'fecha_orden' => today(),
        ]);

        $service = app(WarehouseConsumptionService::class);
        $service->consumeForOrder($order);
        $service->consumeForOrder($order);

        $this->assertSame(8.0, (float) $stock->fresh()->current_qty);
        $this->assertSame(8.0, (float) $otherStock->fresh()->current_qty);
        $this->assertSame(1, WarehouseStockMovement::query()->where('reference_type', Order::class)->where('reference_id', $order->id)->count());
    }

    public function test_automatic_consumption_records_shortage_as_visible_negative_stock(): void
    {
        $sede = Sede::create(['name' => 'Sede Centro', 'code' => 'CEN', 'is_active' => true]);
        $warehouse = Warehouse::create(['sede_id' => $sede->id, 'name' => 'Almacén Centro']);
        $material = WarehouseMaterial::create(['code' => 'AUTO-02', 'name' => 'Filtro', 'unit' => 'unidad', 'automatic_consumption' => true, 'quantity_per_session' => 2]);
        $stock = WarehouseStock::create(['warehouse_id' => $warehouse->id, 'warehouse_material_id' => $material->id, 'current_qty' => 1, 'min_qty' => 2]);
        $patient = Patient::factory()->create(['sede_id' => $sede->id]);
        $order = Order::create(['sede_id' => $sede->id, 'patient_id' => $patient->id, 'codigo_unico' => 'SES-002', 'sala' => 'Módulo 1', 'turno' => '1', 'fecha_orden' => today()]);

        app(WarehouseConsumptionService::class)->consumeForOrder($order);

        $this->assertSame(-1.0, (float) $stock->fresh()->current_qty);
        $this->assertDatabaseHas('warehouse_stock_movements', ['warehouse_id' => $warehouse->id, 'warehouse_material_id' => $material->id, 'movement_type' => 'out', 'qty' => 2]);
    }
}
