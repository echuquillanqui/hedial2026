<?php

namespace App\Http\Controllers;

use App\Models\FuaConfiguration;
use App\Models\Fua;
use App\Models\MedicationCatalog;
use App\Models\NephrologyConsultation;
use App\Models\Patient;
use App\Models\User;
use App\Support\CurrentSede;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class NephrologyConsultationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:nephrology.view')->only(['index']);
        $this->middleware('permission:nephrology.update')->only(['edit', 'update', 'updateDate', 'updateDates', 'destroyDuplicates']);
        $this->middleware('permission:nephrology.print')->only(['consultationPdf', 'prescriptionPdf', 'bulkPdf']);
    }

    public const AUXILIARY_EXAMS = [
        'Mensual' => ['Hematocrito', 'Hemoglobina', 'Nitrógeno ureico (urea pre y post diálisis)', 'Perfil de electrolitos (cloro, sodio y potasio)', 'Calcio total', 'Fósforo inorgánico (fosfato)'],
        'Bimestral' => ['Aspartato aminotransferasa (AST/TGO)', 'Alanina aminotransferasa (ALT/TGP)'],
        'Trimestral' => ['Albúmina', 'Fosfatasa alcalina', 'Hierro', 'Ferritina', 'Transferrina', 'Parathormona (PTH)'],
        'Semestral' => ['Anticuerpos VIH 1 y VIH 2', 'Sífilis (anticuerpo no treponémico)', 'Antígeno de superficie hepatitis B (HBsAg)', 'Anticuerpo de superficie hepatitis B (anti-HBs)', 'Anticuerpo core total hepatitis B (anti-HBc)', 'Anticuerpo hepatitis C (anti-HCV)', 'Anticuerpo HTLV 1'],
    ];

    public function index(Request $request)
    {
        $patientsWithConsultations = Patient::query()
            ->whereHas('nephrologyConsultations', fn ($query) => $query
                ->whereHas('order', fn ($order) => $order->where('attention_type', Fua::NEPHROLOGY))
                ->when(CurrentSede::id(), fn ($consultations, $sede) => $consultations->where('sede_id', $sede)))
            ->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede));

        $filterOptions = collect(['secuencia', 'turno', 'modulo'])->mapWithKeys(function (string $field) use ($patientsWithConsultations) {
            return [$field => (clone $patientsWithConsultations)->whereNotNull($field)->where($field, '!=', '')
                ->distinct()->pluck($field)->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()];
        });

        $consultationsQuery = NephrologyConsultation::with(['patient', 'doctor', 'order.fua'])
            ->whereHas('order', fn ($order) => $order->where('attention_type', Fua::NEPHROLOGY))
            ->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->whereHas('patient', fn ($patient) => $patient->where(function ($patient) use ($search) {
                    $patient->where('dni', 'like', "%{$search}%")
                        ->orWhere('medical_history_number', 'like', "%{$search}%")
                        ->orWhere('affiliation_code', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('other_names', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                }));
            })
            ->when($request->filled('date'), fn ($query) => $query->whereDate('consultation_date', $request->date))
            ->when($request->filled('sequence'), fn ($query) => $query->whereHas('patient', fn ($patient) => $patient->where('secuencia', $request->sequence)))
            ->when($request->filled('shift'), fn ($query) => $query->whereHas('patient', fn ($patient) => $patient->where('turno', $request->shift)))
            ->when($request->filled('module'), fn ($query) => $query->whereHas('patient', fn ($patient) => $patient->where('modulo', $request->module)));

        $duplicateIds = (clone $consultationsQuery)
            ->get(['nephrology_consultations.id', 'nephrology_consultations.patient_id', 'nephrology_consultations.consultation_date'])
            ->groupBy(fn (NephrologyConsultation $item) => $item->patient_id.'|'.$item->consultation_date?->format('Y-m-d'))
            ->flatMap(fn ($group) => $group->sortBy('id')->skip(1)->pluck('id'))
            ->map(fn ($id) => (string) $id)
            ->values();

        $consultations = $consultationsQuery
            ->orderBy(Patient::select('surname')->whereColumn('patients.id', 'nephrology_consultations.patient_id'))
            ->orderBy(Patient::select('last_name')->whereColumn('patients.id', 'nephrology_consultations.patient_id'))
            ->orderBy(Patient::select('first_name')->whereColumn('patients.id', 'nephrology_consultations.patient_id'))
            ->orderBy(Patient::select('other_names')->whereColumn('patients.id', 'nephrology_consultations.patient_id'))
            ->orderBy('nephrology_consultations.id')
            ->paginate(30)->withQueryString();

        return view('consultations.index', compact('consultations', 'filterOptions', 'duplicateIds'));
    }

    public function create()
    {
        $currentDoctorId = Auth::user()?->isMedicalProfessional() ? Auth::id() : null;

        return view('consultations.form', [
            'consultation' => new NephrologyConsultation(['consultation_date' => now()]),
            'patients' => Patient::when(CurrentSede::id(), fn ($q, $sede) => $q->where('sede_id', $sede))->orderBy('surname')->get(),
            'doctors' => User::medicalProfessionals()->orderBy('name')->get(),
            'medications' => $this->defaultMedications(),
            'examGroups' => self::AUXILIARY_EXAMS,
            'currentDoctorId' => $currentDoctorId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['diagnosis'] = $this->diagnosisSummary($data);
        DB::transaction(function () use ($data) {
            $medications = $data['medications']; unset($data['medications']);
            $data['sede_id'] = CurrentSede::id();
            $data['doctor_id'] = $data['doctor_id']
                ?? (Auth::user()?->isMedicalProfessional() ? Auth::id() : null);
            $consultation = NephrologyConsultation::create($data);
            $consultation->medications()->createMany($medications);
        });
        return redirect()->route('consultations.index')->with('success', 'Consulta nefrológica registrada correctamente.');
    }

    public function edit(NephrologyConsultation $consultation)
    {
        $this->authorizeSede($consultation);
        abort_unless($consultation->order?->attention_type === Fua::NEPHROLOGY, 404);
        $medications = $consultation->medications;

        if ($medications->isEmpty()) {
            $medications = $this->defaultMedications();
        }

        $currentDoctorId = Auth::user()?->isMedicalProfessional() ? Auth::id() : null;

        return view('consultations.form', [
            'consultation' => $consultation,
            'patients' => Patient::when(CurrentSede::id(), fn ($q, $sede) => $q->where('sede_id', $sede))->orderBy('surname')->get(),
            'doctors' => User::medicalProfessionals()->orderBy('name')->get(),
            'medications' => $medications,
            'examGroups' => self::AUXILIARY_EXAMS,
            'currentDoctorId' => $currentDoctorId,
        ]);
    }

    public function update(Request $request, NephrologyConsultation $consultation)
    {
        $this->authorizeSede($consultation);
        abort_unless($consultation->order?->attention_type === Fua::NEPHROLOGY, 404);
        $data = $this->validated($request);
        // The patient belongs to the generated consultation and cannot be
        // reassigned from the fill-in form, even if the request is tampered with.
        $data['patient_id'] = $consultation->patient_id;
        $data['diagnosis'] = $this->diagnosisSummary($data);
        DB::transaction(function () use ($data, $consultation) {
            $medications = $data['medications']; unset($data['medications']);
            $consultation->update($data); $consultation->medications()->delete();
            $consultation->medications()->createMany($medications);
            $consultation->order?->update(['fecha_orden' => $consultation->consultation_date]);
        });
        return redirect()->route('consultations.index')->with('success', 'Consulta nefrológica actualizada.');
    }

    public function updateDate(Request $request, NephrologyConsultation $consultation)
    {
        $this->authorizeSede($consultation);
        abort_unless($consultation->order?->attention_type === Fua::NEPHROLOGY, 404);
        $data = $request->validate(['consultation_date' => ['required', 'date']]);

        DB::transaction(function () use ($consultation, $data) {
            $consultation->update($data);
            // La fecha que imprime la FUA proviene de la orden asociada.
            $consultation->order->update(['fecha_orden' => $data['consultation_date']]);
        });

        return back()->with('success', 'Fecha de consulta y FUA actualizada.');
    }

    public function updateDates(Request $request)
    {
        $data = $request->validate([
            'consultations' => ['required', 'array', 'min:1'],
            'consultations.*' => ['integer', 'distinct', 'exists:nephrology_consultations,id'],
            'consultation_date' => ['required', 'date'],
        ]);

        $consultations = NephrologyConsultation::query()
            ->whereIn('id', $data['consultations'])
            ->whereHas('order', fn ($order) => $order->where('attention_type', Fua::NEPHROLOGY))
            ->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede))
            ->with('order')
            ->get();

        abort_unless($consultations->count() === count($data['consultations']), 403);

        DB::transaction(function () use ($consultations, $data) {
            foreach ($consultations as $consultation) {
                $consultation->update(['consultation_date' => $data['consultation_date']]);
                $consultation->order->update(['fecha_orden' => $data['consultation_date']]);
            }
        });

        return back()->with('success', $consultations->count().' consultas y sus FUA fueron actualizadas.');
    }

    public function destroyDuplicates(Request $request)
    {
        $data = $request->validate([
            'consultations' => ['required', 'array', 'min:1'],
            'consultations.*' => ['integer', 'distinct', 'exists:nephrology_consultations,id'],
        ]);

        [$deleted, $protected] = DB::transaction(function () use ($data) {
            $consultations = NephrologyConsultation::query()
                ->whereIn('id', $data['consultations'])
                ->whereHas('order', fn ($order) => $order->where('attention_type', Fua::NEPHROLOGY))
                ->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede))
                ->with('order')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            abort_unless($consultations->count() === count($data['consultations']), 403);
            $deleted = 0;
            $protected = 0;

            foreach ($consultations as $consultation) {
                $duplicates = NephrologyConsultation::query()
                    ->where('patient_id', $consultation->patient_id)
                    ->whereDate('consultation_date', $consultation->consultation_date)
                    ->whereHas('order', fn ($order) => $order->where('attention_type', Fua::NEPHROLOGY))
                    ->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede))
                    ->count();

                if ($duplicates < 2) {
                    $protected++;
                    continue;
                }

                // Deleting the order also removes its consultation, medications and FUA.
                $consultation->order->delete();
                $deleted++;
            }

            return [$deleted, $protected];
        });

        $message = $deleted.' consulta(s) duplicada(s) eliminada(s).';
        if ($protected > 0) {
            $message .= ' Se conservaron '.$protected.' registro(s) porque eran la única consulta restante.';
        }

        return back()->with($deleted > 0 ? 'success' : 'warning', $message);
    }

    public function prescriptionPdf(NephrologyConsultation $consultation)
    {
        $this->authorizeSede($consultation);
        $consultation->load(['patient', 'doctor', 'sede', 'medications']);

        $configuration = FuaConfiguration::global();
        $logoData = $this->logoData($configuration->logo_path);

        return Pdf::loadView('consultations.prescription_pdf', compact('consultation', 'configuration', 'logoData'))->setPaper('a4')
            ->stream('receta-nefrologia-'.$consultation->id.'.pdf');
    }

    public function consultationPdf(NephrologyConsultation $consultation)
    {
        $this->authorizeSede($consultation);
        $consultation->load(['patient', 'doctor', 'sede', 'medications']);

        $configuration = FuaConfiguration::global();
        $logoData = $this->logoData($configuration->logo_path);

        return Pdf::loadView('consultations.consultation_pdf', compact('consultation', 'configuration', 'logoData'))->setPaper('a4')
            ->stream('consulta-nefrologica-'.$consultation->id.'.pdf');
    }

    public function bulkPdf(Request $request)
    {
        $data = $request->validate([
            'consultations' => ['required', 'array', 'min:1'],
            'consultations.*' => ['integer', 'distinct', 'exists:nephrology_consultations,id'],
            'document_type' => ['required', 'in:consultation,prescription'],
        ]);

        $consultations = NephrologyConsultation::query()
            ->whereIn('id', $data['consultations'])
            ->whereHas('order', fn ($order) => $order->where('attention_type', Fua::NEPHROLOGY))
            ->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede))
            ->with(['patient', 'doctor', 'sede', 'medications'])->get()
            ->sortBy(fn ($item) => array_search($item->id, $data['consultations']))->values();

        abort_unless($consultations->count() === count($data['consultations']), 403);
        $configuration = FuaConfiguration::global();
        $logoData = $this->logoData($configuration->logo_path);
        $view = $data['document_type'] === 'consultation' ? 'consultations.consultation_pdf' : 'consultations.prescription_pdf';

        return Pdf::loadView($view, compact('consultations', 'configuration', 'logoData'))->setPaper('a4')
            ->stream($data['document_type'].'-nefrologia-bloque.pdf');
    }

    /** Use the logo uploaded in FUA settings and keep an embedded fallback for reliable PDFs. */
    private function logoData(?string $path): ?string
    {
        $candidates = array_filter([
            $path ? storage_path('app/public/'.$path) : null,
            public_path('logo/logo_03.jpeg'),
        ]);

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $mime = mime_content_type($candidate) ?: 'image/jpeg';

                return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($candidate));
            }
        }

        return null;
    }

    private function validated(Request $request): array
    {
        // MySQL returns TIME values with seconds (HH:MM:SS), while the browser's
        // time control normally submits HH:MM. Normalize both representations so
        // editing an existing consultation cannot fail without user intervention.
        if ($request->filled('consultation_time')) {
            $request->merge([
                'consultation_time' => substr((string) $request->input('consultation_time'), 0, 5),
            ]);
        }

        return $request->validate([
            'patient_id' => ['required', 'exists:patients,id'], 'doctor_id' => ['nullable', 'exists:users,id'],
            'consultation_date' => ['required', 'date'], 'blood_pressure' => ['nullable', 'string', 'max:20'],
            'consultation_time' => ['nullable', 'date_format:H:i'], 'dialysis_start_date' => ['nullable', 'date'],
            'disease_duration' => ['nullable', 'string', 'max:80'], 'etiology' => ['nullable', 'string', 'max:255'],
            'vascular_access' => ['nullable', 'string', 'max:255'], 'symptoms' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric', 'min:0'], 'temperature' => ['nullable', 'numeric', 'between:25,45'],
            'heart_rate' => ['nullable', 'integer', 'between:1,300'], 'oxygen_saturation' => ['nullable', 'integer', 'between:1,100'],
            'height' => ['nullable', 'numeric', 'between:0.3,2.5'], 'respiratory_rate' => ['nullable', 'integer', 'between:1,100'],
            'bmi' => ['nullable', 'numeric', 'between:1,100'], 'diuresis' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string'], 'current_illness' => ['nullable', 'string'], 'history' => ['nullable', 'string'],
            'physical_exam' => ['nullable', 'string'], 'diagnosis' => ['nullable', 'string'], 'treatment_plan' => ['nullable', 'string'], 'observations' => ['nullable', 'string'],
            'lung_exam' => ['nullable', 'string'], 'cardiac_exam' => ['nullable', 'string'],
            'dialysis_prescription' => ['nullable', 'string', 'max:255'], 'dialysis_hours' => ['nullable', 'numeric', 'between:0,24'],
            'filter_area' => ['nullable', 'numeric', 'between:0,10'], 'anemia_treatment' => ['nullable', 'boolean'],
            'hemoglobin' => ['nullable', 'numeric', 'between:0,30'], 'epoetin_dose' => ['nullable', 'string', 'max:255'],
            'hydroxocobalamin_dose' => ['nullable', 'string', 'max:255'], 'iron_dose' => ['nullable', 'string', 'max:255'],
            'bone_mineral_treatment' => ['nullable', 'boolean'], 'antihypertensive_treatment' => ['nullable', 'boolean'],
            'other_treatment' => ['nullable', 'string'],
            'diagnoses' => ['nullable', 'array', 'max:10'], 'diagnoses.*.cie10_id' => ['nullable', 'exists:cie10s,id'],
            'diagnoses.*.codigo' => ['required_with:diagnoses.*.descripcion', 'nullable', 'string', 'max:20'],
            'diagnoses.*.descripcion' => ['required_with:diagnoses.*.codigo', 'nullable', 'string', 'max:255'],
            'auxiliary_exams' => ['nullable', 'array'], 'auxiliary_exams.*' => ['string', 'max:100'],
            'next_laboratory_date' => ['nullable', 'date'], 'next_appointment_date' => ['nullable', 'date'],
            'medications' => ['required', 'array', 'min:1'], 'medications.*.fua_code' => ['nullable', 'string', 'max:30'],
            'medications.*.description' => ['required', 'string', 'max:255'], 'medications.*.c' => ['nullable', 'string', 'max:50'],
            'medications.*.prescribed_quantity' => ['nullable', 'numeric', 'min:0'], 'medications.*.delivered_quantity' => ['nullable', 'numeric', 'min:0'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'required_with' => 'El campo :attribute es obligatorio.',
            'date_format' => 'El campo :attribute no tiene un formato válido.',
        ], [
            'patient_id' => 'paciente',
            'consultation_date' => 'fecha',
            'consultation_time' => 'hora',
            'medications' => 'medicamentos',
            'medications.*.description' => 'medicamento',
            'diagnoses.*.codigo' => 'código CIE-10',
            'diagnoses.*.descripcion' => 'descripción del diagnóstico',
        ]);
    }

    /** Build the editable default prescription from the current medication catalog. */
    private function defaultMedications(): Collection
    {
        return MedicationCatalog::query()->orderBy('name')->get()->map(fn (MedicationCatalog $medication) => [
            'fua_code' => $medication->code,
            'description' => $medication->name,
            'c' => $medication->indication,
            'prescribed_quantity' => $medication->reference_quantity,
            'delivered_quantity' => $medication->reference_quantity,
        ]);
    }

    private function authorizeSede(NephrologyConsultation $consultation): void
    {
        abort_if(CurrentSede::id() && (int) $consultation->sede_id !== (int) CurrentSede::id(), 403, 'Consulta fuera de la sede activa.');
    }

    private function diagnosisSummary(array $data): ?string
    {
        if (! empty($data['diagnoses'])) {
            return collect($data['diagnoses'])
                ->filter(fn ($item) => ! empty($item['codigo']) && ! empty($item['descripcion']))
                ->map(fn ($item) => "{$item['descripcion']} ({$item['codigo']})")
                ->implode('; ');
        }

        return $data['diagnosis'] ?? null;
    }
}
