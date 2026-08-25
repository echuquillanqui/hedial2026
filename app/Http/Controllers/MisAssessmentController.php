<?php

namespace App\Http\Controllers;

use App\Models\MisAssessment;
use App\Models\NutritionAssessment;
use App\Services\MisScoreService;
use App\Services\PdfBrandingService;
use App\Support\CurrentSede;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MisAssessmentController extends Controller
{
    public function __construct() { $this->middleware('permission:nutrition.mis.view')->only(['index','show']); $this->middleware('permission:nutrition.mis.create')->only(['create','store']); $this->middleware('permission:nutrition.print')->only('pdf'); }
    public function index() { $items = MisAssessment::with('nutritionAssessment.order.patient')->whereHas('nutritionAssessment.order', fn ($q) => $q->where('sede_id', CurrentSede::id()))->latest('assessed_at')->paginate(20); return view('mis.index', compact('items')); }
    public function create(NutritionAssessment $nutrition) { $this->authorizeNutrition($nutrition); abort_if($nutrition->misAssessment()->exists(), 409); return view('mis.form', compact('nutrition')); }
    public function store(Request $request, NutritionAssessment $nutrition, MisScoreService $scores)
    {
        $this->authorizeNutrition($nutrition); abort_if($nutrition->misAssessment()->exists(), 409);
        $data = $request->validate(['assessed_at'=>['required','date'], 'weight_change_score'=>'required|integer|between:0,3', 'dietary_intake_score'=>'required|integer|between:0,3',
            'gastrointestinal_score'=>'required|integer|between:0,3', 'functional_capacity_score'=>'required|integer|between:0,3', 'comorbidity_score'=>'required|integer|between:0,3',
            'fat_stores_score'=>'required|integer|between:0,3', 'muscle_wasting_score'=>'required|integer|between:0,3', 'notes'=>'nullable|string']);
        $nutrition->load('laboratoryResults.test','nephrologyConsultation');
        $normalize = fn ($value) => str()->of($value)->ascii()->lower();
        $albumin = $nutrition->laboratoryResults->first(fn ($item) => $normalize($item->test->name)->contains('albumina'));
        $transferrin = $nutrition->laboratoryResults->first(function ($item) use ($normalize) {
            $name = $normalize($item->test->name);
            return $name->contains('transferrina') || $name->contains('ctfh') || $name->contains('capacidad total de fijacion');
        });
        $bmi = $nutrition->nephrologyConsultation?->bmi;
        $automatic = ['bmi_score'=>$scores->bmi($bmi), 'albumin_score'=>$scores->albumin($albumin?->result_value), 'transferrin_score'=>$scores->transferrin($transferrin?->result_value)];
        $all = array_merge(array_intersect_key($data, array_flip(['weight_change_score','dietary_intake_score','gastrointestinal_score','functional_capacity_score','comorbidity_score','fat_stores_score','muscle_wasting_score'])), $automatic);
        $mis = MisAssessment::create($data + $automatic + ['nutrition_assessment_id'=>$nutrition->id, 'albumin_result_id'=>$albumin?->id,
            'transferrin_result_id'=>$transferrin?->id, 'total_score'=>$scores->total(array_values($all)), 'created_by'=>$request->user()->id]);
        return redirect()->route('mis.show', $mis)->with('success', 'MIS calculado y vinculado a resultados históricos.');
    }
    public function show(MisAssessment $mis) { $this->authorizeNutrition($mis->nutritionAssessment); $this->load($mis); return view('mis.show', compact('mis')); }
    public function pdf(MisAssessment $mis, PdfBrandingService $branding) { $this->authorizeNutrition($mis->nutritionAssessment); $this->load($mis); return Pdf::loadView('mis.pdf', $branding->data()+compact('mis'))->setPaper('a4')->stream("anexo-7-{$mis->id}.pdf"); }
    private function authorizeNutrition(NutritionAssessment $nutrition): void { $order=$nutrition->order; abort_unless((int)$order->sede_id===(int)CurrentSede::id() && (auth()->user()->hasRole('admin') || auth()->user()->can('orders.edit') || (int)$order->assigned_professional_id===(int)auth()->id()),403); }
    private function load(MisAssessment $mis): void { $mis->load(['nutritionAssessment.order.patient.sede','nutritionAssessment.order.assignedProfessional','albuminResult.test','transferrinResult.test','creator']); }
}
