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
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:patients.view')->only(['index', 'show', 'search', 'export']);
        $this->middleware('permission:patients.create')->only(['create', 'store', 'import']);
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

    public function downloadImportTemplate()
    {
        $headers = [
            'dni', 'apellido_paterno', 'apellido_materno', 'primer_nombre', 'otros_nombres',
            'codigo_afiliacion', 'numero_historia_clinica', 'tipo_seguro', 'regimen_seguro',
            'asegurado', 'sexo', 'fecha_nacimiento', 'edad', 'direccion', 'distrito',
            'provincia', 'departamento', 'telefono', 'secuencia', 'turno', 'modulo', 'activo',
        ];
        $example = ['12345678', 'PEREZ', 'GARCIA', 'JUAN', 'CARLOS', 'SIS-001', 'HC-001', 'SIS', 'SUBSIDIADO', 'SI', 'M', '1980-05-20', '46', 'AV. EJEMPLO 123', 'LIMA', 'LIMA', 'LIMA', '999888777', 'L-M-V', '1', '1', 'SI'];

        return response()->streamDownload(function () use ($headers, $example) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers, ';');
            fputcsv($output, $example, ';');
            fclose($output);
        }, 'plantilla_importacion_pacientes.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function export()
    {
        $patients = Patient::query()
            ->with('sede')
            ->when(CurrentSede::id(), fn ($query, $sedeId) => $query->where('sede_id', $sedeId))
            ->orderBy('surname')->orderBy('last_name')->orderBy('first_name')->get();

        return response()->streamDownload(function () use ($patients) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'DNI', 'Apellido paterno', 'Apellido materno', 'Primer nombre', 'Otros nombres',
                'Historia clínica', 'Código de afiliación', 'Tipo de seguro', 'Régimen', 'Asegurado',
                'Sexo', 'Fecha de nacimiento', 'Edad', 'Dirección', 'Distrito', 'Provincia',
                'Departamento', 'Teléfono', 'Secuencia', 'Turno', 'Módulo', 'Sede', 'Estado',
            ], ';');

            foreach ($patients as $patient) {
                $values = [
                    $patient->dni, $patient->surname, $patient->last_name, $patient->first_name,
                    $patient->other_names, $patient->medical_history_number, $patient->affiliation_code,
                    $patient->insurance_type, $patient->insurance_regime,
                    $patient->is_insured ? 'Sí' : 'No', $patient->gender,
                    optional($patient->birth_date)->format('Y-m-d'), $patient->age, $patient->address,
                    $patient->district, $patient->province, $patient->department, $patient->phone,
                    $patient->secuencia, $patient->turno, $patient->modulo, $patient->sede?->name,
                    $patient->is_active ? 'Activo' : 'Inactivo',
                ];
                $values = array_map(function ($value) {
                    $value = (string) ($value ?? '');
                    return preg_match('/^[\s]*[=+@\\-]/u', $value) ? "'".$value : $value;
                }, $values);
                fputcsv($output, $values, ';');
            }

            fclose($output);
        }, 'pacientes_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $sedeId = CurrentSede::id();
        abort_unless($sedeId, 403, 'Seleccione una sede antes de importar pacientes.');

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->with('error', 'No se pudo leer el archivo.');
        }

        $headers = fgetcsv($handle, 0, ';');
        if (! $headers) {
            fclose($handle);
            return back()->with('error', 'El archivo está vacío o no tiene encabezados válidos.');
        }
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $expected = ['dni', 'apellido_paterno', 'apellido_materno', 'primer_nombre', 'otros_nombres', 'codigo_afiliacion', 'numero_historia_clinica', 'tipo_seguro', 'regimen_seguro', 'asegurado', 'sexo', 'fecha_nacimiento', 'edad', 'direccion', 'distrito', 'provincia', 'departamento', 'telefono', 'secuencia', 'turno', 'modulo', 'activo'];
        if ($headers !== $expected) {
            fclose($handle);
            return back()->with('error', 'Los encabezados no coinciden con la plantilla. Descargue la plantilla actualizada y vuelva a intentarlo.');
        }

        $inserted = 0;
        $skipped = 0;
        $issues = [];
        $rowNumber = 1;
        $availableColumns = Schema::getColumnListing('patients');

        DB::transaction(function () use ($handle, $sedeId, &$inserted, &$skipped, &$issues, &$rowNumber, $availableColumns) {
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $rowNumber++;
                if (count($row) === 1 && trim((string) $row[0]) === '') continue;
                if (count($row) !== 22) {
                    $skipped++;
                    $issues[] = "Fila {$rowNumber}: cantidad de columnas incorrecta.";
                    continue;
                }
                $data = array_combine(['dni', 'surname', 'last_name', 'first_name', 'other_names', 'affiliation_code', 'medical_history_number', 'insurance_type', 'insurance_regime', 'is_insured', 'gender', 'birth_date', 'age', 'address', 'district', 'province', 'department', 'phone', 'secuencia', 'turno', 'modulo', 'is_active'], array_map(fn ($value) => trim((string) $value), $row));
                foreach ($data as $key => $value) if ($value === '') $data[$key] = null;
                $data['dni'] = $data['dni'] ? preg_replace('/\D/', '', $data['dni']) : null;
                $data['gender'] = $data['gender'] ? strtoupper($data['gender']) : null;
                $data['insurance_type'] = $data['insurance_type'] ? strtoupper($data['insurance_type']) : null;
                $data['insurance_regime'] = $data['insurance_regime'] ? strtoupper($data['insurance_regime']) : 'SUBSIDIADO';
                $data['secuencia'] = $data['secuencia'] ? strtoupper($data['secuencia']) : null;
                $data['turno'] = $data['turno'] ? (string) $data['turno'] : null;
                $data['modulo'] = $data['modulo'] ? strtoupper($data['modulo']) : null;
                $data['is_insured'] = in_array(strtoupper((string) $data['is_insured']), ['SI', 'SÍ', '1', 'TRUE', 'YES'], true);
                $data['is_active'] = in_array(strtoupper((string) $data['is_active']), ['SI', 'SÍ', '1', 'TRUE', 'YES', 'ACTIVO'], true);
                $data['sede_id'] = $sedeId;

                $errors = [];
                if (! $data['first_name'] || ! $data['surname'] || ! $data['last_name']) $errors[] = 'apellidos y primer nombre son obligatorios';
                if ($data['dni'] !== null && ! preg_match('/^\d{8}$/', $data['dni'])) $errors[] = 'el DNI debe tener 8 dígitos';
                if ($data['dni'] && Patient::where('dni', $data['dni'])->exists()) $errors[] = 'el DNI ya está registrado';
                if ($data['gender'] !== null && ! in_array($data['gender'], ['F', 'M'], true)) $errors[] = 'sexo debe ser F o M';
                if ($data['insurance_type'] !== null && ! in_array($data['insurance_type'], ['ESSALUD', 'SIS', 'SALUDPOL'], true)) $errors[] = 'tipo de seguro inválido';
                if (! in_array($data['insurance_regime'], ['SUBSIDIADO', 'SEMICONTRIBUTIVO'], true)) $errors[] = 'régimen de seguro inválido';
                if ($data['secuencia'] !== null && ! in_array($data['secuencia'], ['L-M-V', 'M-J-S'], true)) $errors[] = 'secuencia debe ser L-M-V o M-J-S';
                if ($data['turno'] !== null && ! in_array($data['turno'], ['1', '2', '3', '4'], true)) $errors[] = 'turno debe ser 1, 2, 3 o 4';
                if ($data['modulo'] !== null && ! in_array($data['modulo'], Patient::MODULES, true)) $errors[] = 'módulo inválido';
                if ($data['birth_date'] !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['birth_date'])) $errors[] = 'fecha de nacimiento debe tener formato AAAA-MM-DD';
                if ($data['age'] !== null && ! ctype_digit((string) $data['age'])) $errors[] = 'edad debe ser numérica';
                if ($errors) {
                    $skipped++;
                    $issues[] = "Fila {$rowNumber}: ".implode(', ', $errors).'.';
                    continue;
                }
                $data = array_intersect_key($data, array_flip($availableColumns));
                Patient::create($data);
                $inserted++;
            }
        });
        fclose($handle);

        $message = "Importación completada: {$inserted} pacientes registrados y {$skipped} filas omitidas.";
        return back()->with($skipped ? 'error' : 'success', $message)->with('import_issues', array_slice($issues, 0, 20));
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

        // The station belongs to patient_sectorizations, not the patients table.
        Patient::create($request->except(['puesto', '_token', '_method']));

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
