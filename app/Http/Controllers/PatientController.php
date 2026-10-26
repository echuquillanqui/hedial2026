<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientSectorization;
use App\Models\Sede;
use App\Support\DailyHemodialysisSequence;
use App\Support\CurrentSede;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:patients.view')->only(['index', 'show', 'search']);
        $this->middleware('permission:patients.create')->only(['create', 'store']);
        $this->middleware('permission:patients.edit')->only(['edit', 'update']);
        $this->middleware('permission:patients.delete')->only(['destroy']);
    }

    public function index()
    {
        $currentSedeId = CurrentSede::id();
        $patients = Patient::with(['sede', 'sectorizations' => fn ($query) => $query
                ->orderByDesc('effective_from')->orderByDesc('id')])
            ->when($currentSedeId, fn ($q) => $q->where('sede_id', $currentSedeId))
            ->orderBy('surname', 'asc')
            ->select([
                'id', 'sede_id', 'dni', 'affiliation_code', 'medical_history_number',
                'first_name', 'other_names', 'surname', 'last_name', 'is_insured',
                'insurance_type', 'insurance_regime', 'gender', 'birth_date', 'age',
                'address', 'district', 'province', 'department', 'phone',
                'secuencia', 'turno', 'modulo', 'is_active', 'fua_non_signature_reason',
                'peso_seco', 'acceso_arterial', 'acceso_venoso',
            ])
            ->get();

        $patients->each(function (Patient $patient) {
            $assignment = $patient->sectorizations->firstWhere('sequence', $patient->secuencia);
            $patient->setAttribute('sectorization_module', $assignment?->module);
            $patient->setAttribute('sectorization_puesto', $assignment?->puesto ?? $assignment?->numero_maquina);
        });

        $sedes = Sede::where('is_active', true)->orderBy('name')->get();

        return view('patients.index', compact('patients', 'sedes', 'currentSedeId'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $currentSedeId = CurrentSede::id();

        $request->validate([
            'dni'                    => 'nullable|string|size:8|unique:patients,dni',
            'medical_history_number' => 'nullable|string|unique:patients,medical_history_number',
            'first_name'             => 'required|string|max:100',
            'surname'                => 'required|string|max:100',
            'last_name'              => 'required|string|max:100',
            'insurance_type'         => ['nullable', Rule::in(['ESSALUD', 'SIS', 'SALUDPOL'])],
            'insurance_regime'       => ['nullable', Rule::in(['SUBSIDIADO', 'SEMICONTRIBUTIVO'])],
            'gender'                 => ['nullable', Rule::in(['F', 'M'])],
            'birth_date'             => 'nullable|date|before:today',
            'province'               => 'nullable|string|max:100',
            'phone'                  => 'nullable|string|max:30',
            'modulo'                 => ['nullable', Rule::in(Patient::MODULES)],
            'fua_non_signature_reason' => 'nullable|string|max:500',
            'sede_id'                => ['required', 'exists:sedes,id'],
            'is_active'              => 'required|boolean',
        ]);

        if ($currentSedeId && (int) $request->sede_id !== (int) $currentSedeId) {
            return redirect()->back()->withErrors(['sede_id' => 'Solo puede registrar pacientes en la sede activa.'])->withInput();
        }

        Patient::create($request->all());

        return redirect()->back()->with('success', 'Paciente registrado exitosamente.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, Patient $patient)
    {
        $currentSedeId = CurrentSede::id();

        if ($currentSedeId && (int) $patient->sede_id !== (int) $currentSedeId) {
            abort(403, 'No puede editar pacientes de otra sede.');
        }

        $request->validate([
            'dni'                    => ['nullable', 'string', 'size:8', Rule::unique('patients')->ignore($patient->id)],
            'medical_history_number' => ['nullable', 'string', Rule::unique('patients')->ignore($patient->id)],
            'first_name'             => 'required|string|max:100',
            'surname'                => 'required|string|max:100',
            'last_name'              => 'required|string|max:100',
            'insurance_type'         => ['nullable', Rule::in(['ESSALUD', 'SIS', 'SALUDPOL'])],
            'birth_date'             => 'nullable|date|before:today',
            'province'               => 'nullable|string|max:100',
            'phone'                  => 'nullable|string|max:30',
            'modulo'                 => ['nullable', Rule::in(Patient::MODULES)],
            'puesto'                 => ['nullable', 'string', 'max:255'],
            'secuencia'              => ['sometimes', 'nullable', Rule::in([
                DailyHemodialysisSequence::MONDAY_WEDNESDAY_FRIDAY,
                DailyHemodialysisSequence::TUESDAY_THURSDAY_SATURDAY,
            ])],
            'turno'                  => ['sometimes', 'nullable', Rule::in(['1', '2', '3', '4'])],
            'fua_non_signature_reason' => 'nullable|string|max:500',
            'sede_id'                => ['required', 'exists:sedes,id'],
            'is_active'              => 'required|boolean',
        ]);

        if ($currentSedeId && (int) $request->sede_id !== (int) $currentSedeId) {
            return redirect()->back()->withErrors(['sede_id' => 'Solo puede mantener pacientes en la sede activa.'])->withInput();
        }

        $data = $request->all();
        unset($data['modulo'], $data['puesto'], $data['_token'], $data['_method']);

        DB::transaction(function () use ($patient, $data, $request) {
            $patient->update($data);

            if (! $request->hasAny(['modulo', 'puesto'])) {
                return;
            }

            $sequence = $request->input('secuencia', $patient->secuencia);
            if (! in_array($sequence, [
                DailyHemodialysisSequence::MONDAY_WEDNESDAY_FRIDAY,
                DailyHemodialysisSequence::TUESDAY_THURSDAY_SATURDAY,
            ], true)) {
                $sequence = $patient->secuencia;
            }

            $effectiveFrom = $this->nextScheduledDate($sequence);
            $configuredCutoff = DB::table('sectorization_cutoffs')
                ->where('sede_id', $patient->sede_id)
                ->where('sequence', $sequence)
                ->value('effective_from');
            if ($configuredCutoff && $configuredCutoff > $effectiveFrom) {
                $effectiveFrom = $configuredCutoff;
            }

            $assignment = PatientSectorization::query()->firstOrNew([
                'patient_id' => $patient->id,
                'sequence' => $sequence,
                'effective_from' => $effectiveFrom,
            ]);
            if ($request->exists('modulo')) {
                $assignment->module = $request->input('modulo');
                $patient->modulo = $request->input('modulo');
                $patient->save();
            }
            if ($request->exists('puesto')) {
                $assignment->puesto = $request->input('puesto');
                $assignment->numero_maquina = $request->input('puesto');
            }
            $assignment->save();

            $orders = $patient->orders()->where('sede_id', $patient->sede_id)
                ->whereDate('fecha_orden', '>=', $effectiveFrom)->with('nurse')->get()
                ->filter(fn ($order) => DailyHemodialysisSequence::forDate($order->fecha_orden) === $sequence);
            foreach ($orders as $order) {
                if ($request->exists('modulo')) {
                    $module = $request->input('modulo');
                    if ($module) {
                        $order->sala = $module === Patient::ISOLATED_MODULE ? 'AISLADO' : 'MODULO '.$module;
                        $order->save();
                    }
                }
                if ($request->exists('puesto') && $order->nurse) {
                    $order->nurse->puesto = $request->input('puesto');
                    $order->nurse->numero_maquina = $request->input('puesto');
                    $order->nurse->save();
                }
            }
        });

        return redirect()->back()->with('success', 'Historial actualizado correctamente.');
    }

    public function destroy(Patient $patient)
    {
        $currentSedeId = CurrentSede::id();

        if ($currentSedeId && (int) $patient->sede_id !== (int) $currentSedeId) {
            abort(403, 'No puede eliminar pacientes de otra sede.');
        }

        if ($patient->referrals()->exists()) {
            return redirect()->back()->with('error', 'Acción denegada: El paciente tiene hojas de referencia activas en el sistema.');
        }

        $patient->delete();

        return redirect()->back()->with('success', 'Paciente eliminado correctamente.');
    }

    public function search(Request $request)
    {
        $q = $request->q;
        $insuranceType = strtoupper((string) $request->insurance_type);
        $currentSedeId = CurrentSede::id();

        $patientsQuery = \App\Models\Patient::query()
            ->when($currentSedeId, fn ($query) => $query->where('sede_id', $currentSedeId))
            ->where(function ($query) use ($q) {
                $query->where('dni', 'LIKE', "%$q%")
                    ->orWhere('surname', 'LIKE', "%$q%")
                    ->orWhere('last_name', 'LIKE', "%$q%")
                    ->orWhere('first_name', 'LIKE', "%$q%");
            });

        if ($insuranceType === 'SIS') {
            $patientsQuery->where('insurance_type', 'SIS');
        } elseif ($insuranceType === 'ESSALUD') {
            $patientsQuery->where('insurance_type', '!=', 'SIS');
        }

        $patients = $patientsQuery
            ->select([
                'id', 'dni', 'affiliation_code', 'medical_history_number',
                'first_name', 'other_names', 'surname', 'last_name', 'is_insured',
                'insurance_regime', 'gender', 'age', 'birth_date', 'address',
                'district', 'province', 'department', 'phone', 'sede_id',
            ])
            ->orderBy('surname')
            ->orderBy('first_name')
            ->limit(50)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'text' => "{$p->surname} {$p->last_name}, {$p->first_name} (DNI: {$p->dni})",
                    'dni' => $p->dni,
                    'affiliation_code' => $p->affiliation_code,
                    'medical_history_number' => $p->medical_history_number,
                    'first_name' => $p->first_name,
                    'surname' => $p->surname,
                    'last_name' => $p->last_name,
                    'other_names' => $p->other_names,
                    'is_insured' => (bool) $p->is_insured,
                    'insurance_regime' => $p->insurance_regime,
                    'gender' => $p->gender,
                    'age' => $p->age,
                    'address' => $p->address,
                    'district' => $p->district,
                    'province' => $p->province,
                    'department' => $p->department,
                    'phone' => $p->phone,
                    'sede_id' => $p->sede_id,
                ];
            });
        return response()->json(['results' => $patients]);
    }

    private function nextScheduledDate(string $sequence): string
    {
        $date = Carbon::tomorrow();
        for ($day = 0; $day < 7; $day++, $date->addDay()) {
            if (DailyHemodialysisSequence::forDate($date) === $sequence) {
                return $date->toDateString();
            }
        }

        return Carbon::tomorrow()->toDateString();
    }
}
