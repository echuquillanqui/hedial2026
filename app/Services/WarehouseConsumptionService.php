<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Warehouse;
use App\Models\WarehouseMaterial;
use App\Models\WarehouseStock;
use App\Models\WarehouseStockMovement;
use Illuminate\Support\Facades\DB;

class WarehouseConsumptionService
{
    public function consumeIfFinalized(Order $order): void
    {
        $order->loadMissing(['medical:id,order_id,hora_final', 'nurse:id,order_id,enfermero_que_finaliza_id', 'treatments:id,order_id,hora']);

        if (! $order->medical?->hora_final
            || ! $order->nurse?->enfermero_que_finaliza_id
            || ! $order->treatments->contains(fn ($treatment) => filled($treatment->hora))) {
            return;
        }

        $this->consumeForOrder($order);
    }

    /**
     * Registers configured consumables once per finalized session and sede.
     */
    public function consumeForOrder(Order $order): void
    {
        if (! $order->sede_id) {
            return;
        }

        DB::transaction(function () use ($order) {
            $warehouse = Warehouse::query()->where('sede_id', $order->sede_id)->first();

            if (! $warehouse) {
                return;
            }

            $materials = WarehouseMaterial::query()
                ->where('is_active', true)
                ->where('automatic_consumption', true)
                ->where('quantity_per_session', '>', 0)
                ->get();

            foreach ($materials as $material) {
                $alreadyConsumed = WarehouseStockMovement::query()
                    ->where('warehouse_id', $warehouse->id)
                    ->where('warehouse_material_id', $material->id)
                    ->where('movement_type', 'out')
                    ->where('reference_type', Order::class)
                    ->where('reference_id', $order->id)
                    ->exists();

                if ($alreadyConsumed) {
                    continue;
                }

                $stock = WarehouseStock::query()->firstOrCreate([
                    'warehouse_id' => $warehouse->id,
                    'warehouse_material_id' => $material->id,
                ], ['current_qty' => 0, 'min_qty' => 0]);
                $stock = WarehouseStock::query()->lockForUpdate()->findOrFail($stock->id);
                $quantity = (float) $material->quantity_per_session;

                // A negative balance makes an unfulfilled consumption visible instead of losing it.
                $stock->update(['current_qty' => (float) $stock->current_qty - $quantity]);

                WarehouseStockMovement::query()->create([
                    'warehouse_id' => $warehouse->id,
                    'warehouse_material_id' => $material->id,
                    'movement_type' => 'out',
                    'qty' => $quantity,
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'performed_by' => auth()->id(),
                    'notes' => 'Consumo automático por sesión finalizada '.$order->codigo_unico,
                ]);
            }
        });
    }
}
