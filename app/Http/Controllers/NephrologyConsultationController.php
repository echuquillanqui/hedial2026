<?php

namespace App\Http\Controllers;

use App\Models\NephrologyConsultation;
use App\Models\Patient;
use App\Models\User;
use App\Support\CurrentSede;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NephrologyConsultationController extends Controller
{
    public const DEFAULT_MEDICATIONS = [
        ['fua_code' => '06127', 'description' => 'Tiamina 100 mg tableta', 'c' => '1 tableta VO cada 24 horas', 'prescribed_quantity' => 30, 'delivered_quantity' => 30],
        ['fua_code' => '05491', 'description' => 'Piridoxina 50 mg tableta', 'c' => '1 tableta VO cada 24 horas', 'prescribed_quantity' => 30, 'delivered_quantity' => 30],
        ['fua_code' => '00200', 'description' => 'Ácido fólico 500 mcg (0,5 mg) tableta', 'c' => '1 tableta VO cada 24 horas', 'prescribed_quantity' => 30, 'delivered_quantity' => 30],
        ['fua_code' => '3107', 'description' => 'Epoetina alfa 2 000 UI/ml, inyectable', 'c' => 'Según esquema de hemodiálisis', 'prescribed_quantity' => 13, 'delivered_quantity' => 13],
        ['fua_code' => '3979', 'description' => 'Vitamina B12, inyectable', 'c' => 'Según indicación médica', 'prescribed_quantity' => 13, 'delivered_quantity' => 13],
        ['fua_code' => '19238', 'description' => 'Hierro sacarato, inyectable', 'c' => 'Según indicación médica', 'prescribed_quantity' => 4, 'delivered_quantity' => 4],
    ];

    public const AUXILIARY_EXAMS = [
        'Mensual' => ['Hemograma completo', 'Urea prediálisis', 'Urea postdiálisis', 'Creatinina', 'Potasio', 'Calcio', 'Fósforo', 'Albúmina'],
        'Bimestral' => ['Transaminasas TGO/TGP', 'Fosfatasa alcalina', 'Bilirrubinas', 'Proteínas totales', 'Glucosa', 'Perfil lipídico'],
        'Trimestral' => ['Ferritina', 'Hierro sérico', 'Saturación de transferrina', 'PTH intacta', 'Kt/V', 'PCR'],
        'Semestral' => ['HBsAg', 'Anti-HBs', 'Anti-HCV', 'VIH', 'Radiografía de tórax', 'Electrocardiograma'],
    ];

    public function index(Request $request)
    {
        $consultations = NephrologyConsultation::with(['patient', 'doctor'])
            ->when(CurrentSede::id(), fn ($query, $sede) => $query->where('sede_id', $sede))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->whereHas('patient', fn ($patient) => $patient->where('dni', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")->orWhere('surname', 'like', "%{$search}%"));
            })->latest('consultation_date')->paginate(15)->withQueryString();

        return view('consultations.index', compact('consultations'));
    }

    public function create()
    {
        return view('consultations.form', [
            'consultation' => new NephrologyConsultation(['consultation_date' => now()]),
            'patients' => Patient::when(CurrentSede::id(), fn ($q, $sede) => $q->where('sede_id', $sede))->orderBy('surname')->get(),
            'doctors' => User::where('profession', 'like', '%MEDIC%')->orderBy('name')->get(),
            'medications' => collect(self::DEFAULT_MEDICATIONS),
            'examGroups' => self::AUXILIARY_EXAMS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['diagnosis'] = $this->diagnosisSummary($data);
        DB::transaction(function () use ($data) {
            $medications = $data['medications']; unset($data['medications']);
            $data['sede_id'] = CurrentSede::id();
            $data['doctor_id'] = $data['doctor_id'] ?? Auth::id();
            $consultation = NephrologyConsultation::create($data);
            $consultation->medications()->createMany($medications);
        });
        return redirect()->route('consultations.index')->with('success', 'Consulta nefrológica registrada correctamente.');
    }

    public function edit(NephrologyConsultation $consultation)
    {
        $this->authorizeSede($consultation);
        $medications = $consultation->medications;

        if ($medications->isEmpty()) {
            $medications = collect(self::DEFAULT_MEDICATIONS);
        }

        return view('consultations.form', [
            'consultation' => $consultation,
            'patients' => Patient::when(CurrentSede::id(), fn ($q, $sede) => $q->where('sede_id', $sede))->orderBy('surname')->get(),
            'doctors' => User::where('profession', 'like', '%MEDIC%')->orderBy('name')->get(),
            'medications' => $medications,
            'examGroups' => self::AUXILIARY_EXAMS,
        ]);
    }

    public function update(Request $request, NephrologyConsultation $consultation)
    {
        $this->authorizeSede($consultation); $data = $this->validated($request);
        $data['diagnosis'] = $this->diagnosisSummary($data);
        DB::transaction(function () use ($data, $consultation) {
            $medications = $data['medications']; unset($data['medications']);
            $consultation->update($data); $consultation->medications()->delete();
            $consultation->medications()->createMany($medications);
        });
        return redirect()->route('consultations.index')->with('success', 'Consulta nefrológica actualizada.');
    }

    public function prescriptionPdf(NephrologyConsultation $consultation)
    {
        $this->authorizeSede($consultation);
        $consultation->load(['patient', 'doctor', 'sede', 'medications']);
        return Pdf::loadView('consultations.prescription_pdf', compact('consultation'))->setPaper('a4')
            ->stream('receta-nefrologia-'.$consultation->id.'.pdf');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'patient_id' => ['required', 'exists:patients,id'], 'doctor_id' => ['nullable', 'exists:users,id'],
            'consultation_date' => ['required', 'date'], 'blood_pressure' => ['nullable', 'string', 'max:20'],
            'weight' => ['nullable', 'numeric', 'min:0'], 'temperature' => ['nullable', 'numeric', 'between:25,45'],
            'heart_rate' => ['nullable', 'integer', 'between:1,300'], 'oxygen_saturation' => ['nullable', 'integer', 'between:1,100'],
            'reason' => ['nullable', 'string'], 'current_illness' => ['nullable', 'string'], 'history' => ['nullable', 'string'],
            'physical_exam' => ['nullable', 'string'], 'diagnosis' => ['nullable', 'string'], 'treatment_plan' => ['nullable', 'string'], 'observations' => ['nullable', 'string'],
            'diagnoses' => ['nullable', 'array', 'max:10'], 'diagnoses.*.cie10_id' => ['nullable', 'exists:cie10s,id'],
            'diagnoses.*.codigo' => ['required_with:diagnoses.*.descripcion', 'nullable', 'string', 'max:20'],
            'diagnoses.*.descripcion' => ['required_with:diagnoses.*.codigo', 'nullable', 'string', 'max:255'],
            'auxiliary_exams' => ['nullable', 'array'], 'auxiliary_exams.*' => ['string', 'max:100'],
            'next_laboratory_date' => ['nullable', 'date'], 'next_appointment_date' => ['nullable', 'date'],
            'medications' => ['required', 'array', 'min:1'], 'medications.*.fua_code' => ['nullable', 'string', 'max:30'],
            'medications.*.description' => ['required', 'string', 'max:255'], 'medications.*.c' => ['nullable', 'string', 'max:50'],
            'medications.*.prescribed_quantity' => ['nullable', 'numeric', 'min:0'], 'medications.*.delivered_quantity' => ['nullable', 'numeric', 'min:0'],
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
