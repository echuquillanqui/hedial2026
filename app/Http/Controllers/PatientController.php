<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $patients = Patient::orderBy('surname', 'asc')->get();
        return view('patients.index', compact('patients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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
        ]);

        Patient::create($request->all());

        return redirect()->back()->with('success', 'Paciente registrado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Patient $patient)
    {
        $request->validate([
            'dni'                    => ['nullable', 'string', 'size:8', Rule::unique('patients')->ignore($patient->id)],
            'medical_history_number' => ['nullable', 'string', Rule::unique('patients')->ignore($patient->id)],
            'first_name'             => 'required|string|max:100',
            'surname'                => 'required|string|max:100',
            'last_name'              => 'required|string|max:100',
            'insurance_type'         => ['nullable', Rule::in(['ESSALUD', 'SIS', 'SALUDPOL'])],
        ]);

        $patient->update($request->all());

        return redirect()->back()->with('success', 'Historial actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient)
    {
        // Verificamos si existen registros en la relación 'referrals'
        // definida en el modelo Patient.php
        if ($patient->referrals()->exists()) {
            return redirect()->back()->with('error', 'Acción denegada: El paciente tiene hojas de referencia activas en el sistema.');
        }

        $patient->delete();

        return redirect()->back()->with('success', 'Paciente eliminado correctamente.');
    }

    public function search(Request $request) {
    $q = $request->q;
    $patients = \App\Models\Patient::where('dni', 'LIKE', "%$q%")
        ->orWhere('surname', 'LIKE', "%$q%")
        ->get()
        ->map(function($p) {
            return [
                'id' => $p->id,
                'text' => "{$p->surname} {$p->last_name}, {$p->first_name} (DNI: {$p->dni})",
                // Datos exactos de tu migración 'patients'
                'dni' => $p->dni,
                'affiliation_code' => $p->affiliation_code,
                'medical_history_number' => $p->medical_history_number,
                'first_name' => $p->first_name,
                'surname' => $p->surname,
                'last_name' => $p->last_name,
                'other_names' => $p->other_names,
                'is_insured' => (bool)$p->is_insured,
                'insurance_regime' => $p->insurance_regime, // SUBSIDIADO / SEMICONTRIBUTIVO
                'gender' => $p->gender,
                'age' => $p->age,
                'address' => $p->address,
                'district' => $p->district,
                'department' => $p->department,
            ];
        });
    return response()->json(['results' => $patients]);
}
}
