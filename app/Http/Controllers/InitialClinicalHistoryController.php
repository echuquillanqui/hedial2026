<?php

namespace App\Http\Controllers;

use App\Models\InitialClinicalHistory;
use App\Models\NephrologyConsultation;
use App\Models\Patient;
use App\Models\User;
use App\Services\LaboratoryResultSnapshotService;
use App\Services\PdfBrandingService;
use App\Support\CurrentSede;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InitialClinicalHistoryController extends Controller
{
    public const COMORBIDITIES = ['Hipertensión arterial', 'Diabetes mellitus', 'Cardiopatía', 'Enfermedad vascular', 'Enfermedad hepática', 'Tuberculosis', 'Neoplasia'];
    public const IMMUNIZATIONS = ['Hepatitis B', 'Influenza', 'Neumococo', 'COVID-19'];

    public function __construct()
    {
        $this->middleware('permission:initial_history.view')->only(['index', 'show']);
        $this->middleware('permission:initial_history.create')->only(['create', 'store']);
        $this->middleware('permission:initial_history.update')->only(['edit', 'update']);
        $this->middleware('permission:initial_history.print')->only('pdf');
    }

    public function index(Request $request)
    {
        $histories = InitialClinicalHistory::query()->with(['patient', 'nephrologist'])
            ->when(CurrentSede::id(), fn ($query, $sede) => $query->whereHas('patient', fn ($patient) => $patient->where('sede_id', $sede)))
            ->when($request->filled('search'), fn ($query) => $query->whereHas('patient', function ($patient) use ($request) {
                $search = trim((string) $request->search);
                $patient->where('dni', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%");
            }))->latest('recorded_at')->paginate(20)->withQueryString();

        return view('initial-histories.index', compact('histories'));
    }

    public function create(Request $request)
    {
        return $this->form(new InitialClinicalHistory(['patient_id' => $request->integer('patient_id'), 'recorded_at' => today()]));
    }

    public function store(Request $request, LaboratoryResultSnapshotService $laboratory)
    {
        $data = $this->validated($request, true);
        $patient = Patient::findOrFail($data['patient_id']);
        $this->authorizePatientSede($patient);
        $this->validateNephrologist($data['nephrologist_id'] ?? null);

        $history = DB::transaction(function () use ($data, $patient, $laboratory, $request) {
            $data['created_by'] = $request->user()->id;
            $data['updated_by'] = $request->user()->id;
            $history = InitialClinicalHistory::create($data);
            $history->laboratoryResults()->attach(
                $laboratory->latestValidFor($patient, $history->recorded_at)->modelKeys()
            );

            return $history;
        });

        return redirect()->route('initial-histories.show', $history)->with('success', 'Historia clínica inicial registrada.');
    }

    public function show(InitialClinicalHistory $initialHistory)
    {
        $this->authorizePatientSede($initialHistory->patient);
        $this->loadHistory($initialHistory);

        return view('initial-histories.show', ['history' => $initialHistory, 'medications' => $this->medicationsAt($initialHistory)]);
    }

    public function edit(InitialClinicalHistory $initialHistory)
    {
        $this->authorizePatientSede($initialHistory->patient);

        return $this->form($initialHistory);
    }

    public function update(Request $request, InitialClinicalHistory $initialHistory)
    {
        $this->authorizePatientSede($initialHistory->patient);
        $data = $this->validated($request, false);
        $this->validateNephrologist($data['nephrologist_id'] ?? null);
        $data['updated_by'] = $request->user()->id;
        $initialHistory->update($data);

        return redirect()->route('initial-histories.show', $initialHistory)->with('success', 'Historia clínica inicial actualizada.');
    }

    public function pdf(InitialClinicalHistory $initialHistory, PdfBrandingService $branding)
    {
        $this->authorizePatientSede($initialHistory->patient);
        $this->loadHistory($initialHistory);

        return Pdf::loadView('initial-histories.pdf', $branding->data() + [
            'history' => $initialHistory,
            'medications' => $this->medicationsAt($initialHistory),
        ])->setPaper('a4')->stream('historia-clinica-inicial-'.$initialHistory->patient_id.'.pdf');
    }

    private function form(InitialClinicalHistory $history)
    {
        $patients = Patient::query()->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede))
            ->whereDoesntHave('initialClinicalHistory')->when($history->patient_id, fn ($query) => $query->orWhereKey($history->patient_id))
            ->orderBy('surname')->get();
        $nephrologists = User::query()->where(fn ($query) => $query->where('profession', 'like', '%MEDIC%')
            ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'medico')))->orderBy('name')->get();

        return view('initial-histories.form', compact('history', 'patients', 'nephrologists'));
    }

    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'patient_id' => [$creating ? 'required' : 'prohibited', 'nullable', 'exists:patients,id', 'unique:initial_clinical_histories,patient_id'],
            'nephrologist_id' => ['nullable', 'exists:users,id'], 'recorded_at' => [$creating ? 'required' : 'prohibited', 'nullable', 'date'],
            'personal_history' => ['nullable', 'string'], 'family_history' => ['nullable', 'string'], 'ckd_etiology' => ['nullable', 'string'],
            'first_hemodialysis_date' => ['nullable', 'date'], 'comorbidities' => ['nullable', 'array'], 'comorbidities.*' => ['string', 'max:100'],
            'blood_type' => ['nullable', 'string', 'max:10'], 'transfusion_history' => ['nullable', 'string'], 'residual_diuresis' => ['nullable', 'numeric', 'min:0'],
            'allergies' => ['nullable', 'string'], 'previous_renal_therapy' => ['nullable', 'string'], 'immunizations' => ['nullable', 'array'],
            'immunizations.*' => ['string', 'max:100'], 'clinical_exam' => ['nullable', 'string'], 'vascular_access_notes' => ['nullable', 'string'],
        ]);
    }

    private function loadHistory(InitialClinicalHistory $history): void
    {
        $history->load(['patient.sede', 'nephrologist', 'creator', 'updater', 'laboratoryResults.test.area', 'laboratoryResults.order']);
    }

    private function medicationsAt(InitialClinicalHistory $history)
    {
        return NephrologyConsultation::query()->with('medications')->where('patient_id', $history->patient_id)
            ->whereDate('consultation_date', '<=', $history->recorded_at)->latest('consultation_date')->first()?->medications ?? collect();
    }

    private function authorizePatientSede(Patient $patient): void
    {
        abort_if(CurrentSede::id() && (int) $patient->sede_id !== (int) CurrentSede::id(), 403, 'Paciente fuera de la sede activa.');
    }

    private function validateNephrologist(?int $userId): void
    {
        if (! $userId) return;
        $user = User::findOrFail($userId);
        $profession = mb_strtolower((string) $user->profession);
        abort_unless($user->hasRole('medico') || str_contains($profession, 'medic') || str_contains($profession, 'nefro'), 422, 'El responsable debe ser médico o nefrólogo.');
    }
}
