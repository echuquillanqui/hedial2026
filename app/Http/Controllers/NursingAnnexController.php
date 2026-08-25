<?php

namespace App\Http\Controllers;

use App\Models\DisposableDiscard;
use App\Models\Order;
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
        $this->middleware('permission:annexes.nursing.record')->only('storeDiscard');
        $this->middleware('permission:annexes.nursing.print')->only(['discardPdf', 'carePdf']);
    }

    public function index(Request $request)
    {
        $date = $request->date('date')?->format('Y-m-d') ?? today()->format('Y-m-d');
        $orders = $this->orders($date);
        return view('nursing-annexes.index', compact('orders', 'date'));
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
        $orders = $this->orders($date);
        return Pdf::loadView('nursing-annexes.care-pdf', $branding->data() + compact('orders', 'date'))
            ->setPaper('a4', 'landscape')->stream('registro-enfermeria-'.$date.'.pdf');
    }

    private function orders(string $date)
    {
        return Order::query()->with(['patient', 'medical', 'nurse.enfermeroInicia', 'nurse.enfermeroFinaliza', 'treatments',
            'hemodialysisMaterialConsumptions.material', 'disposableDiscards.recorder'])
            ->where('sede_id', CurrentSede::id())->where('attention_type', ClinicalService::HEMODIALYSIS)
            ->whereDate('fecha_orden', $date)->orderBy('turno')->orderBy('sala')->get();
    }

    private function authorizeOrder(Order $order): void
    {
        abort_unless((int) $order->sede_id === (int) CurrentSede::id() && $order->attention_type === ClinicalService::HEMODIALYSIS, 403);
    }
}
