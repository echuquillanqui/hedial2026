<?php

namespace App\Http\Controllers;

use App\Models\Fua;
use App\Models\FuaCorrection;
use App\Models\FuaConfiguration;
use App\Models\NephrologyConsultation;
use App\Models\Test;
use App\Models\User;
use App\Models\Order;
use App\Models\Patient;
use App\Services\FuaNumberService;
use App\Support\ClinicalService;
use App\Support\CurrentSede;
use App\Support\DailyHemodialysisSequence;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FuaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:fua.view')->only(['index', 'hemodialysisIndex', 'nephrologyIndex']);
        $this->middleware('permission:fua.generate')->only(['bulkPdf', 'nephrologyBulkPdf']);
        $this->middleware('permission:fua.responsible.update')->only(['updateResponsible', 'bulkUpdateResponsible']);
        $this->middleware('permission:fua.correction.create')->only('storeCorrection');
    }

    public function hemodialysisIndex(Request $request)
    {
        return $this->printIndex($request, Fua::HEMODIALYSIS);
    }

    public function nephrologyIndex(Request $request)
    {
        return $this->printIndex($request, Fua::NEPHROLOGY);
    }

    public function multisectorialIndex(Request $request)
    {
        $type = $this->validatedMultisectorialType($request);
        $this->authorizeType($request, $type, 'view');

        return $this->printIndex($request, $type);
    }

    private function printIndex(Request $request, string $type)
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'patient' => ['nullable', 'string', 'max:100'],
            'modulo' => ['nullable', Rule::in(Patient::MODULES)],
            'turno' => ['nullable', 'integer', 'between:1,4'],
            'sequence' => ['nullable', Rule::in([
                DailyHemodialysisSequence::MONDAY_WEDNESDAY_FRIDAY,
                DailyHemodialysisSequence::TUESDAY_THURSDAY_SATURDAY,
            ])],
            'all_dates' => ['nullable', 'boolean'],
            'professional_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'max:30'],
            'prescription_status' => ['nullable', Rule::in(['with_prescription', 'without_prescription'])],
            'sede_id' => ['nullable', 'integer', 'exists:sedes,id'],
        ]);

        $date = $request->boolean('all_dates') ? null : ($filters['date'] ?? now()->toDateString());
        $usesDialysisSchedule = $type === Fua::HEMODIALYSIS || ClinicalService::isMultisectorial($type);
        $sequence = $usesDialysisSchedule
            ? ($request->has('sequence')
                ? ($filters['sequence'] ?? null)
                : ($date ? DailyHemodialysisSequence::forDate($date) : null))
            : null;
        if (CurrentSede::id() && isset($filters['sede_id']) && (int) $filters['sede_id'] !== (int) CurrentSede::id()) {
            abort(403, 'La sede del filtro no coincide con la sede activa.');
        }
        $sedeId = CurrentSede::id() ?: ($filters['sede_id'] ?? null);
        if ($sedeId) {
            abort_unless($request->user()->sedes()->whereKey($sedeId)->exists(), 403);
        }
        $query = $this->printQuery(
            $type,
            $date,
            $filters['patient'] ?? null,
            $filters['modulo'] ?? null,
            $filters['turno'] ?? null,
            $sequence,
            $filters['professional_id'] ?? null,
            $filters['status'] ?? null,
            $filters['prescription_status'] ?? null,
            $sedeId,
        );
        $dailyFuaIds = $type === Fua::HEMODIALYSIS && $date
            ? (clone $query)->pluck('fuas.id')->map(fn ($id) => (int) $id)->values()
            : collect();
        $fuas = $query
            ->orderBy('patients.surname')
            ->orderBy('patients.last_name')
            ->orderBy('patients.first_name')
            ->orderBy('patients.other_names')
            ->orderBy('orders.turno')
            ->orderBy('patients.modulo')
            ->orderByDesc('orders.fecha_orden')
            ->orderBy('fuas.id')
            ->select('fuas.*')
            ->paginate(30)
            ->withQueryString();

        $professionalIds = $type === Fua::NEPHROLOGY
            ? NephrologyConsultation::query()
                ->whereNotNull('doctor_id')
                ->whereHas('order', fn (Builder $order) => $order
                    ->where('attention_type', $type)
                    ->when($sedeId, fn (Builder $order) => $order->where('sede_id', $sedeId)))
                ->select('doctor_id')
            : Order::query()
                ->where('attention_type', $type)
                ->whereNotNull('assigned_professional_id')
                ->when($sedeId, fn (Builder $order) => $order->where('sede_id', $sedeId))
                ->select('assigned_professional_id');

        return view('fuas.print-index', [
            'fuas' => $fuas,
            'date' => $date,
            'sequence' => $sequence,
            'type' => $type,
            'professionals' => User::query()->whereIn('id', $professionalIds)->orderBy('name')->get(),
            'doctors' => $type === Fua::HEMODIALYSIS
                ? User::query()->medicalProfessionals()->orderBy('name')->get()
                : collect(),
            'dailyFuaIds' => $dailyFuaIds,
            'sedes' => $request->user()->sedes()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function bulkPdf(Request $request)
    {
        return $this->bulkPdfForType($request, Fua::HEMODIALYSIS);
    }

    public function nephrologyBulkPdf(Request $request)
    {
        return $this->bulkPdfForType($request, Fua::NEPHROLOGY);
    }

    public function multisectorialBulkPdf(Request $request)
    {
        $type = $this->validatedMultisectorialType($request);
        $this->authorizeType($request, $type, 'generate');

        return $this->bulkPdfForType($request, $type);
    }

    public function generateForOrder(Request $request, Order $order, FuaNumberService $numbers)
    {
        abort_unless(ClinicalService::isMultisectorial($order->attention_type), 422);
        $this->authorizeOrderSede($order);
        $this->authorizeType($request, $order->attention_type, 'generate');

        if ($order->fua()->where('type', $order->attention_type)->exists()) {
            throw ValidationException::withMessages(['order' => 'La orden ya tiene una FUA ordinaria.']);
        }

        $fua = $numbers->createForOrder($order);

        return redirect()->route('fuas.pdf', $fua)->with('success', 'FUA generada correctamente.');
    }

    public function bulkGenerate(Request $request, FuaNumberService $numbers)
    {
        $data = $request->validate([
            'orders' => ['required', 'array', 'min:1'],
            'orders.*' => ['integer', 'distinct', 'exists:orders,id'],
        ]);

        $created = DB::transaction(function () use ($data, $request, $numbers) {
            return Order::query()->whereIn('id', $data['orders'])->orderBy('fecha_orden')->orderBy('id')
                ->lockForUpdate()->get()->map(function (Order $order) use ($request, $numbers) {
                    abort_unless(ClinicalService::isMultisectorial($order->attention_type), 422);
                    $this->authorizeOrderSede($order);
                    $this->authorizeType($request, $order->attention_type, 'generate');

                    return $order->fua()->where('type', $order->attention_type)->first()
                        ?: $numbers->createForOrder($order);
                });
        });

        return back()->with('success', $created->count().' FUA procesadas correctamente.');
    }

    public function storeCorrection(Request $request, Fua $fua, FuaNumberService $numbers)
    {
        abort_if($fua->type === Fua::CORRECTION, 422, 'Una subsanación no puede subsanar otra subsanación.');
        abort_unless(in_array($fua->type, ClinicalService::ORDINARY_TYPES, true), 422);
        $this->authorizeOrderSede($fua->order);

        if ($fua->corrections()->exists()) {
            throw ValidationException::withMessages(['fua' => 'La FUA ya tiene una subsanación registrada.']);
        }

        $correction = $numbers->createCorrection($fua);

        return redirect()->route('fuas.corrections.pdf', $correction)->with('success', 'FUA de subsanación generada correctamente.');
    }

    public function correctionIndex(Request $request)
    {
        abort_unless($request->user()->can('fua.view') && $request->user()->can('fua.correction.create'), 403);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'generated_from' => ['nullable', 'date'], 'generated_to' => ['nullable', 'date', 'after_or_equal:generated_from'],
        ]);
        $corrections = $this->correctionListQuery($filters)
            ->with(['order.patient', 'originalFua'])
            ->latest('id')->paginate(20)->withQueryString();

        return view('fuas.corrections-index', compact('corrections'));
    }

    public function correctionCreate(Request $request)
    {
        abort_unless($request->user()->can('fua.view') && $request->user()->can('fua.correction.create'), 403);
        return view('fuas.corrections-create');
    }

    public function correctionSearch(Request $request)
    {
        abort_unless($request->user()->can('fua.view') && $request->user()->can('fua.correction.create'), 403);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'date' => ['nullable', 'date'],
            'type' => ['nullable', Rule::in(ClinicalService::ORDINARY_TYPES)],
        ]);
        $term = trim($filters['search'] ?? '');
        $numberParts = array_values(array_filter(
            preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [],
            fn (string $part) => preg_match('/\d/', $part) === 1,
        ));
        $fuas = Fua::query()->with(['order.patient', 'order.sede', 'corrections'])
            ->whereIn('type', ClinicalService::ORDINARY_TYPES)
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when(CurrentSede::id(), fn (Builder $query, int $sede) => $query->whereHas('order', fn (Builder $order) => $order->where('sede_id', $sede)))
            ->when($filters['date'] ?? null, fn (Builder $query, string $date) => $query->whereHas('order', fn (Builder $order) => $order->whereDate('fecha_orden', $date)))
            ->when($term !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($term, $numberParts) {
                if ($numberParts) {
                    $query->where(function (Builder $numbers) use ($numberParts) {
                        foreach ($numberParts as $part) {
                            $digits = preg_replace('/\D+/', '', $part);
                            $digits = ltrim($digits, '0') ?: '0';
                            $numbers->orWhere('number', 'like', "%{$part}%")->orWhere('number', 'like', "%{$digits}%");
                        }
                    });
                }
                $query->orWhereHas('order.patient', fn (Builder $patient) => $patient->where('dni', 'like', "%{$term}%")
                        ->orWhere('surname', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")->orWhere('other_names', 'like', "%{$term}%"));
            }))
            ->latest('id')->paginate(20)->withQueryString();
        return view('fuas.corrections-create', compact('fuas'));
    }

    public function correctionBulkPdf(Request $request)
    {
        abort_unless($request->user()->can('fua.view') && $request->user()->can('fua.correction.create'), 403);
        $data = $request->validate([
            'scope' => ['nullable', Rule::in(['selected', 'current_page', 'all_filtered'])],
            'corrections' => ['nullable', 'array', 'min:1'], 'corrections.*' => ['integer', 'distinct'],
            'search' => ['nullable', 'string', 'max:120'],
            'generated_from' => ['nullable', 'date'], 'generated_to' => ['nullable', 'date', 'after_or_equal:generated_from'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $scope = $data['scope'] ?? 'selected';
        if ($scope === 'selected' && empty($data['corrections'])) {
            throw ValidationException::withMessages(['corrections' => 'Selecciona al menos una subsanación para imprimir.']);
        }

        $query = $this->correctionListQuery($data);
        if ($scope === 'selected') {
            $query->whereIn('fua_corrections.id', $data['corrections']);
        } elseif ($scope === 'current_page') {
            $query->latest('fua_corrections.id')->forPage((int) ($data['page'] ?? 1), 20);
        }
        $correctionIds = $query->pluck('fua_corrections.id');
        abort_if($correctionIds->isEmpty(), 404, 'No hay subsanaciones para imprimir con estos filtros.');

        $corrections = FuaCorrection::query()->whereIn('id', $correctionIds)
            ->with(['order.patient', 'order.sede', 'originalFua.order.patient', 'originalFua.order.sede', 'originalFua.order.medical.usuarioInicia', 'originalFua.order.nurse', 'originalFua.order.laboratoryOrder.items.test', 'originalFua.order.nephrologyConsultation.doctor', 'originalFua.order.nephrologyConsultation.medications', 'originalFua.order.nutritionAssessment', 'originalFua.responsibleUser'])
            ->orderBy('created_at')->orderBy('id')->get();

        $documents = $corrections->map(function (FuaCorrection $correction) {
            $original = $correction->originalFua;
            abort_unless($original, 422);
            return [
                'fua' => $original, 'correctionFua' => $correction,
                'correctionOptions' => $this->correctionOptions($correction),
                'responsible' => $this->responsible($original), 'medications' => $this->medications($original),
                'procedures' => $this->procedures($original), 'diagnoses' => $this->diagnoses($original),
            ];
        });
        return Pdf::loadView('fuas.pdf', [
            'documents' => $documents, 'configuration' => FuaConfiguration::global(), 'logoData' => $this->fuaLogoData(),
        ])->setPaper('a4')->stream('subsanaciones-bloque.pdf');
    }

    private function correctionListQuery(array $filters): Builder
    {
        $term = trim($filters['search'] ?? '');

        return FuaCorrection::query()
            ->when(CurrentSede::id(), fn (Builder $query, int $sede) => $query->whereHas('order', fn (Builder $order) => $order->where('sede_id', $sede)))
            ->when($filters['generated_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['generated_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when($term !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($term) {
                $query->where('number', 'like', "%{$term}%")
                    ->orWhereHas('originalFua', fn (Builder $original) => $original->where('number', 'like', "%{$term}%"))
                    ->orWhereHas('order.patient', fn (Builder $patient) => $patient->where('dni', 'like', "%{$term}%")
                        ->orWhere('surname', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")->orWhere('other_names', 'like', "%{$term}%"));
            }));
    }

    public function generateCorrection(Request $request, Fua $fua, FuaNumberService $numbers)
    {
        abort_unless($request->user()->can('fua.view') && $request->user()->can('fua.correction.create'), 403);
        abort_if($fua->type === Fua::CORRECTION || ! in_array($fua->type, ClinicalService::ORDINARY_TYPES, true), 422);
        $this->authorizeOrderSede($fua->order);
        if ($fua->corrections()->exists()) {
            throw ValidationException::withMessages(['fua' => 'La FUA ya tiene una subsanación registrada.']);
        }
        $data = $request->validate([
            'reason' => ['required', Rule::in(['FALTA MEDICAMENTOS', 'FALTA DATOS DEL MEDICO', 'FALTA PROCEDIMIENTO', 'OTRO'])],
            'show_doctor' => ['nullable', 'boolean'], 'show_stamp' => ['nullable', 'boolean'],
            'show_medications' => ['nullable', 'boolean'], 'show_hemodialysis' => ['nullable', 'boolean'],
            'show_laboratories' => ['nullable', 'boolean'], 'show_diagnoses' => ['nullable', 'boolean'],
            'observations' => ['nullable', 'string', 'max:500'],
            'observation_preset' => ['nullable', Rule::in(['PACIENTE NO INDICA MOTIVO DE NO FIRMA'])],
        ]);
        $correction = $numbers->createCorrection($fua);
        $correction->update([
            'reason' => $data['reason'],
            'observation_preset' => $data['observation_preset'] ?? null,
            'observations' => $data['observations'] ?? '',
            'show_doctor' => $request->boolean('show_doctor'), 'show_stamp' => $request->boolean('show_stamp'),
            'show_medications' => $request->boolean('show_medications'),
            'show_hemodialysis' => $request->boolean('show_hemodialysis'), 'show_laboratories' => $request->boolean('show_laboratories'),
            'show_diagnoses' => $request->boolean('show_diagnoses'),
        ]);
        return redirect()->route('fuas.corrections.pdf', $correction);
    }

    public function correctionUpdate(Request $request, FuaCorrection $correction)
    {
        abort_unless($request->user()->can('fua.view') && $request->user()->can('fua.correction.create'), 403);
        $this->authorizeOrderSede($correction->order);
        $data = $request->validate([
            'reason' => ['required', Rule::in(['FALTA MEDICAMENTOS', 'FALTA DATOS DEL MEDICO', 'FALTA PROCEDIMIENTO', 'OTRO'])],
            'observation_preset' => ['nullable', Rule::in(['PACIENTE NO INDICA MOTIVO DE NO FIRMA'])],
            'observations' => ['nullable', 'string', 'max:500'],
            'show_doctor' => ['nullable', 'boolean'], 'show_stamp' => ['nullable', 'boolean'],
            'show_medications' => ['nullable', 'boolean'], 'show_hemodialysis' => ['nullable', 'boolean'],
            'show_laboratories' => ['nullable', 'boolean'],
            'show_diagnoses' => ['nullable', 'boolean'],
        ]);
        foreach (['show_doctor', 'show_stamp', 'show_medications', 'show_hemodialysis', 'show_laboratories', 'show_diagnoses'] as $field) {
            $data[$field] = $request->boolean($field);
        }
        $correction->update($data);
        return redirect()->route('fuas.corrections.index')->with('success', 'La subsanación fue actualizada.');
    }

    public function correctionPdf(Request $request, FuaCorrection $correction)
    {
        abort_unless($request->user()->can('fua.view') && $request->user()->can('fua.correction.create'), 403);
        $this->authorizeOrderSede($correction->order);
        $correction->load(['order.patient', 'order.sede', 'originalFua']);
        $original = $correction->originalFua;
        abort_unless($original, 404);
        $original->load($this->pdfRelations());
        $options = $this->correctionOptions($correction);
        $view = $original->effectiveType() === Fua::NEPHROLOGY ? 'fuas.pdf_nephrology' : 'fuas.pdf';
        return Pdf::loadView($view, [
            'fua' => $original, 'correctionFua' => $correction, 'correctionOptions' => $options,
            'configuration' => FuaConfiguration::global(), 'logoData' => $this->fuaLogoData(),
            'medications' => $this->medications($original), 'responsible' => $this->responsible($original),
            'procedures' => $this->procedures($original), 'diagnoses' => $this->diagnoses($original),
        ])->setPaper('a4')->stream('subsanacion-'.$correction->number.'.pdf');
    }

    private function correctionOptions(FuaCorrection $correction): array
    {
        return [
            'reason' => $correction->reason, 'show_doctor' => $correction->show_doctor,
            'show_stamp' => $correction->show_stamp, 'show_medications' => $correction->show_medications,
            'show_hemodialysis' => $correction->show_hemodialysis, 'show_laboratories' => $correction->show_laboratories,
            'show_diagnoses' => $correction->show_diagnoses,
            'observation_preset' => $correction->observation_preset ?? '',
            'observations' => trim(($correction->observation_preset ?? '').' '.($correction->observations ?? '')),
        ];
    }

    private function bulkPdfForType(Request $request, string $type)
    {
        $data = $request->validate([
            'fuas' => ['required', 'array', 'min:1'],
            'fuas.*' => ['integer', 'distinct'],
        ]);

        $fuas = Fua::query()
            ->join('orders', 'orders.id', '=', 'fuas.order_id')
            ->join('patients', 'patients.id', '=', 'orders.patient_id')
            ->where('fuas.type', $type)
            ->whereIn('fuas.id', $data['fuas'])
            ->when(CurrentSede::id(), fn (Builder $query, int $sede) => $query
                ->whereHas('order', fn (Builder $order) => $order->where('sede_id', $sede)))
            ->with($this->pdfRelations())
            ->orderBy('patients.surname')
            ->orderBy('patients.last_name')
            ->orderBy('patients.first_name')
            ->orderBy('patients.other_names')
            ->orderBy('orders.turno')
            ->orderBy('patients.modulo')
            ->orderBy('fuas.id')
            ->select('fuas.*')
            ->get()
            ->values();

        abort_if($fuas->isEmpty(), 404);

        $configuration = FuaConfiguration::global();
        $documents = $fuas->map(fn (Fua $fua) => [
            'fua' => $fua,
            'responsible' => $this->responsible($fua),
            'medications' => $this->medications($fua),
            'procedures' => $this->procedures($fua),
            'diagnoses' => $this->diagnoses($fua),
        ]);

        $view = $type === Fua::NEPHROLOGY ? 'fuas.pdf_nephrology' : 'fuas.pdf';

        return Pdf::loadView($view, [
            'documents' => $documents,
            'configuration' => $configuration,
            'logoData' => $this->fuaLogoData(),
        ])->setPaper('a4')->stream('fuas-'.strtolower($type).'.pdf');
    }

    private function printQuery(
        string $type,
        ?string $date,
        ?string $patient,
        ?string $module,
        ?int $shift,
        ?string $sequence,
        ?int $professional,
        ?string $status,
        ?string $prescriptionStatus,
        ?int $sede,
    ): Builder
    {
        return Fua::query()
            ->with([
                'order.patient',
                'order.sede',
                'order.nephrologyConsultation' => fn ($query) => $query->withExists('medications'),
            ])
            ->join('orders', 'orders.id', '=', 'fuas.order_id')
            ->join('patients', 'patients.id', '=', 'orders.patient_id')
            ->where('fuas.type', $type)
            ->when($sede, fn (Builder $query) => $query->where('orders.sede_id', $sede))
            ->when($professional, function (Builder $query, int $professional) use ($type) {
                $type === Fua::NEPHROLOGY
                    ? $query->whereHas('order.nephrologyConsultation', fn (Builder $consultation) => $consultation
                        ->where('doctor_id', $professional))
                    : $query->where('orders.assigned_professional_id', $professional);
            })
            ->when($status, fn (Builder $query) => $query->where('fuas.status', $status))
            ->when($type === Fua::NEPHROLOGY && $prescriptionStatus, function (Builder $query) use ($prescriptionStatus) {
                $relation = 'order.nephrologyConsultation.medications';

                $prescriptionStatus === 'with_prescription'
                    ? $query->whereHas($relation)
                    : $query->whereDoesntHave($relation);
            })
            ->when($date, fn (Builder $query) => $query->whereDate('orders.fecha_orden', $date))
            ->when($shift, fn (Builder $query) => $query->where('orders.turno', (string) $shift))
            ->when($sequence, fn (Builder $query) => $query->whereHas('order.patient', fn (Builder $patientQuery) => $patientQuery
                ->scheduledForSequence($sequence)))
            ->when($module, function (Builder $query, string $module) use ($type) {
                if ($type !== Fua::HEMODIALYSIS) {
                    $query->whereHas('order.patient', fn (Builder $patientQuery) => $patientQuery
                        ->where('modulo', (string) $module));

                    return;
                }

                $query->where('orders.sala', 'MODULO '.$module);
            })
            ->when($patient, function (Builder $query, string $patient) {
                $query->whereHas('order.patient', fn (Builder $query) => $query
                    ->where('dni', 'like', "%{$patient}%")
                    ->orWhere('surname', 'like', "%{$patient}%")
                    ->orWhere('last_name', 'like', "%{$patient}%")
                    ->orWhere('first_name', 'like', "%{$patient}%")
                    ->orWhere('other_names', 'like', "%{$patient}%"));
            });
    }

    public function index(Request $request)
    {
        $fuas = Fua::with(['order.patient', 'order.sede', 'corrections'])
            ->whereIn('type', ClinicalService::ORDINARY_TYPES)
            ->when(CurrentSede::id(), fn (Builder $query, int $sede) => $query
                ->whereHas('order', fn (Builder $order) => $order->where('sede_id', $sede)))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($query) use ($search) {
                    $query->where('number', 'like', "%{$search}%")
                        ->orWhereHas('order.patient', fn ($patient) => $patient
                            ->where('dni', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('fuas.index', compact('fuas'));
    }

    public function preview(Fua $fua)
    {
        $this->authorizeFuaView(request(), $fua);
        $fua->load(['order.patient', 'order.sede', 'order.medical.usuarioInicia', 'responsibleUser']);
        $doctors = User::query()
            ->where(function ($query) {
                $query->whereHas('roles', fn ($roles) => $roles->where('name', 'medico'))
                    ->orWhere('profession', 'like', '%medic%')
                    ->orWhere('profession', 'like', '%médic%')
                    ->orWhere('profession', 'like', '%nefro%');
            })
            ->orderBy('name')
            ->get();

        return view('fuas.preview', compact('fua', 'doctors'));
    }

    public function updateResponsible(Request $request, Fua $fua)
    {
        $data = $request->validate([
            'responsible_user_id' => ['nullable', 'exists:users,id'],
        ]);

        if (! empty($data['responsible_user_id'])) {
            $doctor = User::findOrFail($data['responsible_user_id']);
            $profession = mb_strtolower($doctor->profession ?? '');
            abort_unless($doctor->hasRole('medico') || str_contains($profession, 'medic') || str_contains($profession, 'médic') || str_contains($profession, 'nefro'), 422);
        }

        $fua->update($data);

        return back()->with('success', 'Médico responsable actualizado en la FUA.');
    }

    public function bulkUpdateResponsible(Request $request)
    {
        $data = $request->validate([
            'fuas' => ['required', 'array', 'min:1'],
            'fuas.*' => ['integer', 'distinct', 'exists:fuas,id'],
            'responsible_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $doctor = User::findOrFail($data['responsible_user_id']);
        abort_unless($doctor->isMedicalProfessional(), 422, 'El responsable seleccionado debe ser un médico.');

        $query = Fua::query()
            ->where('type', Fua::HEMODIALYSIS)
            ->whereIn('id', $data['fuas'])
            ->when(CurrentSede::id(), fn (Builder $query, int $sede) => $query
                ->whereHas('order', fn (Builder $order) => $order->where('sede_id', $sede)));

        abort_unless($query->count() === count($data['fuas']), 403, 'Una o más FUA no pertenecen a la sede activa o no son de hemodiálisis.');
        $updated = $query->update(['responsible_user_id' => $doctor->id]);

        return back()->with('success', "Médico firmante actualizado en {$updated} FUA de hemodiálisis.");
    }

    public function pdf(Request $request, Fua $fua)
    {
        $this->authorizeFuaView($request, $fua);
        $fua->load($this->pdfRelations());
        $responsible = $this->responsible($fua);
        $medications = $this->medications($fua);
        $procedures = $this->procedures($fua);
        $configuration = FuaConfiguration::global();
        $view = $fua->effectiveType() === Fua::NEPHROLOGY ? 'fuas.pdf_nephrology' : 'fuas.pdf';
        $logoData = $this->fuaLogoData();
        $document = Pdf::loadView($view, [
            'fua' => $fua,
            'configuration' => $configuration,
            'logoData' => $logoData,
            'medications' => $medications,
            'responsible' => $responsible,
            'procedures' => $procedures,
            'diagnoses' => $this->diagnoses($fua),
        ])->setPaper('a4');
        $filename = 'fua-'.str_replace(['/', '\\'], '-', $fua->number).'.pdf';

        return $request->boolean('download')
            ? $document->download($filename)
            : $document->stream($filename);
    }

    private function pdfRelations(): array
    {
        return [
            'order.patient', 'order.sede', 'order.medical.usuarioInicia', 'order.nurse',
            'order.laboratoryOrder.items.test', 'order.nephrologyConsultation.doctor',
            'order.nephrologyConsultation.medications',
            'order.nutritionAssessment',
            'responsibleUser', 'generatedBy', 'correctedFua.order.assignedProfessional',
        ];
    }

    private function medications(Fua $fua): array
    {
        if ($fua->effectiveType() === Fua::NEPHROLOGY) {
            return $fua->order?->nephrologyConsultation?->medications
                ?->map(fn ($medication) => [
                    'code' => $medication->fua_code,
                    'description' => $medication->description,
                    'prescribed_quantity' => $medication->prescribed_quantity,
                    'delivered_quantity' => $medication->delivered_quantity,
                ])->all() ?? [];
        }

        $medical = $fua->order?->medical;
        $nurse = $fua->order?->nurse;
        $catalog = [
            ['code' => '3107', 'description' => 'Epoetina alfa (eritropoyetina) 2 000 UI/ml, inyectable', 'field' => 'epo2000'],
            ['code' => '3113', 'description' => 'Epoetina alfa (eritropoyetina) 4 000 UI/ml, inyectable', 'field' => 'epo4000'],
            ['code' => '3979', 'description' => 'Vitamina B12 (hidroxicobalamina) 1 mg/ml, inyectable', 'field' => 'vitamina_b12'],
            ['code' => '19238', 'description' => 'Hierro (como sacarato) 20 mg Fe/ml, inyectable', 'field' => 'hierro'],
            ['code' => '01502', 'description' => 'Calcitriol 1 mcg/ml, inyectable', 'field' => 'calcitriol'],
        ];

        $rows = collect($catalog)->map(function (array $item) use ($medical, $nurse) {
            $prescribed = $medical?->{$item['field']};
            $delivered = $nurse?->{$item['field']};
            $quantity = is_numeric($prescribed) && (float) $prescribed > 0 ? $prescribed : $delivered;

            return [
                'code' => $item['code'], 'description' => $item['description'],
                'prescribed_quantity' => $quantity,
                'delivered_quantity' => is_numeric($delivered) ? $delivered : $quantity,
            ];
        })->filter(fn (array $medication) => is_numeric($medication['prescribed_quantity']) && (float) $medication['prescribed_quantity'] > 0)
            ->values();

        $otherMedications = trim((string) ($nurse?->otros_medicamentos ?? ''));
        if ($otherMedications !== '') {
            $rows->push(['code' => '', 'description' => $otherMedications, 'prescribed_quantity' => '', 'delivered_quantity' => '']);
        }

        return $rows->all();
    }

    private function diagnoses(Fua $fua): array
    {
        if ($fua->effectiveType() === Fua::NUTRITION) {
            return collect($fua->order?->nutritionAssessment?->diagnoses ?? [])
                ->filter(fn ($diagnosis) => ! empty($diagnosis['codigo']) && ! empty($diagnosis['descripcion']))
                ->take(6)
                ->values()
                ->all();
        }

        return [];
    }

    private function fuaLogoData(): ?string
    {
        $absolutePath = public_path('logo/logo-fissal.png');

        if (! is_file($absolutePath)) {
            return null;
        }

        $mime = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($absolutePath));
    }

    private function procedures(Fua $fua): array
    {
        $type = $fua->effectiveType();
        if ($type === Fua::NEPHROLOGY) {
            return [[
                'code' => ClinicalService::cpms(ClinicalService::NEPHROLOGY),
                'description' => 'Consulta ambulatoria especializada para la evaluación y manejo de un paciente continuador',
                'quantity' => 1,
            ]];
        }

        if (ClinicalService::isMultisectorial($type)) {
            return [[
                'code' => ClinicalService::cpms($type),
                'description' => ClinicalService::label($type),
                'quantity' => 1,
            ]];
        }

        $rows = [['code' => ClinicalService::cpms(ClinicalService::HEMODIALYSIS), 'description' => 'HEMODIÁLISIS (2DA. SESIÓN)', 'quantity' => 1]];
        $frequencies = match ($fua->order?->laboratory_period) {
            'M' => ['M'],
            'B' => ['M', 'B'],
            'T' => ['M', 'B', 'T'],
            'S' => ['M', 'B', 'T', 'S'],
            default => [],
        };
        $tests = Test::query()->where('is_fissal', true)->whereIn('frequency', $frequencies)->get();
        $urea = $tests->filter(fn (Test $test) => str_contains(mb_strtolower($test->name), 'urea'));

        if ($urea->isNotEmpty()) {
            $rows[] = ['code' => '84520', 'description' => 'Nitrógeno ureico; cuantitativo (Urea sérica)', 'quantity' => $urea->sum(fn (Test $test) => $test->fua_quantity ?? 1)];
        }

        $electrolyteNames = ['sodio', 'potasio', 'cloro'];
        $electrolytes = $tests->filter(fn (Test $test) => in_array(mb_strtolower(trim($test->name)), $electrolyteNames, true));

        if ($electrolytes->isNotEmpty()) {
            $rows[] = [
                'code' => $electrolytes->first()->code ?? '',
                'description' => 'Perfil de electrolitos (sodio, potasio y cloro)',
                'quantity' => 1,
            ];
        }

        foreach ($tests->reject(function (Test $test) use ($electrolyteNames) {
            $name = mb_strtolower(trim($test->name));

            return str_contains($name, 'urea') || in_array($name, $electrolyteNames, true);
        }) as $test) {
            $rows[] = [
                'code' => $test->code ?? '',
                'description' => $test->name,
                'quantity' => $test->fua_quantity ?? 1,
            ];
        }

        return $rows;
    }

    private function validatedMultisectorialType(Request $request): string
    {
        return validator($request->only('type'), [
            'type' => ['required', Rule::in(ClinicalService::MULTISECTORIAL_TYPES)],
        ])->validate()['type'];
    }

    private function authorizeType(Request $request, string $type, string $action): void
    {
        $permission = ClinicalService::permissionPrefix($type).'.fua.'.$action;
        abort_unless($request->user()->can($permission) || $request->user()->can('fua.'.($action === 'view' ? 'view' : 'generate')), 403);
    }

    private function authorizeFuaView(Request $request, Fua $fua): void
    {
        $fua->loadMissing('correctedFua');
        $type = $fua->effectiveType();
        if (ClinicalService::isMultisectorial($type)) {
            $this->authorizeType($request, $type, 'view');
        } else {
            abort_unless($request->user()->can('fua.view'), 403);
        }
        $this->authorizeOrderSede($fua->order);
    }

    private function authorizeOrderSede(?Order $order): void
    {
        abort_unless($order, 422, 'La FUA debe estar relacionada con una orden.');
        abort_if(CurrentSede::id() && (int) $order->sede_id !== (int) CurrentSede::id(), 403, 'Orden fuera de la sede activa.');
    }

    private function responsible(Fua $fua): ?User
    {
        return $fua->responsibleUser
            ?: $fua->order?->nephrologyConsultation?->doctor
            ?: $fua->order?->assignedProfessional
            ?: $fua->order?->medical?->usuarioInicia;
    }
}
