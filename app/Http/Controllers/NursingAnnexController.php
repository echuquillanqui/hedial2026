<?php

namespace App\Http\Controllers;

use App\Models\DisposableDiscard;
use App\Models\DailyNursingAnnex;
use App\Models\Order;
use App\Models\Patient;
use App\Services\DailyNursingAnnexService;
use App\Services\NursingAnnexXlsxExporter;
use App\Services\PdfBrandingService;
use App\Support\ClinicalService;
use App\Support\CurrentSede;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class NursingAnnexController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:annexes.nursing.view')->only('index');
        $this->middleware('permission:annexes.nursing.record')->only(['storeDiscard', 'storeCare']);
        $this->middleware('permission:annexes.nursing.print')->only(['discardPdf', 'discardXlsx', 'carePdf', 'generatedCarePdf', 'generatedCareXlsx']);
    }

    public function index(Request $request, DailyNursingAnnexService $service)
    {
        $date = $request->date('date')?->format('Y-m-d') ?? today()->format('Y-m-d');
        $frequency = $this->frequency($request->input('frequency', $this->frequencyForDate($date)));
        $module = in_array((string) $request->input('module', '1'), Patient::MODULES, true) ? (string) $request->input('module', '1') : '1';
        $discardFilters = $this->discardFilters($request);
        $orders = $this->orders($date, $discardFilters);
        $discardRows = $this->discardRows($orders);
        $annexOrders = $this->annexOrders($orders, $frequency, $module);
        $automaticValues = $service->calculate($annexOrders);
        $annex = DailyNursingAnnex::where(['sede_id' => CurrentSede::id(), 'work_date' => $date, 'frequency' => $frequency, 'module' => $module])->first();
        $values = $annex?->values ?? $automaticValues;
        $history = DailyNursingAnnex::with('generator')->where('sede_id', CurrentSede::id())
            ->when($request->filled('history_date'), fn ($q) => $q->whereDate('work_date', $request->input('history_date')))
            ->when($request->filled('history_frequency'), fn ($q) => $q->where('frequency', $this->frequency($request->input('history_frequency'))))
            ->latest('work_date')->latest('id')->paginate(12)->withQueryString();
        return view('nursing-annexes.index', compact('orders', 'discardRows', 'annexOrders', 'date', 'frequency', 'module', 'automaticValues', 'values', 'annex', 'history', 'discardFilters'));
    }

    public function storeCare(Request $request, DailyNursingAnnexService $service)
    {
        $data = $request->validate([
            'date' => ['required', 'date'], 'frequency' => ['required', 'in:LMV,MJS'], 'module' => ['required', 'in:'.implode(',', Patient::MODULES)],
            'values' => ['required', 'array'], 'values.*.quantity' => ['required', 'integer', 'min:0', 'max:9999'],
            'values.*.shifts' => ['nullable', 'array'], 'values.*.shifts.*' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'values.*.observations' => ['nullable', 'string', 'max:1000'],
        ]);
        $orders = $this->annexOrders($this->orders($data['date']), $data['frequency'], $data['module']);
        $automatic = $service->calculate($orders);
        $values = [];
        foreach (DailyNursingAnnexService::ROWS as $key => $_) {
            $shifts = collect(['1', '2', '3', '4'])->mapWithKeys(fn ($shift) => [$shift => (int) data_get($data, "values.$key.shifts.$shift", 0)])->all();
            $values[$key] = ['quantity' => array_sum($shifts), 'shifts' => $shifts, 'observations' => trim((string) data_get($data, "values.$key.observations", ''))];
        }
        $code = 'ANX12-'.str_replace('-', '', $data['date']).'-'.$data['frequency'].'-M'.$data['module'].'-S'.CurrentSede::id();
        $annex = DailyNursingAnnex::updateOrCreate(
            ['sede_id' => CurrentSede::id(), 'work_date' => $data['date'], 'frequency' => $data['frequency'], 'module' => $data['module']],
            ['code' => $code, 'automatic_values' => $automatic, 'values' => $values, 'generated_by' => $request->user()->id]
        );
        return redirect()->route('nursing-annexes.index', ['date' => $data['date'], 'frequency' => $data['frequency'], 'module' => $data['module']])
            ->with('success', "Anexo 12 {$annex->code} guardado correctamente.");
    }

    public function storeDiscard(Request $request, Order $order)
    {
        $this->authorizeOrder($order);
        $data = $request->validate([
            'category' => ['required', 'in:'.DisposableDiscard::DIALYZER.','.DisposableDiscard::BLOOD_LINES],
            'discarded_at' => ['required', 'date'], 'lot_number' => ['required', 'string', 'max:80'],
            'discard_reason' => ['required', 'string', 'max:120'], 'final_condition' => ['nullable', 'string', 'max:120'],
            'observations' => ['nullable', 'string'],
        ]);
        if ($order->disposableDiscards()->where('category', $data['category'])->exists()) {
            throw ValidationException::withMessages(['category' => 'La sesión ya tiene registrado este tipo de descarte.']);
        }
        $order->disposableDiscards()->create($data + ['recorded_by' => $request->user()->id]);
        return back()->with('success', 'Descarte registrado sin duplicar la sesión.');
    }

    public function discardPdf(Request $request, string $category, PdfBrandingService $branding)
    {
        abort_unless(in_array($category, [DisposableDiscard::DIALYZER, DisposableDiscard::BLOOD_LINES], true), 404);
        $date = $request->date('date')?->format('Y-m-d') ?? today()->format('Y-m-d');
        $filters = $this->discardFilters($request);
        $month = $request->date('month', 'Y-m')?->startOfMonth() ?? \Carbon\Carbon::parse($date)->startOfMonth();
        $orders = $this->monthlyOrders($month, $filters);
        [$codes, $rows, $codeCounts] = $this->discardReport($orders, $category);
        return Pdf::loadView('nursing-annexes.discard-pdf', $branding->data() + compact('orders', 'date', 'month', 'category', 'filters', 'codes', 'rows', 'codeCounts'))
            ->setPaper('a4', 'landscape')->stream('control-descarte-'.$date.'.pdf');
    }

    public function discardXlsx(Request $request, string $category, NursingAnnexXlsxExporter $exporter)
    {
        abort_unless(in_array($category, [DisposableDiscard::DIALYZER, DisposableDiscard::BLOOD_LINES], true), 404);
        $date = $request->date('date')?->format('Y-m-d') ?? today()->format('Y-m-d');
        $filters = $this->discardFilters($request);
        $month = $request->date('month', 'Y-m')?->startOfMonth() ?? \Carbon\Carbon::parse($date)->startOfMonth();
        [$codes, $rows, $codeCounts] = $this->discardReport($this->monthlyOrders($month, $filters), $category);
        $annex = $category === DisposableDiscard::DIALYZER ? '11-A' : '11-B';
        $title = $category === DisposableDiscard::DIALYZER
            ? 'ANEXO 11-A - CONTROL DIARIO DE DESCARTE DE DIALIZADORES'
            : 'ANEXO 11-B - CONTROL DIARIO DE DESCARTE DE SET DE LÍNEAS ARTERIALES Y VENOSAS';
        $path = $exporter->discard($rows, $codes, $codeCounts, $month, $title, $filters);

        return response()->download($path, "anexo-{$annex}-{$month->format('Y-m')}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function carePdf(Request $request, PdfBrandingService $branding)
    {
        $date = $request->date('date')?->format('Y-m-d') ?? today()->format('Y-m-d');
        $frequency = $this->frequency($request->input('frequency', $this->frequencyForDate($date)));
        $module = in_array((string) $request->input('module', '1'), Patient::MODULES, true) ? (string) $request->input('module', '1') : '1';
        $annex = DailyNursingAnnex::where(['sede_id' => CurrentSede::id(), 'work_date' => $date, 'frequency' => $frequency, 'module' => $module])->firstOrFail();
        return $this->renderCarePdf($annex, $branding);
    }

    public function generatedCarePdf(DailyNursingAnnex $annex, PdfBrandingService $branding)
    {
        abort_unless((int) $annex->sede_id === (int) CurrentSede::id(), 403);
        return $this->renderCarePdf($annex, $branding);
    }

    public function generatedCareXlsx(DailyNursingAnnex $annex, NursingAnnexXlsxExporter $exporter)
    {
        abort_unless((int) $annex->sede_id === (int) CurrentSede::id(), 403);
        $path = $exporter->care($annex);

        return response()->download($path, 'anexo-12-'.$annex->code.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function renderCarePdf(DailyNursingAnnex $annex, PdfBrandingService $branding)
    {
        return Pdf::loadView('nursing-annexes.care-pdf', $branding->data() + ['annex' => $annex, 'rows' => DailyNursingAnnexService::ROWS])
            ->setPaper('a4')->stream('anexo-12-'.$annex->code.'.pdf');
    }

    private function orders(string $date, array $filters = [])
    {
        return Order::query()->with(['patient', 'medical', 'nurse.enfermeroInicia', 'nurse.enfermeroFinaliza', 'treatments',
            'hemodialysisMaterialConsumptions.material', 'disposableDiscards.recorder'])
            ->where('sede_id', CurrentSede::id())->where('attention_type', ClinicalService::HEMODIALYSIS)
            ->finalizedHemodialysis()
            ->whereDate('fecha_orden', $date)
            ->when($filters['shift'] ?? null, fn ($query, $shift) => $query->where('turno', $shift))
            ->when($filters['module'] ?? null, fn ($query, $module) => $query->whereHas('patient', fn ($patient) => $patient->where('modulo', $module)))
            ->when($filters['sequence'] ?? null, fn ($query, $sequence) => $query->whereHas('patient', fn ($patient) => $patient->where('secuencia', $sequence)))
            ->orderBy('turno')->orderBy('sala')->get();
    }

    private function monthlyOrders(\Carbon\Carbon $month, array $filters): Collection
    {
        return Order::query()->with(['patient', 'nurse', 'hemodialysisMaterialConsumptions.material', 'disposableDiscards.recorder'])
            ->where('sede_id', CurrentSede::id())->where('attention_type', ClinicalService::HEMODIALYSIS)
            ->finalizedHemodialysis()->whereBetween('fecha_orden', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->when($filters['shift'], fn ($query, $shift) => $query->where('turno', $shift))
            ->when($filters['module'], fn ($query, $module) => $query->whereHas('patient', fn ($patient) => $patient->where('modulo', $module)))
            ->when($filters['sequence'], fn ($query, $sequence) => $query->whereHas('patient', fn ($patient) => $patient->where('secuencia', $sequence)))
            ->orderBy('turno')->orderBy('fecha_orden')->get();
    }

    private function discardFilters(Request $request): array
    {
        return [
            'shift' => in_array((string) $request->input('discard_shift'), ['1', '2', '3', '4'], true) ? (string) $request->input('discard_shift') : null,
            'module' => in_array((string) $request->input('discard_module'), Patient::MODULES, true) ? (string) $request->input('discard_module') : null,
            'sequence' => in_array((string) $request->input('discard_sequence'), ['L-M-V', 'M-J-S'], true) ? (string) $request->input('discard_sequence') : null,
        ];
    }

    private function discardRows(Collection $orders): Collection
    {
        return $orders->map(function (Order $order): array {
            return [
                'order' => $order,
                'lines' => $order->hemodialysisMaterialConsumptions->first(
                    fn ($consumption) => str_contains(mb_strtolower($consumption->material?->name ?? ''), 'línea')
                ),
                'dialyzerDiscard' => $order->disposableDiscards->firstWhere('category', DisposableDiscard::DIALYZER),
                'linesDiscard' => $order->disposableDiscards->firstWhere('category', DisposableDiscard::BLOOD_LINES),
            ];
        });
    }

    private function discardReport(Collection $orders, string $category): array
    {
        $dialyzer = $category === DisposableDiscard::DIALYZER;
        $product = function ($order) use ($category, $dialyzer) {
            $discard = $order->disposableDiscards->firstWhere('category', $category);
            if ($discard?->lot_number) {
                return $dialyzer && filled($order->nurse?->filtro)
                    ? trim($order->nurse->filtro).' / '.trim($discard->lot_number)
                    : trim($discard->lot_number);
            }

            return $dialyzer
                ? trim((string) $order->nurse?->filtro)
                : trim((string) optional($order->hemodialysisMaterialConsumptions->first(
                    fn ($consumption) => str_contains(mb_strtolower($consumption->material?->name ?? ''), 'línea')
                ))->material?->name);
        };
        $products = $orders->map($product)->filter()->unique()->values();
        $codes = $products->mapWithKeys(fn ($name, $index) => [$name => $index + 1]);
        $rows = $orders->groupBy('patient_id')->map(function ($patientOrders) use ($product, $codes) {
            $days = $patientOrders->mapWithKeys(fn ($order) => [\Carbon\Carbon::parse($order->fecha_orden)->day => $codes[$product($order)] ?? null]);
            return ['patient' => $patientOrders->first()->patient, 'sequence' => $patientOrders->first()->patient->secuencia, 'days' => $days,
                'totals' => $days->filter()->countBy()];
        })->values();
        $codeCounts = $orders->map($product)->filter()->countBy();

        return [$codes, $rows, $codeCounts];
    }

    private function annexOrders($orders, string $frequency, string $module)
    {
        return $orders->filter(fn ($order) => (string) $order->patient->modulo === $module && $this->frequency($order->nurse?->frecuencia_hd ?: $this->frequencyForDate($order->fecha_orden->format('Y-m-d'))) === $frequency)->values();
    }

    private function frequency(?string $value): string
    {
        $value = strtoupper(str_replace(['-', ' ', '.'], '', (string) $value));
        return str_contains($value, 'MJS') || str_contains($value, 'MARTES') ? 'MJS' : 'LMV';
    }

    private function frequencyForDate(string $date): string
    {
        return in_array(\Carbon\Carbon::parse($date)->dayOfWeekIso, [2, 4, 6], true) ? 'MJS' : 'LMV';
    }

    private function authorizeOrder(Order $order): void
    {
        abort_unless((int) $order->sede_id === (int) CurrentSede::id() && $order->attention_type === ClinicalService::HEMODIALYSIS, 403);
        abort_unless($order->nurse()->whereNotNull('enfermero_que_finaliza_id')->exists(), 422, 'La hemodiálisis debe estar finalizada antes de registrar consumos o descartes.');
    }
}
