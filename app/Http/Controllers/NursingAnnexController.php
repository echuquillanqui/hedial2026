<?php

namespace App\Http\Controllers;

use App\Models\DisposableDiscard;
use App\Models\DailyNursingAnnex;
use App\Models\Order;
use App\Services\DailyNursingAnnexService;
use App\Services\PdfBrandingService;
use App\Support\ClinicalService;
use App\Support\CurrentSede;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NursingAnnexController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:annexes.nursing.view')->only('index');
        $this->middleware('permission:annexes.nursing.record')->only(['storeDiscard', 'storeCare']);
        $this->middleware('permission:annexes.nursing.print')->only(['discardPdf', 'carePdf', 'generatedCarePdf']);
    }

    public function index(Request $request, DailyNursingAnnexService $service)
    {
        $date = $request->date('date')?->format('Y-m-d') ?? today()->format('Y-m-d');
        $frequency = $this->frequency($request->input('frequency', $this->frequencyForDate($date)));
        $module = in_array((string) $request->input('module', '1'), ['1', '2', '3', '4'], true) ? (string) $request->input('module', '1') : '1';
        $orders = $this->orders($date);
        $annexOrders = $this->annexOrders($orders, $frequency, $module);
        $automaticValues = $service->calculate($annexOrders);
        $annex = DailyNursingAnnex::where(['sede_id' => CurrentSede::id(), 'work_date' => $date, 'frequency' => $frequency, 'module' => $module])->first();
        $values = $annex?->values ?? $automaticValues;
        $history = DailyNursingAnnex::with('generator')->where('sede_id', CurrentSede::id())
            ->when($request->filled('history_date'), fn ($q) => $q->whereDate('work_date', $request->input('history_date')))
            ->when($request->filled('history_frequency'), fn ($q) => $q->where('frequency', $this->frequency($request->input('history_frequency'))))
            ->latest('work_date')->latest('id')->paginate(12)->withQueryString();
        return view('nursing-annexes.index', compact('orders', 'annexOrders', 'date', 'frequency', 'module', 'automaticValues', 'values', 'annex', 'history'));
    }

    public function storeCare(Request $request, DailyNursingAnnexService $service)
    {
        $data = $request->validate([
            'date' => ['required', 'date'], 'frequency' => ['required', 'in:LMV,MJS'], 'module' => ['required', 'in:1,2,3,4'],
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
            'discarded_at' => ['required', 'date'], 'lot_number' => ['nullable', 'string', 'max:80'],
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
        $orders = $this->orders($date);
        return Pdf::loadView('nursing-annexes.discard-pdf', $branding->data() + compact('orders', 'date', 'category'))
            ->setPaper('a4', 'landscape')->stream('control-descarte-'.$date.'.pdf');
    }

    public function carePdf(Request $request, PdfBrandingService $branding)
    {
        $date = $request->date('date')?->format('Y-m-d') ?? today()->format('Y-m-d');
        $frequency = $this->frequency($request->input('frequency', $this->frequencyForDate($date)));
        $module = in_array((string) $request->input('module', '1'), ['1', '2', '3', '4'], true) ? (string) $request->input('module', '1') : '1';
        $annex = DailyNursingAnnex::where(['sede_id' => CurrentSede::id(), 'work_date' => $date, 'frequency' => $frequency, 'module' => $module])->firstOrFail();
        return $this->renderCarePdf($annex, $branding);
    }

    public function generatedCarePdf(DailyNursingAnnex $annex, PdfBrandingService $branding)
    {
        abort_unless((int) $annex->sede_id === (int) CurrentSede::id(), 403);
        return $this->renderCarePdf($annex, $branding);
    }

    private function renderCarePdf(DailyNursingAnnex $annex, PdfBrandingService $branding)
    {
        return Pdf::loadView('nursing-annexes.care-pdf', $branding->data() + ['annex' => $annex, 'rows' => DailyNursingAnnexService::ROWS])
            ->setPaper('a4')->stream('anexo-12-'.$annex->code.'.pdf');
    }

    private function orders(string $date)
    {
        return Order::query()->with(['patient', 'medical', 'nurse.enfermeroInicia', 'nurse.enfermeroFinaliza', 'treatments',
            'hemodialysisMaterialConsumptions.material', 'disposableDiscards.recorder'])
            ->where('sede_id', CurrentSede::id())->where('attention_type', ClinicalService::HEMODIALYSIS)
            ->whereDate('fecha_orden', $date)->orderBy('turno')->orderBy('sala')->get();
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
    }
}
