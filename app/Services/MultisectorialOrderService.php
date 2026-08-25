<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Patient;
use App\Support\ClinicalService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MultisectorialOrderService
{
    public const PENDING = 'PENDING';
    public const COMPLETED = 'COMPLETED';

    public function create(Patient $patient, string $type, int $professionalId, string $appointmentDate, int $createdBy): Order
    {
        abort_unless(ClinicalService::isMultisectorial($type), 422, 'Tipo de atención multisectorial no válido.');

        return DB::transaction(function () use ($patient, $type, $professionalId, $appointmentDate, $createdBy) {
            $lastOrder = Order::query()
                ->where('patient_id', $patient->id)
                ->where('attention_type', $type)
                ->lockForUpdate()
                ->latest('due_date')
                ->first();

            $dueDate = $this->nextDueDate($patient, $lastOrder);
            $periodKey = $this->periodKey(CarbonImmutable::parse($appointmentDate));

            if (Order::query()
                ->where('patient_id', $patient->id)
                ->where('attention_type', $type)
                ->where('period_key', $periodKey)
                ->exists()) {
                throw ValidationException::withMessages([
                    'patient_id' => 'Ya existe una orden para el paciente en este período.',
                ]);
            }

            return Order::query()->create([
                'patient_id' => $patient->id,
                'assigned_professional_id' => $professionalId,
                'created_by' => $createdBy,
                'sede_id' => $patient->sede_id,
                'codigo_unico' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(str()->random(5)),
                'sala' => ClinicalService::label($type),
                'turno' => $patient->turno ?? 'N/A',
                'attention_type' => $type,
                'status' => self::PENDING,
                'horas_dialisis' => 0.5,
                'fecha_orden' => $appointmentDate,
                'due_date' => $dueDate,
                'period_key' => $periodKey,
            ]);
        });
    }

    public function nextDueDate(Patient $patient, ?Order $lastOrder): CarbonImmutable
    {
        if ($lastOrder) {
            $base = $lastOrder->due_date ?: $lastOrder->fecha_orden;

            return CarbonImmutable::parse($base)->addMonthsNoOverflow(3);
        }

        return CarbonImmutable::parse($patient->created_at)->startOfDay()->addDays(30);
    }

    public function periodKey(CarbonImmutable $dueDate): string
    {
        return $dueDate->year.'-Q'.$dueDate->quarter;
    }
}
