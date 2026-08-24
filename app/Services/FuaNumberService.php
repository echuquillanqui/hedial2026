<?php

namespace App\Services;

use App\Models\Fua;
use App\Models\FuaConfiguration;
use App\Models\Order;
use App\Support\ClinicalService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FuaNumberService
{
    public function createForOrder(Order $order): Fua
    {
        $type = $order->attention_type;
        if (! in_array($type, ClinicalService::ORDINARY_TYPES, true)) {
            throw new InvalidArgumentException('El tipo de atención no admite una FUA ordinaria.');
        }

        return $this->create($type, $order);
    }

    public function create(string $type, ?Order $order = null, ?Fua $correctedFua = null): Fua
    {
        if ($type === Fua::CORRECTION && (! $correctedFua || $correctedFua->type === Fua::CORRECTION)) {
            throw new InvalidArgumentException('La subsanación requiere una FUA ordinaria original.');
        }
        if ($type !== Fua::CORRECTION && ! $order) {
            throw new InvalidArgumentException('La FUA ordinaria requiere una orden.');
        }
        if ($correctedFua && $order?->id !== $correctedFua->order_id) {
            throw new InvalidArgumentException('La subsanación debe conservar la orden de la FUA original.');
        }

        return DB::transaction(function () use ($type, $order, $correctedFua): Fua {
            $configuration = FuaConfiguration::query()->lockForUpdate()->firstOrFail();
            [$seriesField, $counterField] = $this->fieldsFor($type);
            $correlative = (int) $configuration->{$counterField};
            $series = $configuration->{$seriesField};
            $number = $series.'-'.str_pad((string) $correlative, (int) $configuration->number_length, '0', STR_PAD_LEFT);

            $fua = Fua::create([
                'order_id' => $order?->id,
                'responsible_user_id' => $order?->assigned_professional_id,
                'generated_by' => auth()->id(),
                'type' => $type,
                'series' => $series,
                'correlative' => $correlative,
                'number' => $number,
                'corrects_fua_id' => $correctedFua?->id,
            ]);

            $nextNumber = $correlative + 1;
            $configuration->update(in_array($type, ClinicalService::ORDINARY_TYPES, true)
                ? [
                    'hemodialysis_next_number' => $nextNumber,
                    'nephrology_next_number' => $nextNumber,
                ]
                : [$counterField => $nextNumber]);

            return $fua;
        });
    }

    private function fieldsFor(string $type): array
    {
        return match ($type) {
            Fua::HEMODIALYSIS => ['hemodialysis_series', 'hemodialysis_next_number'],
            // Las atenciones de hemodiálisis y las consultas pertenecen a una
            // sola numeración. La subsanación conserva su serie independiente.
            Fua::NEPHROLOGY => ['hemodialysis_series', 'hemodialysis_next_number'],
            Fua::NUTRITION, Fua::PSYCHOLOGY, Fua::SOCIAL_WORK => ['hemodialysis_series', 'hemodialysis_next_number'],
            Fua::CORRECTION => ['correction_series', 'correction_next_number'],
            default => throw new InvalidArgumentException('Tipo de FUA no válido.'),
        };
    }
}
