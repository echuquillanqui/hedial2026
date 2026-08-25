<?php

namespace App\Http\Controllers;

use App\Models\Medical;
use App\Models\NephrologyConsultation;
use App\Models\NutritionAssessment;
use App\Models\Order;
use App\Services\LaboratoryResultSnapshotService;
use App\Services\MultisectorialOrderService;
use App\Services\PdfBrandingService;
use App\Support\ClinicalService;
use App\Support\CurrentSede;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NutritionAssessmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:nutrition.view')->only(['index', 'show']);
        $this->middleware('permission:nutrition.create')->only(['create', 'store']);
        $this->middleware('permission:nutrition.update')->only(['edit', 'update']);
        $this->middleware('permission:nutrition.print')->only('pdf');
    }

    public function index()
    {
        $assessments = NutritionAssessment::with(['order.patient', 'order.assignedProfessional', 'misAssessment'])
            ->whereHas('order', fn ($q) => $q->where('sede_id', CurrentSede::id())->where('attention_type', ClinicalService::NUTRITION))
            ->latest('assessment_date')->paginate(20);
        $pendingOrders = Order::with('patient')->where('sede_id', CurrentSede::id())->where('attention_type', ClinicalService::NUTRITION)
            ->whereDoesntHave('nutritionAssessment')->orderBy('due_date')->get();
        return view('nutrition.index', compact('assessments', 'pendingOrders'));
    }

    public function create(Order $order)
    {
        $this->authorizeOrder($order);
        abort_if($order->nutritionAssessment()->exists(), 409, 'La orden ya tiene atención nutricional.');
        return view('nutrition.form', ['assessment' => new NutritionAssessment(['assessment_date' => today()]), 'order' => $order->load('patient')]);
    }

    public function store(Request $request, Order $order, LaboratoryResultSnapshotService $laboratory)
    {
        $this->authorizeOrder($order);
        $data = $this->validated($request);
        $assessment = DB::transaction(function () use ($data, $order, $laboratory, $request) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            abort_if($order->nutritionAssessment()->exists(), 409, 'La orden ya tiene atención nutricional.');
            $date = $data['assessment_date'];
            $medical = Medical::whereHas('order', fn ($q) => $q->where('patient_id', $order->patient_id)->whereDate('fecha_orden', '<=', $date))
                ->latest('id')->first();
            $consultation = NephrologyConsultation::where('patient_id', $order->patient_id)->whereDate('consultation_date', '<=', $date)
                ->latest('consultation_date')->first();
            $assessment = NutritionAssessment::create($data + ['order_id' => $order->id, 'medical_id' => $medical?->id,
                'nephrology_consultation_id' => $consultation?->id, 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $assessment->laboratoryResults()->attach($laboratory->latestValidFor($order->patient, $assessment->assessment_date)->modelKeys());
            $order->update(['status' => MultisectorialOrderService::COMPLETED, 'completed_at' => now()]);
            return $assessment;
        });
        return redirect()->route('nutrition.show', $assessment)->with('success', 'Atención nutricional registrada.');
    }

    public function show(NutritionAssessment $nutrition) { $this->authorizeAssessment($nutrition); $this->load($nutrition); return view('nutrition.show', ['assessment' => $nutrition]); }
    public function edit(NutritionAssessment $nutrition) { $this->authorizeAssessment($nutrition); return view('nutrition.form', ['assessment' => $nutrition, 'order' => $nutrition->order->load('patient')]); }
    public function update(Request $request, NutritionAssessment $nutrition) { $this->authorizeAssessment($nutrition); $data = $this->validated($request); unset($data['assessment_date']); $nutrition->update($data + ['updated_by' => $request->user()->id]); return redirect()->route('nutrition.show', $nutrition)->with('success', 'Atención actualizada sin alterar su evidencia clínica.'); }
    public function pdf(NutritionAssessment $nutrition, PdfBrandingService $branding) { $this->authorizeAssessment($nutrition); $this->load($nutrition); return Pdf::loadView('nutrition.pdf', $branding->data() + ['assessment' => $nutrition])->setPaper('a4')->stream("anexo-6-{$nutrition->id}.pdf"); }

    private function validated(Request $request): array
    {
        return $request->validate(['assessment_date' => ['required','date'], 'clinical_history'=>['nullable','string'], 'nutritional_history'=>['nullable','string'], 'general_recommendations'=>['nullable','string'], 'dietary_recommendations'=>['nullable','string'], 'reason' => ['nullable','string'], 'appetite' => ['nullable','string'],
            'dietary_intake' => ['nullable','string'], 'gastrointestinal_symptoms' => ['nullable','string'], 'functional_capacity' => ['nullable','string'],
            'physical_findings' => ['nullable','string'], 'nutritional_diagnosis' => ['required','string'], 'intervention_plan' => ['nullable','string'],
            'recommendations' => ['nullable','string'], 'observations' => ['nullable','string']]);
    }

    private function authorizeOrder(Order $order): void
    {
        abort_unless($order->attention_type === ClinicalService::NUTRITION && (int) $order->sede_id === (int) CurrentSede::id(), 403);
        abort_unless(auth()->user()->hasRole('admin') || auth()->user()->can('orders.edit') || (int) $order->assigned_professional_id === (int) auth()->id(), 403);
    }
    private function authorizeAssessment(NutritionAssessment $assessment): void { $this->authorizeOrder($assessment->order); }
    private function load(NutritionAssessment $assessment): void { $assessment->load(['order.patient.sede','order.assignedProfessional','medical','nephrologyConsultation','laboratoryResults.test','laboratoryResults.order','misAssessment']); }
}
