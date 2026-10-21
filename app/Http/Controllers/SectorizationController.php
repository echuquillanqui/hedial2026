<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Nurse;
use App\Support\DailyHemodialysisSequence;
use App\Support\CurrentSede;
use Carbon\Carbon;
use Illuminate\Http\Request;

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

        $patientsBySequence = collect([
            'L-M-V' => $patients->filter(fn ($patient) => $patient->modulo === Patient::ISOLATED_MODULE || $patient->secuencia === 'L-M-V')->values(),
            'M-J-S' => $patients->filter(fn ($patient) => $patient->modulo === Patient::ISOLATED_MODULE || $patient->secuencia === 'M-J-S')->values(),
        ]);

        if ($request->boolean('partial')) {
            $sequence = $request->validate(['sequence' => ['required', 'in:L-M-V,M-J-S']])['sequence'];
            $turn = $request->validate(['turn' => ['required', 'in:1,2,3,4']])['turn'];
            $patients = $patientsBySequence[$sequence]->where('turno', $turn)->values();

            return view('sectorization._turn-table', compact('patients'));
        }

        return view('sectorization.index', compact('patientsBySequence'));
    }

    public function update(Request $request, Patient $patient)
    {
        $sedeId = CurrentSede::id();
        abort_unless($sedeId && (int) $patient->sede_id === (int) $sedeId, 404);

        $validated = $request->validate([
            'field' => ['required', 'in:modulo,puesto,numero_maquina,acceso_venoso,acceso_arterial'],
            'value' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['field'] === 'modulo') {
            validator($validated, ['value' => ['required', 'in:1,2,3,4,AISLADO']])->validate();
            $patient->modulo = $validated['value'] === 'AISLADO' ? null : $validated['value'];
            $patient->save();
        } elseif (in_array($validated['field'], ['acceso_venoso', 'acceso_arterial'], true)) {
            $patient->{$validated['field']} = $validated['value'];
            $patient->save();
        } else {
            $latestOrder = $patient->orders()
                ->where('sede_id', $sedeId)
                ->whereHas('nurse', fn ($query) => $query->whereNotNull('enfermero_que_finaliza_id'))
                ->with('nurse')
                ->orderByDesc('fecha_orden')->orderByDesc('id')
                ->first();

            abort_unless($latestOrder?->nurse, 422, 'El paciente no tiene una hoja de enfermería para actualizar el puesto o máquina.');
            $latestOrder->nurse->puesto = $validated['value'];
            $latestOrder->nurse->numero_maquina = $validated['value'];
            $latestOrder->nurse->save();

            $nextDate = $this->nextScheduledDate($patient->secuencia);
            if ($nextDate) {
                Nurse::query()
                    ->whereHas('order', fn ($query) => $query
                        ->where('patient_id', $patient->id)
                        ->where('sede_id', $sedeId)
                        ->whereDate('fecha_orden', $nextDate))
                    ->update([
                        'puesto' => $validated['value'],
                        'numero_maquina' => $validated['value'],
                    ]);
            }
        }

        return response()->json(['saved' => true]);
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
}
