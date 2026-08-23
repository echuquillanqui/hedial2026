<?php

namespace Tests\Feature;

use App\Models\HemodialysisMaterial;
use App\Models\HemodialysisMaterialConsumption;
use App\Models\Order;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HemodialysisMaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_material_with_history_removes_it_from_the_configured_list(): void
    {
        $user = User::factory()->create();
        $patient = Patient::factory()->create();
        $order = Order::create([
            'sede_id' => $patient->sede_id,
            'patient_id' => $patient->id,
            'codigo_unico' => 'ORD-MATERIAL-1',
            'sala' => 'MODULO 1',
            'turno' => '1',
            'fecha_orden' => today(),
            'attention_type' => 'HEMODIALYSIS',
        ]);
        $material = HemodialysisMaterial::create([
            'name' => 'Material que debe desaparecer',
            'unit' => 'unidad',
            'stock' => 10,
            'quantity_per_order' => 1,
            'is_active' => true,
        ]);
        HemodialysisMaterialConsumption::create([
            'hemodialysis_material_id' => $material->id,
            'order_id' => $order->id,
            'patient_id' => $patient->id,
            'consumed_at' => today(),
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->withoutMiddleware()
            ->delete(route('extra-materials.base.destroy', $material))
            ->assertSessionHas('toastr.message', 'Material retirado de la lista. Su historial de atenciones se conserva.');

        $this->assertDatabaseHas('hemodialysis_materials', [
            'id' => $material->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('hemodialysis_material_consumptions', [
            'hemodialysis_material_id' => $material->id,
            'order_id' => $order->id,
        ]);

        $this->actingAs($user)
            ->withoutMiddleware()
            ->get(route('extra-materials.index', ['view' => 'base']))
            ->assertOk()
            ->assertViewHas('hemodialysisMaterials', fn ($materials): bool => ! $materials->contains($material));
    }
}
