<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Test;
use App\Support\CurrentSede;
use App\Services\LaboratoryEvolutionXlsxExporter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LaboratoryEvolutionController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:superadmin');
        $this->middleware('permission:laboratory.results.view');
    }

    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        [$from, $to] = $this->period($filters);
        $patientOptions = Patient::query()->active()
            ->where('secuencia', $filters['sequence'])
            ->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede))
            ->orderBy('surname')->orderBy('last_name')->orderBy('first_name')->get();
        $patients = $this->patients($filters['sequence'], $filters['patient'] ?? null, $from, $to);
        $selectedPatient = $patients->firstWhere('id', (int) ($filters['patient'] ?? 0)) ?? $patients->first();
        $months = $this->monthsWithResults(collect([$selectedPatient]), $from, $to);
        $tests = $this->testsByFrequency();

        return view('laboratory.evolution.index', compact('patients', 'patientOptions', 'tests', 'months', 'selectedPatient', 'filters'));
    }

    public function export(Request $request, LaboratoryEvolutionXlsxExporter $exporter)
    {
        $filters = $this->validatedFilters($request);
        [$from, $to] = $this->period($filters);
        $patients = $this->patients($filters['sequence'], null, $from, $to);
        $months = $this->monthsWithResults($patients, $from, $to);
        $tests = $this->testsByFrequency()->flatMap(fn ($group) => $group['tests'])->values();
        $path = $exporter->export($patients, $tests, $months);
        $sequence = str_replace('-', '', $filters['sequence']);

        return response()->download($path, 'EVOLUCION_LABORATORIO_'.$sequence.'_'.$from->format('Ymd').'_AL_'.$to->format('Ymd').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    private function validatedFilters(Request $request): array
    {
        if (! $request->filled('sequence')) {
            $request->merge(['sequence' => match (now()->dayOfWeekIso) {
                2, 4, 6 => 'M-J-S',
                default => 'L-M-V',
            }]);
        }

        return $request->validate([
            'sequence' => ['required', Rule::in(['L-M-V', 'M-J-S'])],
            'patient' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m'],
            'to' => ['nullable', 'date_format:Y-m', 'after_or_equal:from'],
        ]);
    }

    private function period(array $filters): array
    {
        $to = CarbonImmutable::createFromFormat('!Y-m', $filters['to'] ?? now()->format('Y-m'))->endOfMonth();
        $from = CarbonImmutable::createFromFormat('!Y-m', $filters['from'] ?? $to->subMonths(15)->format('Y-m'))->startOfMonth();
        return [$from, $to];
    }

    private function monthsWithResults($patients, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $available = [];
        foreach ($patients as $patient) {
            if (! $patient) continue;
            foreach ($patient->laboratoryOrders as $order) {
                $date = $order->sampled_at ?? $order->created_at;
                if ($date && $order->items->isNotEmpty()) {
                    $key = $date->format('Y-m');
                    $available[$key] = CarbonImmutable::createFromFormat('!Y-m', $key);
                }
            }
        }
        ksort($available);

        return array_values($available);
    }

    private function testsByFrequency()
    {
        $labels = ['M' => 'MENSUAL', 'B' => 'BIMESTRAL', 'T' => 'TRIMESTRAL', 'S' => 'SEMESTRAL'];
        $tests = Test::query()->where('is_fissal', true)->orderBy('name')->get(['id', 'name', 'unit', 'frequency']);

        return collect($labels)->mapWithKeys(fn ($label, $frequency) => [$frequency => [
            'label' => $label,
            'tests' => $tests->where('frequency', $frequency)->values(),
        ]])->filter(fn ($group) => $group['tests']->isNotEmpty());
    }

    private function patients(string $sequence, ?int $patientId, CarbonImmutable $from, CarbonImmutable $to)
    {
        return Patient::query()
            ->active()
            ->where('secuencia', $sequence)
            ->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede))
            ->when($patientId, fn ($query) => $query->whereKey($patientId))
            ->with(['laboratoryOrders' => function ($query) use ($from, $to) {
                $query->with(['items' => fn ($items) => $items->whereNotNull('result_value')->with('test:id,name,unit')])
                    ->where(function ($query) use ($from, $to) {
                        $query->whereBetween('sampled_at', [$from, $to])
                            ->orWhere(function ($query) use ($from, $to) {
                                $query->whereNull('sampled_at')->whereBetween('created_at', [$from, $to]);
                            });
                    })
                    ->orderBy('sampled_at')->orderBy('id');
            }])
            ->orderBy('surname')->orderBy('last_name')->orderBy('first_name')->get();
    }
}
