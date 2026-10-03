<?php

namespace App\Http\Controllers;

use App\Models\DialysisSupplyLot;
use App\Models\DisposableDiscard;
use App\Models\Medical;
use App\Models\MedicalModuleSchedule;
use App\Models\Nurse;
use App\Models\Patient;
use App\Models\User;
use App\Services\WarehouseConsumptionService;
use App\Support\CurrentSede;
use App\Support\DailyHemodialysisSequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MedicalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:medicals.view')->only(['index', 'show']);
        $this->middleware('permission:medicals.edit')->only(['edit', 'update', 'editModuleSchedules', 'updateModuleSchedules']);
    }

    /**
     * Listado con búsqueda interactiva y filtros.
     */
    public function index(Request $request)
    {
        $dateFilter = $request->get('date', date('Y-m-d'));
        $dailySequence = DailyHemodialysisSequence::forDate($dateFilter);

        $medicals = Medical::with(['order.patient', 'usuarioInicia', 'usuarioFinaliza'])
            ->when(CurrentSede::id(), function ($query) {
                $query->whereHas('order', fn ($q) => $q->where('sede_id', CurrentSede::id()));
            })
            ->when($request->search, function ($query, $search) {
                $query->whereHas('order.patient', function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('surname', 'like', "%{$search}%")
                      ->orWhere('dni', 'like', "%{$search}%");
                })->orWhereHas('order', function($q) use ($search) {
                    $q->where('codigo_unico', 'like', "%{$search}%");
                });
            })
            ->when($dateFilter, function ($query, $date) {
                $query->whereHas('order', function($q) use ($date) {
                    $q->whereDate('fecha_orden', $date);
                });
            })
            ->when($dailySequence, function ($query, $sequence) {
                $query->whereHas('order.patient', fn ($patient) => $patient->scheduledForSequence($sequence));
            })
            ->when($request->turno, function ($query, $turno) {
                $query->whereHas('order', function($q) use ($turno) {
                    $q->where('turno', $turno);
                });
            })
            ->when($request->modulo, function ($query, $modulo) {
                $query->whereHas('order.patient', fn ($q) => $q->where('modulo', $modulo));
            })
            ->when($request->estado, function ($query, $estado) {
                if ($estado === 'finalizado') {
                    $query->whereNotNull('hora_final');
                } elseif ($estado === 'en_curso') {
                    $query->whereNull('hora_final');
                }
            })
            ->latest()
            ->paginate(15)
            ->appends($request->all());

        return view('atenciones.medicina.index', compact('medicals'));
    }

    /**
     * Formulario de edición con selección de médicos.
     */
    public function edit(Medical $medical)
    {
        if (CurrentSede::id() && (int) optional($medical->order)->sede_id !== (int) CurrentSede::id()) {
            abort(403, 'Atención fuera de la sede activa.');
        }
        $order = $medical->order;
        $order->loadMissing('patient', 'treatments');
        
        // Obtenemos solo los usuarios cuya profesión sea MEDICO
        $medicos = User::where('profession', 'MEDICO')->get();
        
        $dialyzerMeasurements = DialysisSupplyLot::query()
            ->where('category', DisposableDiscard::DIALYZER)->where('is_active', true)
            ->whereDate('valid_until', '>=', $order->fecha_orden)->pluck('measurement')->map(fn ($value) => (string) $value)->unique()->sort()->values();
        if ($medical->area_filtro && ! $dialyzerMeasurements->contains((string) $medical->area_filtro)) {
            $dialyzerMeasurements->push((string) $medical->area_filtro);
        }

        $moduleSchedule = MedicalModuleSchedule::query()
            ->where('sede_id', $order->sede_id)
            ->where('module', (string) $order->patient->modulo)
            ->where('shift', $order->turno)
            ->first();
        $startSuggestions = $moduleSchedule?->startSlots() ?? collect();
        $finishSuggestions = $moduleSchedule?->finishSlots() ?? collect();
        $treatmentTimes = $order->treatments->pluck('hora')->filter()
            ->map(fn ($time) => substr((string) $time, 0, 5))->values();
        $firstTreatmentTime = $treatmentTimes->first();
        $lastTreatmentTime = $this->lastTimeAfterReference($treatmentTimes, $firstTreatmentTime);

        if ($lastTreatmentTime && $firstTreatmentTime) {
            $lastElapsed = $this->elapsedMinutes($lastTreatmentTime, $firstTreatmentTime);
            $finishSuggestions = $finishSuggestions->filter(
                fn ($time) => $this->elapsedMinutes($time, $firstTreatmentTime) > $lastElapsed
            )->values();
        }

        return view('atenciones.medicina.edit', compact(
            'medical', 'order', 'medicos', 'dialyzerMeasurements', 'moduleSchedule',
            'startSuggestions', 'finishSuggestions', 'firstTreatmentTime', 'lastTreatmentTime'
        ));
    }

    public function editModuleSchedules()
    {
        $schedules = MedicalModuleSchedule::query()
            ->where('sede_id', CurrentSede::id())->get()->groupBy('module')
            ->map(fn ($moduleSchedules) => $moduleSchedules->keyBy('shift'));

        return view('atenciones.medicina.configuration', compact('schedules'));
    }

    public function updateModuleSchedules(Request $request)
    {
        $allowedModules = implode(',', Patient::MODULES);
        $rules = ['schedules' => ['required', 'array:'.$allowedModules, 'min:1']];
        foreach (Patient::MODULES as $module) {
            $rules["schedules.{$module}"] = ['sometimes', 'array:1,2,3,4', 'size:4'];
            foreach (range(1, 4) as $shift) {
                $prefix = "schedules.{$module}.{$shift}";
                $rules[$prefix] = ['required_with:schedules.'.$module, 'array'];
                foreach (['start_from', 'start_to', 'finish_from', 'finish_to'] as $field) {
                    $rules["{$prefix}.{$field}"] = ['required', 'date_format:H:i'];
                }
                $rules["{$prefix}.interval_minutes"] = ['required', 'integer', Rule::in([1, 2, 5, 10, 15, 20, 30])];
            }
        }

        $validated = $request->validate($rules);
        DB::transaction(function () use ($validated) {
            foreach ($validated['schedules'] as $module => $shifts) {
                foreach ($shifts as $shift => $schedule) {
                    MedicalModuleSchedule::updateOrCreate(
                        ['sede_id' => CurrentSede::id(), 'module' => $module, 'shift' => $shift],
                        $schedule
                    );
                }
            }
        });

        return redirect()->route('medicals.schedules.edit')
            ->with('success', 'Los rangos médicos por módulo fueron actualizados.');
    }

    /**
     * Actualización integral de la ficha médica.
     */
    public function update(Request $request, Medical $medical)
    {
        if (CurrentSede::id() && (int) optional($medical->order)->sede_id !== (int) CurrentSede::id()) {
            abort(403, 'Atención fuera de la sede activa.');
        }

        // MySQL TIME columns are returned as HH:MM:SS. Some browsers submit that
        // complete value again even though the time control only shows HH:MM.
        // Keep the value in the format used by this form before validating it.
        $request->merge([
            'hora_inicial' => $this->withoutSeconds($request->input('hora_inicial')),
            'hora_final' => $this->withoutSeconds($request->input('hora_final')),
        ]);

        $configuredMeasurements = DialysisSupplyLot::query()->where('category', DisposableDiscard::DIALYZER)
            ->where('is_active', true)->pluck('measurement')->map(fn ($value) => (string) $value)->unique()->all();
        $allowedMeasurements = $configuredMeasurements ?: ['1.3', '1.5', '1.8', '1.9', '2.1', '2.2'];

        $validated = $request->validate([
            // Signos Vitales e Iniciales (Migración)
            'hora_inicial'        => 'nullable|date_format:H:i',
            'peso_inicial'        => 'nullable|numeric',
            'pa_inicial'          => 'nullable|string',
            'frecuencia_cardiaca' => 'nullable|integer',
            'so2'                 => 'nullable|integer',
            'fio2'                => 'nullable|numeric',
            'temperatura'         => 'nullable|numeric',
            
            // Textos Clínicos
            'problemas_clinicos'  => 'nullable|string',
            'evaluacion'          => 'nullable|string',
            'indicaciones'        => 'nullable|string',
            'signos_sintomas'     => 'nullable|string',

            // Medicación (Migración)
            'epo2000'             => 'nullable|string',
            'epo4000'             => 'nullable|string',
            'hierro'              => 'nullable|string',
            'vitamina_b12'        => 'nullable|string',
            'calcitriol'          => 'nullable|string',
            'heparina'            => 'nullable|string',

            // Parámetros Técnicos
            'hora_hd'             => 'required|numeric',
            'peso_seco'           => 'nullable|numeric',
            'uf'                  => 'required|string|max:20',
            'qb'                  => 'nullable|integer',
            'qd'                  => 'nullable|integer',
            'bicarbonato'         => 'nullable|integer',
            'na_inicial'          => 'nullable|integer',
            'cnd'                 => 'nullable|numeric',
            'na_final'            => 'nullable|integer',
            'perfil_na'           => 'nullable|string',
            'area_filtro'         => ['required', Rule::in($allowedMeasurements)],
            'membrana'            => 'nullable|string',
            'perfil_uf'           => 'nullable|string',

            // Cierre y Responsables
            'evaluacion_final'    => 'nullable|string',
            'hora_final'          => 'nullable|date_format:H:i',
            'usuario_que_inicia_hd'   => 'nullable|exists:users,id',
            'usuario_que_finaliza_hd' => 'nullable|exists:users,id',
        ]);

        $timeValidator = validator($validated);
        $timeValidator->after(function ($validator) use ($medical, $validated) {
            $initialTime = $validated['hora_inicial'] ?? null;
            $finalTime = $validated['hora_final'] ?? null;

            // La hora de inicio puede coincidir entre pacientes del mismo turno.
            // Solo se conserva el control de duplicados para la hora de cierre.
            if ($finalTime) {
                $timeAlreadyUsed = Medical::query()
                    ->whereKeyNot($medical->id)
                    ->where(function ($query) use ($finalTime) {
                        $query->where('hora_inicial', $finalTime)
                            ->orWhere('hora_final', $finalTime);
                    })
                    ->whereHas('order', function ($query) use ($medical) {
                        $query->whereDate('fecha_orden', $medical->order->fecha_orden)
                            ->where('turno', $medical->order->turno)
                            ->where('sede_id', $medical->order->sede_id);
                    })
                    ->exists();

                if ($timeAlreadyUsed) {
                    $validator->errors()->add(
                        'hora_final',
                        'La hora seleccionada ya fue registrada en este turno.'
                    );
                }
            }

            if ($initialTime && $finalTime && $initialTime === $finalTime) {
                $validator->errors()->add(
                    'hora_final',
                    'La hora final debe ser distinta de la hora inicial.'
                );
            }

            if ($finalTime) {
                $treatmentTimes = $medical->order->treatments()
                    ->whereNotNull('hora')
                    ->orderBy('id')->pluck('hora')
                    ->map(fn ($time) => substr((string) $time, 0, 5));
                $referenceTime = $initialTime ?: $treatmentTimes->first();
                $toMinutes = static function (string $time): int {
                    [$hours, $minutes] = array_map('intval', explode(':', $time));
                    return ($hours * 60) + $minutes;
                };
                $elapsed = static fn (string $time, string $reference): int =>
                    ($toMinutes($time) - $toMinutes($reference) + 1440) % 1440;
                $lastTreatmentTime = $referenceTime
                    ? $treatmentTimes->sortByDesc(fn ($time) => $elapsed($time, $referenceTime))->first()
                    : null;

                if ($lastTreatmentTime && $referenceTime
                    && $elapsed($finalTime, $referenceTime) <= $elapsed($lastTreatmentTime, $referenceTime)) {
                    $validator->errors()->add(
                        'hora_final',
                        'La hora final médica debe ser posterior a la última hora registrada en el tratamiento (' . $lastTreatmentTime . '), considerando el cambio de día.'
                    );
                }
            }
        });

        $timeValidator->validate();

        // Si no se selecciona un médico de inicio, se asigna el usuario actual por defecto
        if (!$request->filled('usuario_que_inicia_hd') && !$medical->usuario_que_inicia_hd) {
            $validated['usuario_que_inicia_hd'] = Auth::id();
        }

        $previousMedications = $medical->only($this->nursingMedicationFields());
        $medical->update($validated);
        $this->syncMedicationToNurse($medical, $previousMedications);
        app(WarehouseConsumptionService::class)->consumeIfFinalized($medical->order->fresh());

        return redirect()->route('medicals.index')
            ->with('success', 'Ficha médica actualizada correctamente.');
    }

    private function withoutSeconds(mixed $time): mixed
    {
        if (is_string($time) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return substr($time, 0, 5);
        }

        return $time;
    }

    private function elapsedMinutes(string $time, string $reference): int
    {
        $toMinutes = static function (string $value): int {
            [$hours, $minutes] = array_map('intval', explode(':', substr($value, 0, 5)));

            return ($hours * 60) + $minutes;
        };

        return ($toMinutes($time) - $toMinutes($reference) + 1440) % 1440;
    }

    private function lastTimeAfterReference(Collection $times, ?string $reference): ?string
    {
        if (! $reference || $times->isEmpty()) {
            return null;
        }

        return $times->sortByDesc(fn ($time) => $this->elapsedMinutes($time, $reference))->first();
    }

    /**
     * Actualiza únicamente la medicación desde el listado de atenciones.
     */
    public function updateMedications(Request $request, Medical $medical)
    {
        if (CurrentSede::id() && (int) optional($medical->order)->sede_id !== (int) CurrentSede::id()) {
            abort(403, 'Atención fuera de la sede activa.');
        }

        $medications = $request->validate([
            'epo2000'      => 'nullable|string|max:50',
            'epo4000'      => 'nullable|string|max:50',
            'hierro'       => 'nullable|string|max:50',
            'vitamina_b12' => 'nullable|string|max:50',
            'calcitriol'   => 'nullable|string|max:50',
            'heparina'     => 'nullable|string|max:50',
        ]);

        $previousMedications = $medical->only($this->nursingMedicationFields());
        $medical->update($medications);
        $this->syncMedicationToNurse($medical, $previousMedications);

        return back()->with('success', 'Medicamentos de la atención actualizados correctamente.');
    }

    /**
     * Actualiza en bloque la medicación de los pacientes seleccionados.
     */
    public function bulkUpdateMedications(Request $request)
    {
        $validated = $request->validate([
            'medicals' => ['required', 'array', 'min:1'],
            'medicals.*' => ['required', 'array'],
            'medicals.*.epo2000' => ['nullable', 'string', 'max:50'],
            'medicals.*.epo4000' => ['nullable', 'string', 'max:50'],
            'medicals.*.hierro' => ['nullable', 'string', 'max:50'],
            'medicals.*.vitamina_b12' => ['nullable', 'string', 'max:50'],
            'medicals.*.calcitriol' => ['nullable', 'string', 'max:50'],
            'medicals.*.heparina' => ['nullable', 'string', 'max:50'],
        ]);

        $medicalIds = array_keys($validated['medicals']);
        if (collect($medicalIds)->contains(fn ($id) => ! ctype_digit((string) $id))) {
            throw ValidationException::withMessages([
                'medicals' => 'La selección de pacientes no es válida.',
            ]);
        }

        $medicals = Medical::query()
            ->with('order')
            ->whereKey($medicalIds)
            ->when(CurrentSede::id(), function ($query) {
                $query->whereHas('order', fn ($order) => $order->where('sede_id', CurrentSede::id()));
            })
            ->get()
            ->keyBy(fn (Medical $medical) => (string) $medical->getKey());

        if ($medicals->count() !== count($medicalIds)) {
            throw ValidationException::withMessages([
                'medicals' => 'Uno o más pacientes seleccionados no pertenecen a la sede activa.',
            ]);
        }

        DB::transaction(function () use ($validated, $medicals) {
            foreach ($validated['medicals'] as $medicalId => $medications) {
                $medical = $medicals->get((string) $medicalId);
                $previousMedications = $medical->only($this->nursingMedicationFields());
                $medical->update($medications);
                $this->syncMedicationToNurse($medical, $previousMedications);
            }
        });

        return back()->with('success', 'Medicamentos guardados para '.count($medicalIds).' pacientes.');
    }

    public function show(Medical $medical)
    {
        // Cargamos la orden y el paciente para obtener sala, turno y fecha
        $medical->load(['order.patient']);

        if (request()->ajax()) {
            $html = '
            <div class="row g-3">
                <div class="col-md-6"><strong>Fecha:</strong><br> ' . \Carbon\Carbon::parse($medical->created_at)->format('d/m/Y') . '</div>
                <div class="col-md-3"><strong>Sala:</strong><br> ' . ($medical->order->sala ?? '---') . '</div>
                <div class="col-md-3"><strong>Turno:</strong><br> ' . ($medical->order->turno ?? '---') . '</div>
                
                <div class="col-12"><hr class="my-1"></div>

                <div class="col-md-3"><strong>Peso Inicial:</strong><br> ' . ($medical->peso_inicial ?: '0') . ' kg</div>
                <div class="col-md-3"><strong>Peso Seco:</strong><br> ' . ($medical->peso_seco ?: '0') . ' kg</div>
                <div class="col-md-3"><strong>UF:</strong><br> <span class="text-primary fw-bold">' . ($medical->uf ?: '---') . '</span></div>
                <div class="col-md-3"><strong>Hora HD:</strong><br> ' . ($medical->hora_hd ?: '---') . ' hrs</div>

                <div class="col-12"><hr class="my-1"></div>
                
                <div class="col-12">
                    <label class="fw-bold text-success small">MEDICAMENTOS APLICADOS:</label>
                    <div class="p-2 border rounded bg-light">
                        <div class="row">
                            <div class="col-md-4"><strong>EPO 2000:</strong> ' . ($medical->epo2000 ?: '0') . '</div>
                            <div class="col-md-4"><strong>EPO 4000:</strong> ' . ($medical->epo4000 ?: '0') . '</div>
                            <div class="col-md-4"><strong>Hierro:</strong> ' . ($medical->hierro ?: '0') . '</div>
                            <div class="col-md-4"><strong>Vit. B12:</strong> ' . ($medical->vitamina_b12 ?: '0') . '</div>
                            <div class="col-md-4"><strong>Calcitriol:</strong> ' . ($medical->calcitriol ?: '0') . '</div>
                        </div>
                    </div>
                </div>
            </div>';

            return response($html);
        }

        return redirect()->route('medicals.index');
    }

    /**
     * Mantiene en enfermería los cambios de una prescripción que todavía no fue
     * modificada por el personal de enfermería.
     *
     * Además de los valores vacíos, se reemplaza el valor que coincide con la
     * prescripción médica anterior. Así, una corrección de 3 a 1 no deja el 3
     * copiado previamente en la ficha de enfermería. Un valor distinto se
     * considera una cantidad administrada editada por enfermería y se conserva.
     */
    private function syncMedicationToNurse(Medical $medical, array $previousMedications): void
    {
        $nurse = Nurse::where('order_id', $medical->order_id)->first();
        if (!$nurse) {
            return;
        }

        $changes = [];

        foreach ($this->nursingMedicationFields() as $field) {
            $nurseValue = (string) ($nurse->$field ?? '');
            $medicalValue = (string) ($medical->$field ?? '');
            $previousMedicalValue = (string) ($previousMedications[$field] ?? '');

            $nurseHasDefaultValue = in_array($nurseValue, ['', '0'], true);
            $nurseStillHasPreviousPrescription = $nurseValue === $previousMedicalValue;

            if ($nurseHasDefaultValue || $nurseStillHasPreviousPrescription) {
                $changes[$field] = $medical->$field;
            }
        }

        if (!empty($changes)) {
            $nurse->update($changes);
        }
    }

    private function nursingMedicationFields(): array
    {
        return ['epo2000', 'epo4000', 'hierro', 'vitamina_b12', 'calcitriol'];
    }
}
