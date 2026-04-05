<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Sede;
use App\Support\CurrentSede;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function index()
    {
        $currentSedeId = CurrentSede::id();
        $patients = Patient::with('sede')
            ->when($currentSedeId, fn ($q) => $q->where('sede_id', $currentSedeId))
            ->orderBy('surname', 'asc')
            ->get();

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
            'sede_id'                => ['required', 'exists:sedes,id'],
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
            'sede_id'                => ['required', 'exists:sedes,id'],
        ]);

        if ($currentSedeId && (int) $request->sede_id !== (int) $currentSedeId) {
            return redirect()->back()->withErrors(['sede_id' => 'Solo puede mantener pacientes en la sede activa.'])->withInput();
        }

        $patient->update($request->all());

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
                    ->orWhere('surname', 'LIKE', "%$q%");
            });

        if ($insuranceType === 'SIS') {
            $patientsQuery->where('insurance_type', 'SIS');
        } elseif ($insuranceType === 'ESSALUD') {
            $patientsQuery->where('insurance_type', '!=', 'SIS');
        }

        $patients = $patientsQuery->get()
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
                    'department' => $p->department,
                    'sede_id' => $p->sede_id,
                ];
            });
        return response()->json(['results' => $patients]);
    }
}
