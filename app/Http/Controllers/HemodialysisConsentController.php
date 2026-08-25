<?php

namespace App\Http\Controllers;

use App\Models\HemodialysisConsent;
use App\Models\Patient;
use App\Models\User;
use App\Services\PdfBrandingService;
use App\Support\CurrentSede;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HemodialysisConsentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:consents.view')->only(['index', 'show']);
        $this->middleware('permission:consents.create')->only(['create', 'store']);
        $this->middleware('permission:consents.print')->only(['pdf', 'bulkPdf']);
    }

    public function index(Request $request)
    {
        $date = $request->date('date')?->toDateString() ?? today()->toDateString();
        $consents = HemodialysisConsent::query()->with(['patient', 'sede', 'physician'])
            ->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede))
            ->whereDate('consented_at', $date)
            ->when($request->filled('sequence'), fn ($query) => $query->whereHas('patient', fn ($patient) => $patient->where('secuencia', $request->string('sequence'))))
            ->when($request->filled('shift'), fn ($query) => $query->whereHas('patient', fn ($patient) => $patient->where('turno', $request->string('shift'))))
            ->when($request->filled('search'), fn ($query) => $query->whereHas('patient', function ($patient) use ($request) {
                $search = trim((string) $request->search);
                $patient->where('dni', 'like', "%{$search}%")->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%");
            }))->latest('consented_at')->paginate(20)->withQueryString();

        return view('consents.index', compact('consents', 'date'));
    }

    public function create(Request $request)
    {
        return view('consents.create', [
            'patients' => Patient::query()->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede))->orderBy('surname')->get(),
            'physicians' => User::query()->where('profession', 'like', '%MEDIC%')->orderBy('name')->get(),
            'patientId' => $request->integer('patient_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'], 'physician_id' => ['nullable', 'exists:users,id'],
            'consented_at' => ['required', 'date'], 'version' => ['required', 'string', 'max:30'], 'accepted' => ['required', 'boolean'],
            'representative_name' => ['nullable', 'required_with:representative_document', 'string', 'max:255'],
            'representative_document' => ['nullable', 'string', 'max:30'], 'representative_relationship' => ['nullable', 'string', 'max:80'],
            'patient_signature' => ['nullable', 'image', 'max:2048'], 'representative_signature' => ['nullable', 'image', 'max:2048'], 'fingerprint' => ['nullable', 'image', 'max:2048'], 'notes' => ['nullable', 'string'],
        ]);
        $patient = Patient::findOrFail($data['patient_id']);
        abort_if(CurrentSede::id() && (int) $patient->sede_id !== (int) CurrentSede::id(), 403);
        if (! empty($data['physician_id'])) {
            $physician = User::findOrFail($data['physician_id']);
            $profession = mb_strtolower((string) $physician->profession);
            abort_unless($physician->hasRole('medico') || str_contains($profession, 'medic') || str_contains($profession, 'nefro'), 422, 'El responsable debe ser médico.');
        }
        if (HemodialysisConsent::query()->where('patient_id', $patient->id)
            ->where('consented_at', $data['consented_at'])->where('version', $data['version'])->exists()) {
            throw ValidationException::withMessages(['version' => 'Ya existe un consentimiento de esta versión en la fecha indicada.']);
        }
        unset($data['patient_signature'], $data['representative_signature'], $data['fingerprint']);
        $data['sede_id'] = $patient->sede_id;
        $data['created_by'] = $request->user()->id;
        if ($request->hasFile('patient_signature')) $data['patient_signature_path'] = $request->file('patient_signature')->store('consents/signatures', 'public');
        if ($request->hasFile('representative_signature')) $data['representative_signature_path'] = $request->file('representative_signature')->store('consents/representatives', 'public');
        if ($request->hasFile('fingerprint')) $data['fingerprint_path'] = $request->file('fingerprint')->store('consents/fingerprints', 'public');
        $consent = HemodialysisConsent::create($data);

        return redirect()->route('consents.show', $consent)->with('success', 'Consentimiento registrado sin sobrescribir antecedentes.');
    }

    public function show(HemodialysisConsent $consent)
    {
        $this->authorizeSede($consent);
        $consent->load(['patient', 'sede', 'physician', 'creator']);
        return view('consents.show', compact('consent'));
    }

    public function pdf(HemodialysisConsent $consent, PdfBrandingService $branding)
    {
        $this->authorizeSede($consent);
        $consent->load(['patient', 'sede', 'physician']);
        return Pdf::loadView('consents.pdf', $branding->data() + compact('consent'))->setPaper('a4')
            ->stream('consentimiento-hemodialisis-'.$consent->id.'.pdf');
    }

    public function bulkPdf(Request $request, PdfBrandingService $branding)
    {
        $data = $request->validate([
            'consent_ids' => ['required', 'array', 'min:1'],
            'consent_ids.*' => ['integer', 'distinct', 'exists:hemodialysis_consents,id'],
        ]);

        $consents = HemodialysisConsent::query()->with(['patient', 'sede', 'physician'])
            ->whereIn('id', $data['consent_ids'])
            ->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede))
            ->orderBy('consented_at')->get();

        abort_unless($consents->count() === count($data['consent_ids']), 403);

        return Pdf::loadView('consents.bulk-pdf', $branding->data() + compact('consents'))->setPaper('a4')
            ->stream('consentimientos-hemodialisis.pdf');
    }

    private function authorizeSede(HemodialysisConsent $consent): void
    {
        abort_if(CurrentSede::id() && (int) $consent->sede_id !== (int) CurrentSede::id(), 403);
    }
}
