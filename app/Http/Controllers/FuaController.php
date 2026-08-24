<?php

namespace App\Http\Controllers;

use App\Models\Fua;
use App\Models\FuaConfiguration;
use App\Models\Test;
use App\Models\User;
use App\Models\Order;
use App\Services\FuaNumberService;
use App\Support\ClinicalService;
use App\Support\CurrentSede;
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
        $this->middleware('permission:fua.responsible.update')->only('updateResponsible');
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
            'modulo' => ['nullable', 'integer', 'between:1,4'],
            'turno' => ['nullable', 'integer', 'between:1,4'],
            'all_dates' => ['nullable', 'boolean'],
            'professional_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'max:30'],
            'sede_id' => ['nullable', 'integer', 'exists:sedes,id'],
        ]);

        $date = $request->boolean('all_dates') ? null : ($filters['date'] ?? now()->toDateString());
        if (CurrentSede::id() && isset($filters['sede_id']) && (int) $filters['sede_id'] !== (int) CurrentSede::id()) {
            abort(403, 'La sede del filtro no coincide con la sede activa.');
        }
        $sedeId = CurrentSede::id() ?: ($filters['sede_id'] ?? null);
        if ($sedeId) {
            abort_unless($request->user()->sedes()->whereKey($sedeId)->exists(), 403);
        }
        $fuas = $this->printQuery(
            $type,
            $date,
            $filters['patient'] ?? null,
            $filters['modulo'] ?? null,
            $filters['turno'] ?? null,
            $filters['professional_id'] ?? null,
            $filters['status'] ?? null,
            $sedeId,
        )
            ->orderByDesc('orders.fecha_orden')
            ->orderByDesc('fuas.id')
            ->select('fuas.*')
            ->paginate(30)
            ->withQueryString();

        return view('fuas.print-index', [
            'fuas' => $fuas,
            'date' => $date,
            'type' => $type,
            'professionals' => User::query()->whereIn('id', Order::query()
                ->where('attention_type', $type)->whereNotNull('assigned_professional_id')
                ->select('assigned_professional_id'))->orderBy('name')->get(),
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

        $correction = $numbers->create(Fua::CORRECTION, $fua->order, $fua);

        return redirect()->route('fuas.pdf', $correction)->with('success', 'FUA de subsanación generada correctamente.');
    }

    private function bulkPdfForType(Request $request, string $type)
    {
        $data = $request->validate([
            'fuas' => ['required', 'array', 'min:1'],
            'fuas.*' => ['integer', 'distinct'],
        ]);

        $fuas = Fua::query()
            ->where('type', $type)
            ->whereIn('id', $data['fuas'])
            ->when(CurrentSede::id(), fn (Builder $query, int $sede) => $query
                ->whereHas('order', fn (Builder $order) => $order->where('sede_id', $sede)))
            ->with($this->pdfRelations())
            ->get()
            ->sortBy(fn (Fua $fua) => array_search($fua->id, $data['fuas']))
            ->values();

        abort_if($fuas->isEmpty(), 404);

        $configuration = FuaConfiguration::global();
        $documents = $fuas->map(fn (Fua $fua) => [
            'fua' => $fua,
            'responsible' => $this->responsible($fua),
            'medications' => $this->medications($fua),
            'procedures' => $this->procedures($fua),
        ]);

        $view = $type === Fua::NEPHROLOGY ? 'fuas.pdf_nephrology' : 'fuas.pdf';

        return Pdf::loadView($view, [
            'documents' => $documents,
            'configuration' => $configuration,
            'logoData' => $this->logoData($configuration->logo_path),
        ])->setPaper('a4')->stream('fuas-'.strtolower($type).'.pdf');
    }

    private function printQuery(
        string $type,
        ?string $date,
        ?string $patient,
        ?int $module,
        ?int $shift,
        ?int $professional,
        ?string $status,
        ?int $sede,
    ): Builder
    {
        return Fua::query()
            ->with(['order.patient', 'order.sede'])
            ->join('orders', 'orders.id', '=', 'fuas.order_id')
            ->where('fuas.type', $type)
            ->when($sede, fn (Builder $query) => $query->where('orders.sede_id', $sede))
            ->when($professional, fn (Builder $query) => $query->where('orders.assigned_professional_id', $professional))
            ->when($status, fn (Builder $query) => $query->where('fuas.status', $status))
            ->when($date, fn (Builder $query) => $query->whereDate('orders.fecha_orden', $date))
            ->when($shift, fn (Builder $query) => $query->where('orders.turno', (string) $shift))
            ->when($module, function (Builder $query, int $module) use ($type) {
                if ($type === Fua::NEPHROLOGY) {
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

    public function pdf(Request $request, Fua $fua)
    {
        $this->authorizeFuaView($request, $fua);
        $fua->load($this->pdfRelations());
        $responsible = $this->responsible($fua);
        $medications = $this->medications($fua);
        $procedures = $this->procedures($fua);
        $configuration = FuaConfiguration::global();
        $view = $fua->effectiveType() === Fua::NEPHROLOGY ? 'fuas.pdf_nephrology' : 'fuas.pdf';
        $logoData = $this->logoData($configuration->logo_path);
        $document = Pdf::loadView($view, [
            'fua' => $fua,
            'configuration' => $configuration,
            'logoData' => $logoData,
            'medications' => $medications,
            'responsible' => $responsible,
            'procedures' => $procedures,
        ])->setPaper('a4');
        $filename = 'fua-'.str_replace(['/', '\\'], '-', $fua->number).'.pdf';

        return $request->boolean('download')
            ? $document->download($filename)
            : $document->stream($filename);
    }

    private function pdfRelations(): array
    {
        return [
            'order.patient', 'order.sede', 'order.medical.usuarioInicia',
            'order.laboratoryOrder.items.test', 'order.nephrologyConsultation.medications',
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

        return collect([
            ['code' => '3107', 'description' => 'Epoetina alfa (eritropoyetina) 2 000 UI/ml, inyectable', 'quantity' => $medical?->epo2000],
            ['code' => '3113', 'description' => 'Epoetina alfa (eritropoyetina) 4 000 UI/ml, inyectable', 'quantity' => $medical?->epo4000],
            ['code' => '3979', 'description' => 'Vitamina B12 (hidroxicobalamina) 1 mg/ml, inyectable', 'quantity' => $medical?->vitamina_b12],
            ['code' => '19238', 'description' => 'Hierro (como sacarato) 20 mg Fe/ml, inyectable', 'quantity' => $medical?->hierro],
            ['code' => '01502', 'description' => 'Calcitriol 1 mcg/ml, inyectable', 'quantity' => $medical?->calcitriol],
        ])->filter(fn (array $medication) => is_numeric($medication['quantity']) && (float) $medication['quantity'] > 0)
            ->values()
            ->all();
    }

    private function logoData(?string $path): ?string
    {
        $absolutePath = $path
            ? storage_path('app/public/'.$path)
            : public_path('logo/logo-fissal.png');

        if (! is_file($absolutePath)) {
            $absolutePath = public_path('logo/logo-fissal.png');
        }

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
            ?: $fua->order?->assignedProfessional
            ?: $fua->order?->medical?->usuarioInicia;
    }
}
