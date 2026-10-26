<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Nurse;
use App\Models\PatientSectorization;
use App\Models\Order;
use App\Support\DailyHemodialysisSequence;
use App\Support\CurrentSede;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SectorizationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:sectorization.view')->only('index');
        $this->middleware('permission:sectorization.edit')->only('update');
    }

    public function index(Request $request)
    {
        $sedeId = CurrentSede::id();
        abort_unless($sedeId, 403, 'Seleccione una sede para ver la sectorización.');

        $query = Patient::query()
            ->where('sede_id', $sedeId)
            ->where('is_active', true)
            ->with(['orders' => fn ($query) => $query->where('sede_id', $sedeId)
                ->whereHas('nurse', fn ($nurse) => $nurse->whereNotNull('enfermero_que_finaliza_id'))
                ->with('nurse')->orderByDesc('fecha_orden')->orderByDesc('id')])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search')->toString());
                $query->where(fn ($q) => $q->where('dni', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%"));
            })
            ->orderByRaw("CASE turno WHEN '1' THEN 1 WHEN '2' THEN 2 WHEN '3' THEN 3 WHEN '4' THEN 4 ELSE 5 END")
            ->orderByRaw("CASE modulo WHEN '1' THEN 1 WHEN '2' THEN 2 WHEN '3' THEN 3 WHEN '4' THEN 4 WHEN 'AISLADO' THEN 5 ELSE 6 END")
            ->orderBy('surname')->orderBy('last_name')->orderBy('first_name')
            ->orderBy('secuencia');

        $patients = $query->get();

        $patientIds = $patients->modelKeys();
        $sequences = [
            DailyHemodialysisSequence::MONDAY_WEDNESDAY_FRIDAY,
            DailyHemodialysisSequence::TUESDAY_THURSDAY_SATURDAY,
        ];
        $defaultCutoffs = collect($sequences)->mapWithKeys(fn ($sequence) => [$sequence => $this->nextScheduledDate($sequence)]);
        $sequenceCutoffs = $defaultCutoffs->merge(
            DB::table('sectorization_cutoffs')->where('sede_id', $sedeId)->pluck('effective_from', 'sequence')
        );
        $assignments = PatientSectorization::query()
            ->whereIn('patient_id', $patientIds)
            ->whereIn('sequence', $sequences)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get()
            ->unique(fn (PatientSectorization $assignment) => $assignment->patient_id.'|'.$assignment->sequence)
            ->keyBy(fn (PatientSectorization $assignment) => $assignment->patient_id.'|'.$assignment->sequence);
        $nextDates = collect($sequences)->mapWithKeys(fn ($sequence) => [$sequence => $this->nextScheduledDate($sequence)]);
        $nextOrders = Order::query()
            ->where('sede_id', $sedeId)
            ->whereIn('patient_id', $patientIds)
            ->whereIn('fecha_orden', $nextDates->values())
            ->get(['id', 'patient_id', 'fecha_orden', 'sala'])
            ->keyBy(fn ($order) => $order->patient_id.'|'.$order->fecha_orden->toDateString());
        $latestNurses = Order::query()
            ->join('nurses', 'nurses.order_id', '=', 'orders.id')
            ->where('orders.sede_id', $sedeId)
            ->whereIn('orders.patient_id', $patientIds)
            ->whereNotNull('nurses.enfermero_que_finaliza_id')
            ->orderByDesc('orders.fecha_orden')
            ->orderByDesc('orders.id')
            ->get(['orders.patient_id', 'nurses.puesto', 'nurses.numero_maquina'])
            ->unique('patient_id')
            ->keyBy('patient_id');

        foreach ($patients as $patient) {
            $sectorizationBySequence = [];
            foreach ($sequences as $sequence) {
                $nextDate = $nextDates->get($sequence);
                $assignment = $assignments->get($patient->id.'|'.$sequence);
                $nextOrder = $nextOrders->get($patient->id.'|'.$nextDate);
                $latestNurse = $latestNurses->get($patient->id);
                $station = $assignment?->puesto ?? $assignment?->numero_maquina
                    ?? $latestNurse?->puesto ?? $latestNurse?->numero_maquina;

                $sectorizationBySequence[$sequence] = [
                    // The explicit sectorization assignment is authoritative, including
                    // on the cutoff day when an existing order may still have old data.
                    'module' => $assignment?->module ?? $this->moduleFromOrderSala($nextOrder?->sala) ?? $patient->modulo,
                    'puesto' => $station,
                    'numero_maquina' => $station,
                    'effective_from' => $assignment?->effective_from?->toDateString() ?? $nextDate,
                ];
            }

            $patient->setAttribute('sectorization_by_sequence', $sectorizationBySequence);
        }

        $patientsBySequence = collect([
            DailyHemodialysisSequence::MONDAY_WEDNESDAY_FRIDAY,
            DailyHemodialysisSequence::TUESDAY_THURSDAY_SATURDAY,
        ])->mapWithKeys(function ($sequence) use ($patients) {
            $rows = $patients->filter(fn ($patient) =>
                $patient->modulo === Patient::ISOLATED_MODULE || $patient->secuencia === $sequence
            );

            return [$sequence => $rows->sort(function ($left, $right) use ($sequence) {
                $leftModule = $left->sectorization_by_sequence[$sequence]['module'] ?? $left->modulo;
                $rightModule = $right->sectorization_by_sequence[$sequence]['module'] ?? $right->modulo;
                $moduleOrder = ['1' => 1, '2' => 2, '3' => 3, '4' => 4, Patient::ISOLATED_MODULE => 5];
                $moduleComparison = ($moduleOrder[(string) $leftModule] ?? 6) <=> ($moduleOrder[(string) $rightModule] ?? 6);
                if ($moduleComparison !== 0) {
                    return $moduleComparison;
                }

                $leftStation = $left->sectorization_by_sequence[$sequence]['puesto'] ?? $left->orders->first()?->nurse?->puesto;
                $rightStation = $right->sectorization_by_sequence[$sequence]['puesto'] ?? $right->orders->first()?->nurse?->puesto;

                if (($leftStation === null || $leftStation === '') !== ($rightStation === null || $rightStation === '')) {
                    return ($leftStation === null || $leftStation === '') ? 1 : -1;
                }

                return strnatcmp((string) $leftStation, (string) $rightStation)
                    ?: strnatcasecmp($left->full_name, $right->full_name);
            })->values()];
        });

        if ($request->boolean('partial')) {
            $sequence = $request->validate(['sequence' => ['required', 'in:L-M-V,M-J-S']])['sequence'];
            $turn = $request->validate(['turn' => ['required', 'in:1,2,3,4']])['turn'];
            $patients = $patientsBySequence[$sequence]->where('turno', $turn)->values();

            return view('sectorization._turn-table', compact('patients', 'sequence'));
        }

        $minimumCutoff = Carbon::today()->subDays(2)->toDateString();

        return view('sectorization.index', compact('patientsBySequence', 'sequenceCutoffs', 'minimumCutoff'));
    }

    public function update(Request $request, Patient $patient)
    {
        $sedeId = CurrentSede::id();
        abort_unless($sedeId && (int) $patient->sede_id === (int) $sedeId, 404);

        $validated = $request->validate([
            'field' => ['required', 'in:modulo,puesto,numero_maquina,acceso_venoso,acceso_arterial'],
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        $effectiveFrom = null;
        if ($validated['field'] === 'modulo') {
            validator($validated, ['value' => ['required', 'in:1,2,3,4,AISLADO']])->validate();
            [$effectiveFrom, $sequence] = $this->requestedCutoff($request, $patient);
            $effectiveFrom = $this->saveFutureAssignment($patient, $validated['field'], $validated['value'], $effectiveFrom, $sequence);
        } elseif (in_array($validated['field'], ['acceso_venoso', 'acceso_arterial'], true)) {
            $patient->{$validated['field']} = $validated['value'];
            $patient->save();
        } else {
            [$effectiveFrom, $sequence] = $this->requestedCutoff($request, $patient);
            $effectiveFrom = $this->saveFutureAssignment($patient, $validated['field'], $validated['value'], $effectiveFrom, $sequence);
        }

        return response()->json(['saved' => true, 'effective_from' => $effectiveFrom]);
    }

    public function saveCutoff(Request $request)
    {
        $data = $this->validatedCutoff($request);
        $sedeId = CurrentSede::id();
        abort_unless($sedeId, 403, 'Seleccione una sede para guardar la fecha de corte.');

        DB::table('sectorization_cutoffs')->updateOrInsert(
            ['sede_id' => $sedeId, 'sequence' => $data['sequence']],
            ['effective_from' => $data['effective_from'], 'updated_at' => now(), 'created_at' => now()]
        );

        return response()->json(['saved' => true, 'effective_from' => $data['effective_from']]);
    }

    private function requestedCutoff(Request $request, Patient $patient): array
    {
        $data = $this->validatedCutoff($request);
        abort_unless(
            $patient->modulo === Patient::ISOLATED_MODULE || $patient->secuencia === $data['sequence'],
            422,
            'La secuencia elegida no corresponde al paciente.'
        );

        return [$data['effective_from'], $data['sequence']];
    }

    private function validatedCutoff(Request $request): array
    {
        $data = $request->validate([
            'effective_from' => ['required', 'date'],
            'sequence' => ['required', 'in:L-M-V,M-J-S'],
        ]);
        $date = Carbon::parse($data['effective_from'])->toDateString();
        abort_unless($date >= Carbon::today()->subDays(2)->toDateString(), 422, 'La fecha de corte no puede ser anterior a dos días atrás.');
        abort_unless(DailyHemodialysisSequence::forDate($date) === $data['sequence'], 422, 'La fecha de corte debe coincidir con los días de la secuencia seleccionada.');

        return ['effective_from' => $date, 'sequence' => $data['sequence']];
    }

    private function saveFutureAssignment(Patient $patient, string $field, ?string $value, string $effectiveFrom, string $sequence): string
    {
        $assignment = PatientSectorization::query()->firstOrNew([
            'patient_id' => $patient->id,
            'sequence' => $sequence,
            'effective_from' => $effectiveFrom,
        ]);
        $column = match ($field) {
            'modulo' => 'module',
            'puesto' => 'puesto',
            'numero_maquina' => 'numero_maquina',
        };
        $assignment->{$column} = $value;
        if ($field === 'puesto' || $field === 'numero_maquina') {
            $assignment->puesto = $value;
            $assignment->numero_maquina = $value;
        }
        $assignment->save();

        // Keep the patient master record aligned with the current sectorization.
        // Historical orders remain unchanged; only orders on/after the effective
        // date are synchronized below.
        if ($field === 'modulo') {
            $patient->modulo = $value;
            $patient->save();
        }

        $orders = $patient->orders()->where('sede_id', $patient->sede_id)
            ->whereDate('fecha_orden', '>=', $effectiveFrom)->with('nurse')->get()
            ->filter(fn ($order) => DailyHemodialysisSequence::forDate($order->fecha_orden) === $sequence);
        foreach ($orders as $order) {
            if ($field === 'modulo') {
                $order->sala = $value === 'AISLADO' ? 'AISLADO' : 'MODULO '.$value;
                $order->save();
            }

            if ($field !== 'modulo' && ($nurse = $order->nurse)) {
                $nurse->{$column} = $value;
                if ($field === 'puesto') {
                    $nurse->numero_maquina = $value;
                } elseif ($field === 'numero_maquina') {
                    $nurse->puesto = $value;
                }
                $nurse->save();
            }
        }

        return $effectiveFrom;
    }

    public function downloadTemplate()
    {
        abort_unless(CurrentSede::id(), 403, 'Seleccione una sede para descargar la plantilla.');

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_sectorizacion.csv"',
            'Cache-Control' => 'no-store, no-cache',
        ];

        return response()->stream(function () {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['secuencia', 'turno', 'dni', 'modulo', 'puesto_maquina', 'acceso_venoso', 'acceso_arterial'], ';');
            fputcsv($output, ['L-M-V', '1', '12345678', '1', '1', 'FAV', 'FAV'], ';');
            fclose($output);
        }, 200, $headers);
    }

    private function nextScheduledDate(?string $sequence): ?string
    {
        if (! in_array($sequence, [
            DailyHemodialysisSequence::MONDAY_WEDNESDAY_FRIDAY,
            DailyHemodialysisSequence::TUESDAY_THURSDAY_SATURDAY,
        ], true)) {
            return null;
        }

        $date = Carbon::tomorrow();
        for ($day = 0; $day < 7; $day++, $date->addDay()) {
            if (DailyHemodialysisSequence::forDate($date) === $sequence) {
                return $date->toDateString();
            }
        }

        return null;
    }

    private function moduleFromOrderSala(?string $sala): ?string
    {
        if (preg_match('/^MODULO\s+(\d+)$/i', trim((string) $sala), $matches)) {
            return $matches[1];
        }

        return strtoupper(trim((string) $sala)) === 'AISLADO' ? Patient::ISOLATED_MODULE : null;
    }
}
