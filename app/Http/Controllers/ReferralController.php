<?php

namespace App\Http\Controllers;

use App\Models\{Referral, Patient, User, DiagnosisTreatment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReferralController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // En ReferralController.php

    public function index(Request $request)
    {
        $query = Referral::with(['patient', 'referralResponsible']);

        // Filtro por Texto (Nombre, DNI, Código)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('referral_code', 'LIKE', "%{$search}%")
                ->orWhere('origin_facility', 'LIKE', "%{$search}%")
                ->orWhere('destination_facility', 'LIKE', "%{$search}%")
                ->orWhereHas('patient', function($p) use ($search) {
                    $p->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('surname', 'LIKE', "%{$search}%")
                        ->orWhere('dni', 'LIKE', "%{$search}%");
                });
            });
        }

        // Filtro por Fechas
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->from_date)->startOfDay(),
                Carbon::parse($request->to_date)->endOfDay()
            ]);
        }

        $referrals = $query->latest()->get();

        // Si es una petición AJAX (desde nuestro script de búsqueda)
        if ($request->ajax()) {
            return view('referrals.partials.table_rows', compact('referrals'))->render();
        }

        return view('referrals.index', compact('referrals'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Detectamos el tipo desde la URL: /referrals/create?type=SIS
        $type = strtoupper($request->query('type', 'SIS')); 
        $patients = Patient::all();
        $staff = User::all();

        // Retornamos la vista correspondiente según el PDF
        $view = ($type === 'SIS') ? 'referrals.create_sis' : 'referrals.create_essalud';
        
        return view($view, compact('patients', 'staff', 'type'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validar los datos (opcional pero recomendado)
         $validated = $this->validateReferral($request);

        try {
            return DB::transaction(function () use ($validated) {
                
                // 2. Crear la referencia
                // Al ejecutar 'create', se dispara el evento 'creating' en Referral.php
                // El cual buscará la numeración, incrementará el contador y asignará el code.
                $referral = Referral::create(collect($validated)->except('diagnoses')->toArray());

                // 3. Guardar los diagnósticos asociados
                if (!empty($validated['diagnoses'])) {
                    foreach ($validated['diagnoses'] as $row) {
                        // Validamos que la fila tenga datos básicos antes de guardar
                        $referral->diagnosisTreatments()->create([
                            'icd_10_code' => $row['icd_10_code'],
                            'diagnosis'    => $row['diagnosis'],
                            'treatment'    => $row['treatment'],
                            'D'            => isset($row['D']) ? 'X' : null,
                            'P'            => isset($row['P']) ? 'X' : null,
                            'R'            => isset($row['R']) ? 'X' : null,
                        ]);
                    }
                }

                return redirect()->route('referrals.index')
                    ->with('success', "Referencia {$referral->referral_code} creada exitosamente.");
            });

        } catch (\Exception $e) {
            // Si algo falla, Laravel hace rollback y no se "gasta" el número en la numeración
            return back()->withInput()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {

    }

    public function downloadPdf($id)
    {
        // Cargamos la referencia con sus hijos (diagnósticos) y su padre (paciente)
        $referral = Referral::with(['patient', 'diagnosisTreatments', 'referralResponsible'])
                    ->findOrFail($id);

            if ($referral->patient && $referral->patient->birth_date) {
                $referral->patient->calculated_age = Carbon::parse($referral->patient->birth_date)
                    ->diffInYears($referral->created_at);
            } else {
                $referral->patient->calculated_age = 'N/A';
            }

        // Generamos la vista
        $pdf = Pdf::loadView('referrals.pdf', compact('referral'));

        // Configuración de hoja A4
        $pdf->setPaper('a4', 'portrait');

        // Retornamos el stream (abrir en navegador) o download (descarga directa)
        return $pdf->stream('Referencia_' . $referral->referral_code . '.pdf');
    }

    public function downloadPdfEssalud($id)
    {
        // 1. Cargamos la referencia con todas sus relaciones necesarias
        $referral = Referral::with([
            'patient', 
            'diagnosisTreatments', 
            'referralResponsible', 
            'facilityResponsible', 
            'escortStaff'
        ])->findOrFail($id);
        if ($referral->patient && $referral->patient->birth_date) {
                $referral->patient->calculated_age = Carbon::parse($referral->patient->birth_date)
                    ->diffInYears($referral->created_at);
            } else {
                $referral->patient->calculated_age = 'N/A';
            }

        // 2. Cargamos la vista que creamos anteriormente
        // Importante: La vista debe llamarse 'referrals.pdf_essalud'
        $pdf = Pdf::loadView('referrals.pdf_essalud', compact('referral'));

        // 3. Configuraciones de papel (A4 vertical)
        $pdf->setPaper('a4', 'portrait');

        // 4. Retornar el PDF (stream para ver en navegador o download para descargar)
        return $pdf->stream("Referencia_EsSalud_{$referral->referral_code}.pdf");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Referral $referral)
    {
        // Cargamos las relaciones necesarias
        $referral->load('patient');
        $staff = User::all();

        // Detectamos el tipo según el dato guardado en la referencia
        // Si no tienes un campo 'type', podrías usar el régimen del paciente
        $type = $referral->type ?? ($referral->patient->insurance_type === 'ESSALUD' ? 'ESSALUD' : 'SIS');

        // Elegimos la vista correspondiente
        $view = ($type === 'ESSALUD') ? 'referrals.edit_essalud' : 'referrals.edit_sis';

        return view($view, compact('referral', 'staff', 'type'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $referral = Referral::findOrFail($id);

        $validated = $this->validateReferral($request);

        // 1. Actualizar datos principales
        $referral->update(collect($validated)->except('diagnoses')->toArray());

        // 2. Sincronizar Diagnósticos (Borrar y crear es lo más seguro para tablas dinámicas)
        $referral->diagnosisTreatments()->delete();

        if (!empty($validated['diagnoses'])) {
            foreach ($validated['diagnoses'] as $row) {
                    $referral->diagnosisTreatments()->create([
                        'icd_10_code' => $row['icd_10_code'],
                        'diagnosis'    => $row['diagnosis'],
                        'treatment'    => $row['treatment'],
                        'D'            => isset($row['D']) ? 'X' : null,
                        'P'            => isset($row['P']) ? 'X' : null,
                        'R'            => isset($row['R']) ? 'X' : null,
                    ]);
                }
            }
        

        return redirect()->route('referrals.edit', $referral->id)->with('success', 'Referencia actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Referral $referral)
    {
        // Protección de integridad: No eliminar si el paciente existe o hay registros clínicos críticos
        if ($referral->patient()->exists()) {
            return redirect()->back()->with('error', 'No se puede eliminar la referencia porque tiene un paciente asignado. Debe anularla o archivarla.');
        }

        $referral->delete();
        return redirect()->back()->with('success', 'Referencia eliminada.');
    }

    protected function validateReferral(Request $request)
    {
        return $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'referral_type' => 'required|in:EMERGENCIA,CONSULTA EXTERNA,APOYO AL DX',
            'origin_facility' => 'required|string|max:255',
            'destination_facility' => 'required|string|max:255',
            'destination_specialty' => 'required|string|max:255',
            'anamnesis' => 'required|string',
            'general_state' => 'required|string|max:255',
            'temperature' => 'required|string|max:255',
            'blood_pressure' => 'required|string|max:255',
            'respiratory_rate' => 'required|string|max:255',
            'heart_rate' => 'required|string|max:255',
            'oxygen_saturation' => 'required|string|max:255',
            'skin_subcutaneous' => 'required|string|max:255',
            'lungs' => 'required|string|max:255',
            'cardiovascular' => 'required|string|max:255',
            'neurological' => 'required|string|max:255',
            'auxiliary_exams' => 'required|string',
            'others' => 'required|string',
            'appointment_date' => 'nullable|date',
            'appointment_time' => 'nullable|date_format:H:i',
            'attending_physician_name' => 'nullable|string|max:255',
            'coordination_name' => 'nullable|string|max:255',
            'patient_condition' => 'required|in:ESTABLE,MAL ESTADO',
            'arrival_condition' => 'required|in:ESTABLE,MAL ESTADO,FALLECIDO',
            'diagnoses' => 'required|array|min:1',
            'diagnoses.*.icd_10_code' => 'required|string|max:10',
            'diagnoses.*.diagnosis' => 'required|string',
            'diagnoses.*.treatment' => 'required|string',
            'referral_responsible_id' => 'required|exists:users,id',
            'facility_responsible_id' => 'required|exists:users,id',
            'escort_staff_id' => 'required|exists:users,id',
            'receiving_staff_id' => 'nullable|exists:users,id',
            ], [
            'required' => 'El campo :attribute es obligatorio.',
            'diagnoses.required' => 'Debe registrar al menos un diagnóstico.',
            'diagnoses.*.icd_10_code.required' => 'El CIE-10 es obligatorio en cada fila de diagnóstico.',
            'diagnoses.*.diagnosis.required' => 'El diagnóstico es obligatorio en cada fila.',
            'diagnoses.*.treatment.required' => 'El tratamiento es obligatorio en cada fila.',
        ], [
            'patient_id' => 'paciente',
            'referral_type' => 'tipo de referencia',
            'origin_facility' => 'establecimiento de origen',
            'destination_facility' => 'establecimiento destino',
            'destination_specialty' => 'especialidad destino',
            'patient_condition' => 'condición de inicio',
            'arrival_condition' => 'condición de llegada',
            'referral_responsible_id' => 'responsable de referencia',
            'facility_responsible_id' => 'responsable del establecimiento',
            'escort_staff_id' => 'personal acompañante',
            'receiving_staff_id' => 'responsable de recepción',
            'anamnesis' => 'anamnesis',
            'general_state' => 'estado general',
            'temperature' => 'temperatura',
            'blood_pressure' => 'presión arterial',
            'respiratory_rate' => 'frecuencia respiratoria',
            'heart_rate' => 'frecuencia cardíaca',
            'oxygen_saturation' => 'saturación de oxígeno',
            'skin_subcutaneous' => 'piel y tejido subcutáneo',
            'lungs' => 'pulmones',
            'cardiovascular' => 'cardiovascular',
            'neurological' => 'neurológico',
            'auxiliary_exams' => 'exámenes auxiliares',
            'others' => 'otros',
            'appointment_date' => 'fecha de cita',
            'appointment_time' => 'hora de cita',
            'attending_physician_name' => 'nombre de quien atenderá',
            'coordination_name' => 'nombre con quien se coordinó',
        ]);
    }

}
