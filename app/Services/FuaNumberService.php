<?php

namespace App\Services;

use App\Models\Fua;
use App\Models\FuaCorrection;
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
        if ($type === Fua::CORRECTION) {
            throw new InvalidArgumentException('Use createCorrection() para generar una subsanación independiente.');
        }
        if ($type !== Fua::CORRECTION && ! $order) {
            throw new InvalidArgumentException('La FUA ordinaria requiere una orden.');
        }
        return DB::transaction(function () use ($type, $order): Fua {
            $configuration = FuaConfiguration::query()->lockForUpdate()->firstOrFail();
            [$seriesField, $counterField] = $this->fieldsFor($type);
            $correlative = (int) $configuration->{$counterField};
            $series = $configuration->{$seriesField};
            $number = $series.'-'.str_pad((string) $correlative, (int) $configuration->number_length, '0', STR_PAD_LEFT);

            $fua = Fua::create([
                'order_id' => $order->id,
                'responsible_user_id' => $order->assigned_professional_id,
                'generated_by' => auth()->id(),
                'type' => $type,
                'series' => $series,
                'correlative' => $correlative,
                'number' => $number,
            ]);

            $nextNumber = $correlative + 1;
            $configuration->update([
                'hemodialysis_next_number' => $nextNumber,
                'nephrology_next_number' => $nextNumber,
            ]);

            return $fua;
        });
    }

    public function createCorrection(Fua $original): FuaCorrection
    {
        if ($original->type === Fua::CORRECTION || ! in_array($original->type, ClinicalService::ORDINARY_TYPES, true)) {
            throw new InvalidArgumentException('La subsanación requiere una FUA ordinaria original.');
        }

        return DB::transaction(function () use ($original): FuaCorrection {
            $configuration = FuaConfiguration::query()->lockForUpdate()->firstOrFail();
            $correlative = (int) $configuration->correction_next_number;
            $series = $configuration->correction_series;

            $correction = FuaCorrection::create([
                'order_id' => $original->order_id,
                'original_fua_id' => $original->id,
                'responsible_user_id' => $original->responsible_user_id ?: $original->order?->assigned_professional_id,
                'generated_by' => auth()->id(),
                'series' => $series,
                'correlative' => $correlative,
                'number' => $this->correctionNumber($series, $correlative),
            ]);

            $configuration->update(['correction_next_number' => $correlative + 1]);

            return $correction;
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
            Fua::CORRECTION => throw new InvalidArgumentException('La subsanación usa una tabla y numeración independientes.'),
            default => throw new InvalidArgumentException('Tipo de FUA no válido.'),
        };
    }

    private function correctionNumber(string $series, int $correlative): string
    {
        // La letra S forma parte de los ocho caracteres del tramo final:
        // S + siete dígitos. Se acepta que la configuración guarde el prefijo
        // completo o solo el segmento previo a la S.
        $prefix = rtrim(trim($series), '-');
        $prefix = preg_replace('/-S\d*$/i', '', $prefix);

        return $prefix.'-S'.str_pad((string) $correlative, 7, '0', STR_PAD_LEFT);
    }
}
