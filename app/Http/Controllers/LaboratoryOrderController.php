<?php

namespace App\Http\Controllers;

use App\Models\LaboratoryOrder;
use App\Models\LaboratoryOrderItem;
use App\Models\Patient;
use App\Models\Profile;
use App\Models\Test;
use App\Models\User;
use App\Services\LaboratoryResultsXlsxExporter;
use App\Services\LaboratorySealResolver;
use App\Support\CurrentSede;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use ZipArchive;
use Barryvdh\DomPDF\Facade\Pdf;

class LaboratoryOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:laboratory.results.view')->only(['results', 'export', 'show', 'pdf', 'bulkPdf']);
        $this->middleware('permission:laboratory.orders.create')->only(['create', 'store', 'import']);
        $this->middleware('permission:laboratory.results.update')->only(['updateResults', 'bulkUpdate', 'bulkDestroyEmpty', 'removeDuplicates', 'removeWrongSequence']);
    }

    public function create(Request $request)
    {
        $defaultSequence = match (now()->dayOfWeekIso) {
            1, 3, 5 => 'L-M-V',
            2, 4, 6 => 'M-J-S',
            default => null,
        };
        $sequence = in_array($request->query('secuencia'), ['L-M-V', 'M-J-S'], true)
            ? $request->query('secuencia')
            : $defaultSequence;
        $shift = in_array($request->query('turno'), ['1', '2', '3', '4'], true)
            ? $request->query('turno')
            : null;
        $module = in_array($request->query('modulo'), Patient::MODULES, true)
            ? $request->query('modulo')
            : null;

        $tests = Test::with('area:id,name')->where('is_fissal', true)->orderBy('area_id')->orderBy('name')->get();
        $patients = Patient::query()
            ->active()
            ->when(CurrentSede::id(), fn ($query) => $query->where('sede_id', CurrentSede::id()))
            ->when($sequence, fn ($query) => $query->where('secuencia', $sequence))
            ->when($shift, fn ($query) => $query->where('turno', $shift))
            ->when($module, fn ($query) => $query->where('modulo', $module))
            ->orderBy('turno')
            ->orderBy('surname')
            ->orderBy('last_name')
            ->get();
        $profiles = Profile::with(['tests' => fn ($query) => $query->where('is_fissal', true)])
            ->orderBy('name')
            ->get();
        $doctors = $this->doctors()->get(['id', 'name', 'license_number']);

        return view('laboratory.orders.create', compact('tests', 'patients', 'profiles', 'doctors', 'sequence', 'shift', 'module'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_ids' => 'required|array|min:1',
            'patient_ids.*' => ['integer', Rule::exists('patients', 'id')->where('is_active', true)],
            'requested_by' => ['nullable', 'string', 'max:120', Rule::in($this->doctors()->pluck('name'))],
            'schedules' => 'required|array|min:1',
            'schedules.*.sampled_at' => 'required|date',
            'schedules.*.period' => 'required|in:M,B,T,S',
        ]);

        DB::transaction(function () use ($data) {
            Patient::whereIn('id', $data['patient_ids'])->get()->each(function (Patient $patient) use ($data) {
                foreach (collect($data['schedules'])->unique(fn (array $schedule) => $schedule['sampled_at'].'-'.$schedule['period']) as $schedule) {
                    $order = LaboratoryOrder::create([
                        'patient_id' => $patient->id,
                        'patient_name' => $patient->full_name,
                        'requested_by' => $data['requested_by'] ?? null,
                        'period' => $schedule['period'],
                        'sampled_at' => $schedule['sampled_at'],
                        'provenance' => 'FISSAL',
                    ]);

                    $frequencies = $this->includedFrequencies($schedule['period']);
                    $items = Test::where('is_fissal', true)
                        ->whereIn('frequency', $frequencies)
                        ->pluck('id')
                        ->map(fn ($id) => ['test_id' => $id])
                        ->all();
                    $order->items()->createMany($items);
                }
            });
        });

        $total = count(array_unique($data['patient_ids'])) * collect($data['schedules'])
            ->unique(fn (array $schedule) => $schedule['sampled_at'].'-'.$schedule['period'])
            ->count();

        return redirect()->route('laboratory.results.index')->with('success', "Se generaron {$total} órdenes de laboratorio.");
    }

    private function includedFrequencies(string $period): array
    {
        return match ($period) {
            'M' => ['M'],
            'B' => ['M', 'B'],
            'T' => ['M', 'B', 'T'],
            'S' => ['M', 'B', 'T', 'S'],
        };
    }

    private function doctors(): Builder
    {
        return User::query()
            ->where(function ($query) {
                $query->where('profession', 'like', '%MEDIC%')
                    ->orWhere('profession', 'like', '%MÉDIC%')
                    ->orWhere('profession', 'like', '%NEFRO%')
                    ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'medico'));
            })
            ->orderBy('name');
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        $rows = $this->readSpreadsheetRows($data['file']->getRealPath(), $data['file']->getClientOriginalExtension());
        $header = array_shift($rows) ?? [];
        $columns = collect($header)->map(fn ($value) => $this->normalizeHeader($value));
        $requiredColumns = ['dni', 'historia_clinica', 'fecha_toma', 'periodo'];
        if (collect($requiredColumns)->contains(fn ($required) => ! $columns->contains($required))) {
            return back()->withErrors(['file' => 'La plantilla debe incluir DNI, historia_clinica, fecha_toma y periodo. Descargue la plantilla del periodo correspondiente.']);
        }

        $indexes = $columns->flip();
        $testsByColumn = Test::query()->where('is_fissal', true)->get(['id', 'name', 'frequency'])
            ->keyBy(fn (Test $test) => $this->normalizeHeader($test->name));
        $imported = 0;
        $unchanged = 0;
        $skipped = 0;
        $allowedPeriods = ['M', 'B', 'T', 'S'];

        DB::transaction(function () use ($rows, $indexes, $testsByColumn, &$imported, &$unchanged, &$skipped, $allowedPeriods) {
            foreach ($rows as $row) {
                $valueAt = fn (string $column) => trim((string) ($row[$indexes[$column] ?? -1] ?? ''));
                $dni = $valueAt('dni');
                $medicalHistory = $valueAt('historia_clinica');
                $sampledAt = $valueAt('fecha_toma');
                $period = strtoupper($valueAt('periodo'));

                if (($dni === '' && $medicalHistory === '') || $sampledAt === '' || ! in_array($period, $allowedPeriods, true)) {
                    $skipped++;
                    continue;
                }

                try {
                    $date = CarbonImmutable::parse($sampledAt)->toDateString();
                } catch (\Throwable) {
                    $skipped++;
                    continue;
                }

                $patientQuery = Patient::query()
                    ->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede));
                if ($dni !== '') $patientQuery->where('dni', $dni);
                if ($medicalHistory !== '') $patientQuery->where('medical_history_number', $medicalHistory);
                $matchedPatients = $patientQuery->limit(2)->get();
                if ($matchedPatients->count() !== 1) {
                    $skipped++;
                    continue;
                }
                $patient = $matchedPatients->first();

                $frequencyIds = $testsByColumn->filter(fn (Test $test) => in_array($test->frequency, $this->includedFrequencies($period), true))->pluck('id');
                $resultColumns = $indexes->keys()->filter(fn ($column) => $testsByColumn->has($column));
                $order = LaboratoryOrder::query()
                    ->where('patient_id', $patient->id)
                    ->whereDate('sampled_at', $date)
                    ->where('period', $period)
                    ->orderBy('id')
                    ->first();

                if (! $order) {
                    $order = LaboratoryOrder::create([
                        'patient_id' => $patient->id,
                        'patient_name' => $patient->full_name,
                        'period' => $period,
                        'sampled_at' => $date,
                        'provenance' => 'FISSAL',
                        'status' => 'pending',
                    ]);
                }

                $existingItems = $order->items()->get()->keyBy('test_id');
                foreach ($frequencyIds as $testId) {
                    if (! $existingItems->has($testId)) {
                        $existingItems->put($testId, $order->items()->create(['test_id' => $testId]));
                    }
                }

                foreach ($resultColumns as $column) {
                    $test = $testsByColumn[$column];
                    if (! $frequencyIds->contains($test->id)) continue;
                    $result = $valueAt($column);
                    if ($result === '') continue;

                    $item = $existingItems->get($test->id);
                    if (filled($item->result_value) || filled($item->result_notes) || $item->completed_at !== null) {
                        $unchanged++;
                        continue;
                    }

                    $item->update(['result_value' => $result, 'completed_at' => now()]);
                    $imported++;
                }

                $hasPending = $order->items()->whereNull('completed_at')->exists();
                $order->update(['status' => $hasPending ? 'pending' : 'completed']);
            }
        });

        return back()->with('success', "Importación finalizada: {$imported} resultado(s) cargado(s), {$unchanged} existente(s) conservado(s), {$skipped} fila(s) omitida(s).");
    }

    public function downloadImportTemplate(Request $request)
    {
        $data = $request->validate([
            'period' => ['required', Rule::in(['M', 'B', 'T', 'S'])],
            'sampled_at' => ['required', 'date'],
        ]);
        $period = $data['period'];
        $sampledAt = CarbonImmutable::parse($data['sampled_at']);
        $date = $sampledAt->toDateString();
        $tests = Test::query()->where('is_fissal', true)
            ->whereIn('frequency', $this->includedFrequencies($period))
            ->orderBy('area_id')->orderBy('id')->get(['id', 'name']);
        $patients = Patient::query()->active()
            ->whereHas('laboratoryOrders', fn ($orders) => $orders
                ->whereDate('sampled_at', $date)
                ->where('period', $period))
            ->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede))
            ->orderBy('surname')->orderBy('last_name')->orderBy('first_name')->get();

        $patientIds = $patients->pluck('id');
        $testIds = $tests->pluck('id');
        $historicalResults = $patientIds->isEmpty() || $testIds->isEmpty()
            ? collect()
            : LaboratoryOrderItem::query()
                ->select('laboratory_order_items.*')
                ->join('laboratory_orders', 'laboratory_orders.id', '=', 'laboratory_order_items.laboratory_order_id')
                ->whereIn('laboratory_orders.patient_id', $patientIds)
                ->whereIn('laboratory_order_items.test_id', $testIds)
                ->whereRaw('COALESCE(laboratory_orders.sampled_at, DATE(laboratory_orders.created_at)) < ?', [$date])
                ->whereNotNull('laboratory_order_items.result_value')
                ->where('laboratory_order_items.result_value', '<>', '')
                ->with('order:id,patient_id,sampled_at,created_at')
                ->orderByRaw('COALESCE(laboratory_orders.sampled_at, DATE(laboratory_orders.created_at)) DESC')
                ->orderByDesc('laboratory_orders.created_at')
                ->orderByDesc('laboratory_orders.id')
                ->orderByDesc('laboratory_order_items.id')
                ->get()
                ->filter(fn (LaboratoryOrderItem $item) => $item->order !== null)
                ->unique(fn (LaboratoryOrderItem $item) => $item->order->patient_id.'-'.$item->test_id)
                ->keyBy(fn (LaboratoryOrderItem $item) => $item->order->patient_id.'-'.$item->test_id);

        $filename = 'plantilla_resultados_'.$period.'_'.str_replace('-', '', $date).'.csv';
        $testsWithMonthlyVariation = [
            'Cloro', 'Sodio', 'Potasio', 'Calcio total', 'Fósforo inorgánico (fosfato)',
        ];
        $currentOrders = LaboratoryOrder::query()
            ->with('items')
            ->whereIn('patient_id', $patientIds)
            ->whereDate('sampled_at', $date)
            ->where('period', $period)
            ->orderBy('id')
            ->get()
            ->unique('patient_id')
            ->keyBy('patient_id');
        $exportRows = [];

        DB::transaction(function () use ($patients, $tests, $date, $period, $historicalResults, $testsWithMonthlyVariation, $currentOrders, &$exportRows) {
            foreach ($patients as $patient) {
                $order = $currentOrders->get($patient->id);
                $itemsByTest = $order?->items->keyBy('test_id') ?? collect();

                $results = $tests->map(function (Test $test) use ($patient, $order, $itemsByTest, $period, $historicalResults, $testsWithMonthlyVariation) {
                    $previousValue = $historicalResults->get($patient->id.'-'.$test->id)?->result_value;

                    if ($period !== 'M') {
                        return $previousValue ?? '';
                    }

                    if (! in_array($test->name, $testsWithMonthlyVariation, true)) {
                        return '';
                    }

                    $item = $itemsByTest->get($test->id);
                    if (filled($item?->result_value)) {
                        return $item->result_value;
                    }

                    // Keep intentionally blank or otherwise touched items unchanged.
                    if ($item && ($item->completed_at !== null || filled($item->result_notes))) {
                        return '';
                    }

                    if (! is_numeric($previousValue)) {
                        return '';
                    }

                    // Vary the historical value by a random 0.6–1.1 in either direction.
                    $variation = random_int(6, 11) / 10;
                    $direction = random_int(0, 1) === 0 ? -1 : 1;
                    $generatedValue = number_format((float) $previousValue + ($variation * $direction), 1, '.', '');

                    if ($order) {
                        $item ??= $order->items()->create(['test_id' => $test->id]);
                        $item->update(['result_value' => $generatedValue, 'completed_at' => now()]);
                        $itemsByTest->put($test->id, $item);
                    }

                    return $generatedValue;
                })->all();

                if ($order) {
                    $hasPendingItems = $order->items()->whereNull('completed_at')->exists();
                    $order->update(['status' => $hasPendingItems ? 'pending' : 'completed']);
                }

                $exportRows[] = array_merge(
                    [$patient->dni, $patient->medical_history_number, $patient->full_name, $date, $period],
                    $results
                );
            }
        });

        return response()->streamDownload(function () use ($tests, $exportRows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, array_merge(['dni', 'historia_clinica', 'nombres_y_apellidos', 'fecha_toma', 'periodo'], $tests->pluck('name')->all()), ';');
            foreach ($exportRows as $row) {
                fputcsv($output, $row, ';');
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function results(Request $request)
    {
        $orders = LaboratoryOrder::with(['patient', 'items.test.area'])
            ->when($request->filled('q'), fn ($query) => $query->where('patient_name', 'like', '%'.$request->q.'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('period'), fn ($query) => $query->where('period', $request->period))
            ->when($request->filled('date'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->whereDate('sampled_at', $request->date)
                        ->orWhere(function ($query) use ($request) {
                            $query->whereNull('sampled_at')->whereDate('created_at', $request->date);
                        });
                });
            })
            ->when(
                in_array($request->query('sequence'), ['L-M-V', 'M-J-S'], true),
                fn ($query) => $query->whereHas('patient', fn ($query) => $query->where('secuencia', $request->sequence))
            )
            ->orderBy(Patient::select('surname')->whereColumn('patients.id', 'laboratory_orders.patient_id'))
            ->orderBy(Patient::select('last_name')->whereColumn('patients.id', 'laboratory_orders.patient_id'))
            ->orderBy(Patient::select('first_name')->whereColumn('patients.id', 'laboratory_orders.patient_id'))
            ->orderBy('laboratory_orders.id')
            ->paginate(15)->withQueryString();

        return view('laboratory.results.index', compact('orders'));
    }

    public function export(Request $request, LaboratoryResultsXlsxExporter $exporter)
    {
        $data = $request->validate([
            'month' => ['nullable', 'required_without:from', 'date_format:Y-m'],
            'from' => ['nullable', 'required_without:month', 'date_format:Y-m-d', 'required_with:to'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        if (filled($data['month'] ?? null)) {
            $month = CarbonImmutable::createFromFormat('!Y-m', $data['month']);
            $from = $month->startOfMonth();
            $to = $month->endOfMonth();
            $filename = 'RESULTADOS_'.$month->format('Y_m').'.xlsx';
        } else {
            $from = CarbonImmutable::parse($data['from'])->startOfDay();
            $to = CarbonImmutable::parse($data['to'])->endOfDay();
            $filename = 'RESULTADOS_'.$from->format('Ymd').'_AL_'.$to->format('Ymd').'.xlsx';
        }

        $orders = LaboratoryOrder::with(['patient', 'items.test.area'])
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('sampled_at', [$from, $to])
                    ->orWhere(function ($query) use ($from, $to) {
                        $query->whereNull('sampled_at')->whereBetween('created_at', [$from, $to]);
                    });
            })
            ->orderBy('sampled_at')
            ->orderBy('id')
            ->get();

        $monthNames = [
            1 => 'ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO',
            'JULIO', 'AGOSTO', 'SETIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE',
        ];
        if (isset($month)) $filename = $monthNames[$month->month].'_'.$month->year.'.xlsx';

        return response()->download(
            $exporter->export($orders),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend();
    }

    public function show(LaboratoryOrder $laboratoryOrder)
    {
        $laboratoryOrder->load(['patient', 'items.test.area', 'validator']);
        $previousResults = LaboratoryOrderItem::query()
            ->with(['test.area', 'order:id,patient_id,sampled_at,created_at'])
            ->whereHas('order', fn ($query) => $query->where('patient_id', $laboratoryOrder->patient_id)
                ->where('id', '<>', $laboratoryOrder->id))
            ->whereNotNull('result_value')->where('result_value', '<>', '')
            ->get()
            ->sortByDesc(fn (LaboratoryOrderItem $item) => ($item->order->sampled_at?->format('Y-m-d') ?? $item->order->created_at->format('Y-m-d')).'-'.str_pad((string) $item->id, 12, '0', STR_PAD_LEFT))
            ->groupBy(fn (LaboratoryOrderItem $item) => $item->test_id)
            ->map(fn ($items) => $items->first());

        if ($laboratoryOrder->period === 'M') {
            $testsWithMonthlyVariation = [
                'Cloro', 'Sodio', 'Potasio', 'Calcio total', 'Fósforo inorgánico (fosfato)',
            ];

            DB::transaction(function () use ($laboratoryOrder, $previousResults, $testsWithMonthlyVariation) {
                foreach ($laboratoryOrder->items as $item) {
                    if (! in_array($item->test?->name, $testsWithMonthlyVariation, true)
                        || filled($item->result_value)
                        || $item->completed_at !== null
                        || filled($item->result_notes)) {
                        continue;
                    }

                    $previousValue = $previousResults->get($item->test_id)?->result_value;
                    if (! is_numeric($previousValue)) {
                        continue;
                    }

                    // Save the monthly variation the first time this result is viewed.
                    $variation = random_int(6, 11) / 10;
                    $direction = random_int(0, 1) === 0 ? -1 : 1;
                    $item->update([
                        'result_value' => number_format((float) $previousValue + ($variation * $direction), 1, '.', ''),
                        'completed_at' => now(),
                    ]);
                }

                $hasPendingItems = $laboratoryOrder->items()->whereNull('completed_at')->exists();
                $laboratoryOrder->update(['status' => $hasPendingItems ? 'pending' : 'completed']);
            });
        }

        return view('laboratory.results.show', ['order' => $laboratoryOrder, 'previousResults' => $previousResults]);
    }

    public function pdf(LaboratoryOrder $laboratoryOrder, LaboratorySealResolver $sealResolver)
    {
        $laboratoryOrder->load(['patient', 'items.test.area', 'validator']);
        return Pdf::loadView('laboratory.results.pdf', [
            'orders' => collect([$laboratoryOrder]),
            'laboratoryProfessional' => $sealResolver->resolve(),
        ])
            ->setPaper('a4')->stream('laboratorio-'.$laboratoryOrder->id.'.pdf');
    }

    public function bulkPdf(Request $request, LaboratorySealResolver $sealResolver)
    {
        $data = $request->validate(['order_ids' => 'required|array|min:1', 'order_ids.*' => 'exists:laboratory_orders,id']);
        $orders = LaboratoryOrder::with(['patient', 'items.test.area', 'validator'])->whereIn('id', $data['order_ids'])->get();
        $laboratoryProfessional = $sealResolver->resolve();

        return Pdf::loadView('laboratory.results.pdf', compact('orders', 'laboratoryProfessional'))
            ->setPaper('a4')->stream('laboratorios-fissal.pdf');
    }

    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'distinct', 'exists:laboratory_orders,id'],
            'sampled_at' => ['required', 'date'],
        ]);

        $orders = LaboratoryOrder::query()
            ->with('patient:id,sede_id')
            ->whereIn('id', $data['order_ids'])
            ->get();

        abort_if(CurrentSede::id() && $orders->contains(
            fn (LaboratoryOrder $order) => $order->patient && (int) $order->patient->sede_id !== (int) CurrentSede::id()
        ), 403, 'Una de las órdenes de laboratorio está fuera de la sede activa.');

        LaboratoryOrder::query()
            ->whereIn('id', $orders->pluck('id'))
            ->update(['sampled_at' => $data['sampled_at']]);

        return back()->with('success', $orders->count().' órdenes de laboratorio cambiadas a la nueva fecha.');
    }

    public function bulkDestroyEmpty(Request $request)
    {
        $data = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'distinct', 'exists:laboratory_orders,id'],
        ]);

        [$deleted, $protected] = DB::transaction(function () use ($data) {
            $orders = LaboratoryOrder::query()
                ->with('patient:id,sede_id')
                ->whereIn('id', $data['order_ids'])
                ->lockForUpdate()
                ->get();

            abort_if(CurrentSede::id() && $orders->contains(
                fn (LaboratoryOrder $order) => $order->patient && (int) $order->patient->sede_id !== (int) CurrentSede::id()
            ), 403, 'Una de las órdenes de laboratorio está fuera de la sede activa.');

            $deleted = 0;
            $protected = 0;

            foreach ($orders as $order) {
                $hasData = $order->items()->where(function ($query) {
                    $query->whereNotNull('result_value')->where('result_value', '<>', '')
                        ->orWhereNotNull('result_notes')->where('result_notes', '<>', '')
                        ->orWhereNotNull('completed_at');
                })->exists();

                $hasReferences = DB::table('laboratory_order_items')
                    ->join('initial_history_laboratory_results', 'initial_history_laboratory_results.laboratory_order_item_id', '=', 'laboratory_order_items.id')
                    ->where('laboratory_order_items.laboratory_order_id', $order->id)
                    ->exists()
                    || DB::table('laboratory_order_items')
                        ->join('nutrition_assessment_laboratory_results', 'nutrition_assessment_laboratory_results.laboratory_order_item_id', '=', 'laboratory_order_items.id')
                        ->where('laboratory_order_items.laboratory_order_id', $order->id)
                        ->exists()
                    || DB::table('laboratory_order_items')
                        ->join('viral_investigation_laboratory_results', 'viral_investigation_laboratory_results.laboratory_order_item_id', '=', 'laboratory_order_items.id')
                        ->where('laboratory_order_items.laboratory_order_id', $order->id)
                        ->exists()
                    || DB::table('mis_assessments')
                        ->where(function ($query) use ($order) {
                            $query->whereIn('albumin_result_id', $order->items()->select('id'))
                                ->orWhereIn('transferrin_result_id', $order->items()->select('id'));
                        })->exists();

                if ($hasData || $hasReferences) {
                    $protected++;
                    continue;
                }

                $order->delete();
                $deleted++;
            }

            return [$deleted, $protected];
        });

        $message = $deleted.' orden(es) vacía(s) eliminada(s).';
        if ($protected > 0) {
            $message .= ' Se conservaron '.$protected.' porque tienen resultados o están vinculadas a datos clínicos.';
        }

        return back()->with('success', $message);
    }

    public function removeDuplicates(Request $request)
    {
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'period' => ['nullable', Rule::in(['M', 'B', 'T', 'S'])],
            'sequence' => ['nullable', Rule::in(['L-M-V', 'M-J-S'])],
        ]);

        $groups = LaboratoryOrder::query()
            ->select('patient_id', 'sampled_at', 'period')
            ->whereNotNull('patient_id')
            ->whereNotNull('sampled_at')
            ->when(! empty($data['date']), fn ($query) => $query->whereDate('sampled_at', $data['date']))
            ->when(empty($data['date']), fn ($query) => $query->whereBetween('sampled_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]))
            ->when(! empty($data['period']), fn ($query) => $query->where('period', $data['period']))
            ->when(in_array($data['sequence'] ?? null, ['L-M-V', 'M-J-S'], true), fn ($query) => $query->whereHas('patient', fn ($patient) => $patient->where('secuencia', $data['sequence'])) )
            ->when(CurrentSede::id(), fn ($query, $sede) => $query->whereHas('patient', fn ($patient) => $patient->where('sede_id', $sede)))
            ->groupBy('patient_id', 'sampled_at', 'period')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        [$deleted, $protected, $groupsProcessed] = DB::transaction(function () use ($groups) {
            $deleted = 0;
            $protected = 0;
            $groupsProcessed = 0;

            foreach ($groups as $group) {
                $orders = LaboratoryOrder::query()
                    ->where('patient_id', $group->patient_id)
                    ->whereDate('sampled_at', $group->sampled_at)
                    ->where('period', $group->period)
                    ->withCount(['items as data_items_count' => fn ($items) => $items->where(function ($query) {
                        $query->whereNotNull('result_value')->where('result_value', '<>', '')
                            ->orWhereNotNull('result_notes')->where('result_notes', '<>', '')
                            ->orWhereNotNull('completed_at');
                    })])
                    ->withCount('items')
                    ->orderByDesc('data_items_count')
                    ->orderByDesc('items_count')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $keep = $orders->first();
                $groupProtected = false;
                foreach ($orders->skip(1) as $duplicate) {
                    $hasReferences = DB::table('laboratory_order_items')
                        ->join('initial_history_laboratory_results', 'initial_history_laboratory_results.laboratory_order_item_id', '=', 'laboratory_order_items.id')
                        ->where('laboratory_order_items.laboratory_order_id', $duplicate->id)
                        ->exists()
                        || DB::table('laboratory_order_items')
                            ->join('nutrition_assessment_laboratory_results', 'nutrition_assessment_laboratory_results.laboratory_order_item_id', '=', 'laboratory_order_items.id')
                            ->where('laboratory_order_items.laboratory_order_id', $duplicate->id)
                            ->exists()
                        || DB::table('laboratory_order_items')
                            ->join('viral_investigation_laboratory_results', 'viral_investigation_laboratory_results.laboratory_order_item_id', '=', 'laboratory_order_items.id')
                            ->where('laboratory_order_items.laboratory_order_id', $duplicate->id)
                            ->exists()
                        || DB::table('mis_assessments')
                            ->where(function ($query) use ($duplicate) {
                                $query->whereIn('albumin_result_id', $duplicate->items()->select('id'))
                                    ->orWhereIn('transferrin_result_id', $duplicate->items()->select('id'));
                            })->exists();

                    if ($hasReferences) {
                        $protected++;
                        $groupProtected = true;
                        continue;
                    }

                    foreach ($duplicate->items as $duplicateItem) {
                        $keptItem = $keep->items()->where('test_id', $duplicateItem->test_id)->first();
                        if (! $keptItem) {
                            $duplicateItem->update(['laboratory_order_id' => $keep->id]);
                            continue;
                        }

                        $duplicateHasData = filled($duplicateItem->result_value)
                            || filled($duplicateItem->result_notes)
                            || $duplicateItem->completed_at !== null;
                        $keptHasData = filled($keptItem->result_value)
                            || filled($keptItem->result_notes)
                            || $keptItem->completed_at !== null;

                        if ($duplicateHasData && $keptHasData) {
                            $protected++;
                            $groupProtected = true;
                            break;
                        }

                        if ($duplicateHasData) {
                            $keptItem->update([
                                'result_value' => $duplicateItem->result_value,
                                'result_notes' => $duplicateItem->result_notes,
                                'completed_at' => $duplicateItem->completed_at,
                            ]);
                        }
                    }

                    if ($groupProtected) {
                        continue;
                    }

                    $duplicate->delete();
                    $deleted++;
                }

                if (! $groupProtected) {
                    $groupsProcessed++;
                }
            }

            return [$deleted, $protected, $groupsProcessed];
        });

        $message = $groupsProcessed.' grupo(s) revisado(s); '.$deleted.' duplicado(s) eliminado(s) y se conservó al menos una orden por paciente, fecha y periodo.';
        if ($protected > 0) {
            $message .= ' Se conservaron '.$protected.' duplicado(s) adicional(es) porque tienen resultados o vínculos clínicos.';
        }

        return back()->with('success', $message);
    }

    public function removeWrongSequence(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $sampleDate = CarbonImmutable::createFromFormat('!Y-m-d', $data['date']);
        $expectedSequence = match ($sampleDate->dayOfWeekIso) {
            1, 3, 5 => 'L-M-V',
            2, 4, 6 => 'M-J-S',
            default => null,
        };

        if ($expectedSequence === null) {
            return back()->with('success', 'La fecha seleccionada cae domingo y no tiene una secuencia de laboratorio asignada. No se eliminó ningún registro.');
        }

        [$deleted, $protected] = DB::transaction(function () use ($data, $expectedSequence) {
            $orders = LaboratoryOrder::query()
                ->whereDate('sampled_at', $data['date'])
                ->whereNotNull('patient_id')
                ->whereHas('patient', fn ($patient) => $patient->whereNotNull('secuencia')->where('secuencia', '<>', $expectedSequence))
                ->when(CurrentSede::id(), fn ($query, $sede) => $query->whereHas('patient', fn ($patient) => $patient->where('sede_id', $sede)))
                ->with('items')
                ->lockForUpdate()
                ->get();

            $deleted = 0;
            $protected = 0;

            foreach ($orders as $order) {
                $hasData = $order->items->contains(fn (LaboratoryOrderItem $item) => filled($item->result_value)
                    || filled($item->result_notes)
                    || $item->completed_at !== null);

                $itemIds = $order->items->pluck('id');
                $hasReferences = $itemIds->isNotEmpty() && (
                    (Schema::hasTable('initial_history_laboratory_results') && DB::table('initial_history_laboratory_results')->whereIn('laboratory_order_item_id', $itemIds)->exists())
                    || (Schema::hasTable('nutrition_assessment_laboratory_results') && DB::table('nutrition_assessment_laboratory_results')->whereIn('laboratory_order_item_id', $itemIds)->exists())
                    || (Schema::hasTable('viral_investigation_laboratory_results') && DB::table('viral_investigation_laboratory_results')->whereIn('laboratory_order_item_id', $itemIds)->exists())
                    || (Schema::hasTable('mis_assessments') && DB::table('mis_assessments')->where(function ($query) use ($itemIds) {
                        if (Schema::hasColumn('mis_assessments', 'albumin_result_id')) {
                            $query->whereIn('albumin_result_id', $itemIds);
                        }
                        if (Schema::hasColumn('mis_assessments', 'transferrin_result_id')) {
                            $query->orWhereIn('transferrin_result_id', $itemIds);
                        }
                    })->exists())
                );

                if ($hasData || $hasReferences) {
                    $protected++;
                    continue;
                }

                $order->delete();
                $deleted++;
            }

            return [$deleted, $protected];
        });

        $message = "Se eliminaron {$deleted} órdenes vacías de la fecha {$sampleDate->format('d/m/Y')} que no correspondían a la secuencia {$expectedSequence}.";
        if ($protected > 0) {
            $message .= " Se conservaron {$protected} órdenes porque tienen resultados o vínculos clínicos.";
        }

        return back()->with('success', $message);
    }

    public function updateResults(Request $request, LaboratoryOrder $laboratoryOrder)
    {
        $payload = $request->validate([
            'results' => 'required|array',
            'results.*.value' => 'nullable|string|max:255',
            'results.*.notes' => 'nullable|string|max:1000',
        ]);

        foreach ($laboratoryOrder->items as $item) {
            $result = $payload['results'][$item->id] ?? null;
            if (! $result) {
                continue;
            }

            $item->update([
                'result_value' => $result['value'] ?? null,
                'result_notes' => $result['notes'] ?? null,
                'completed_at' => filled($result['value'] ?? null) ? now() : null,
            ]);
        }

        $hasPending = LaboratoryOrderItem::where('laboratory_order_id', $laboratoryOrder->id)
            ->whereNull('completed_at')
            ->exists();

        $laboratoryOrder->update([
            'status' => $hasPending ? 'pending' : 'completed',
            'validated_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Resultados actualizados.');
    }

    public function updateDigitalSeal(Request $request)
    {
        $data = $request->validate([
            'digital_seal' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $previousSeal = $user->digital_seal_path;
        $path = $data['digital_seal']->store('users/digital-seals', 'public');
        $user->update(['digital_seal_path' => $path]);

        if ($previousSeal) {
            Storage::disk('public')->delete($previousSeal);
        }

        return back()->with('success', 'Sello digital actualizado correctamente.');
    }

    public function destroyDigitalSeal(Request $request)
    {
        $user = $request->user();

        if ($user->digital_seal_path) {
            Storage::disk('public')->delete($user->digital_seal_path);
            $user->update(['digital_seal_path' => null]);
        }

        return back()->with('success', 'Sello digital eliminado.');
    }

    private function normalizeHeader(mixed $value): string
    {
        return Str::of(ltrim((string) $value, "\xEF\xBB\xBF"))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    private function readSpreadsheetRows(string $path, string $extension): array
    {
        return strtolower($extension) === 'xlsx'
            ? $this->readXlsxRows($path)
            : $this->readDelimitedRows($path);
    }

    private function readDelimitedRows(string $path): array
    {
        $handle = fopen($path, 'r');
        $sample = fgets($handle) ?: '';
        rewind($handle);
        $delimiter = substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';
        $rows = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            return [];
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            foreach (simplexml_load_string($sharedXml)->si as $item) {
                $sharedStrings[] = (string) ($item->t ?? $item->r->t ?? '');
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $rows = [];
        foreach (simplexml_load_string($sheetXml)->sheetData->row as $rowNode) {
            $row = [];
            foreach ($rowNode->c as $cell) {
                $reference = (string) $cell['r'];
                $index = $this->columnIndexFromReference($reference);
                $value = (string) $cell->v;
                $row[$index] = ((string) $cell['t']) === 's' ? ($sharedStrings[(int) $value] ?? '') : $value;
            }
            if ($row === []) {
                $rows[] = [];
                continue;
            }

            $rows[] = array_map(fn ($index) => $row[$index] ?? '', range(0, max(array_keys($row))));
        }

        return $rows;
    }

    private function columnIndexFromReference(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($reference));
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }
}
