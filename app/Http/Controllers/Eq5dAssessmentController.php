<?php
namespace App\Http\Controllers;
use App\Models\Eq5dAssessment; use App\Models\PsychologyAssessment; use App\Services\PdfBrandingService; use App\Support\CurrentSede; use Barryvdh\DomPDF\Facade\Pdf; use Illuminate\Http\Request;
class Eq5dAssessmentController extends Controller
{
 public function __construct(){$this->middleware('permission:psychology.eq5d.view')->only('show');$this->middleware('permission:psychology.eq5d.create')->only(['create','store']);$this->middleware('permission:psychology.print')->only('pdf');}
 public function create(PsychologyAssessment $psychology){$this->authorizeItem($psychology);abort_if($psychology->eq5dAssessment()->exists(),409);return view('eq5d.form',compact('psychology'));}
 public function store(Request $r,PsychologyAssessment $psychology){$this->authorizeItem($psychology);abort_if($psychology->eq5dAssessment()->exists(),409);$data=$r->validate(['assessed_at'=>'required|date','mobility'=>'required|integer|between:1,3','self_care'=>'required|integer|between:1,3','usual_activities'=>'required|integer|between:1,3','pain_discomfort'=>'required|integer|between:1,3','anxiety_depression'=>'required|integer|between:1,3','health_scale'=>'required|integer|between:0,100']);$eq=Eq5dAssessment::create($data+['psychology_assessment_id'=>$psychology->id,'created_by'=>$r->user()->id]);return redirect()->route('eq5d.show',$eq);}
 public function show(Eq5dAssessment $eq5d){$this->authorizeItem($eq5d->psychologyAssessment);$eq5d->load('psychologyAssessment.order.patient');return view('eq5d.show',compact('eq5d'));}
 public function pdf(Eq5dAssessment $eq5d,PdfBrandingService $branding){$this->authorizeItem($eq5d->psychologyAssessment);$eq5d->load('psychologyAssessment.order.patient');return Pdf::loadView('eq5d.pdf',$branding->data()+compact('eq5d'))->setPaper('a4')->stream("anexo-9-{$eq5d->id}.pdf");}
 private function authorizeItem(PsychologyAssessment $p){$o=$p->order;abort_unless((int)$o->sede_id===(int)CurrentSede::id()&&(auth()->user()->can('orders.edit')||(int)$o->assigned_professional_id===(int)auth()->id()),403);}
}
