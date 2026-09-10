<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Patient;
use App\Models\Medical;
use App\Models\Nurse;
use App\Models\Treatment;
use App\Models\LaboratoryOrder;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\CurrentSede;
use App\Models\Fua;
use App\Models\NephrologyConsultation;
use App\Models\FuaConfiguration;
use App\Services\FuaNumberService;
use App\Services\MultisectorialOrderService;
use App\Support\ClinicalService;
use App\Models\User;
use App\Models\HemodialysisConsent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use App\Support\DailyHemodialysisSequence;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:orders.view')->only(['index']);
        $this->middleware('permission:orders.create')->only(['create', 'store', 'storeBulk', 'createNephrology', 'storeNephrology']);
        $this->middleware('permission:orders.edit')->only(['edit', 'update']);
        $this->middleware('permission:orders.delete')->only(['destroy', 'destroyBulk']);
    }

    /**
     * Listado de Órdenes.
     * Vista: resources/views/atenciones/ordenes/index.blade.php
     */
    public function index(Request $request)
    {
        // La vista inicia en hoy, pero permite consultar todo el historial y
        // combinarlo libremente con los demás filtros.
        $dateFilter = $request->boolean('all_dates')
            ? null
            : $request->input('date', now()->toDateString());
        $dailySequence = $dateFilter ? DailyHemodialysisSequence::forDate($dateFilter) : null;

        $currentSedeId = CurrentSede::id();

        $ordersQuery = Order::with(['patient', 'medical', 'nurse', 'treatments', 'sede', 'fua'])
            ->select('orders.*')
            ->selectSub(function ($duplicates) {
                $duplicates->from('orders as daily_orders')
                    ->selectRaw('count(*)')
                    ->whereColumn('daily_orders.patient_id', 'orders.patient_id')
                    ->whereColumn('daily_orders.fecha_orden', 'orders.fecha_orden')
                    ->whereColumn('daily_orders.attention_type', 'orders.attention_type');
            }, 'daily_duplicate_count')
            ->when($currentSedeId, fn ($query) => $query->where('sede_id', $currentSedeId))
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('codigo_unico', 'like', "%{$search}%")
                    ->orWhereHas('patient', function($pq) use ($search) {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('surname', 'like', "%{$search}%")
                            ->orWhere('dni', 'like', "%{$search}%");
                    });
                });
            })
            ->when($dateFilter, function ($query, $date) {
                $query->whereDate('fecha_orden', $date);
            })
            ->when($dailySequence, function ($query, $sequence) {
                $query->whereHas('patient', fn ($patient) => $patient->where('secuencia', $sequence));
            })
            ->when($request->turno, function ($query, $turno) {
                $query->where('turno', $turno);
            })
            ->when($request->sala, function ($query, $sala) {
                $query->where('sala', $sala);
            })
            ->when($request->boolean('duplicates_only'), fn ($query) => $query->whereExists(function ($duplicates) {
                $duplicates->selectRaw('1')
                    ->from('orders as duplicate_orders')
                    ->whereColumn('duplicate_orders.patient_id', 'orders.patient_id')
                    ->whereColumn('duplicate_orders.fecha_orden', 'orders.fecha_orden')
                    ->whereColumn('duplicate_orders.attention_type', 'orders.attention_type')
                    ->whereColumn('duplicate_orders.id', '!=', 'orders.id');
            }));

        $recordCount = (clone $ordersQuery)->count();
        $patientCount = (clone $ordersQuery)->distinct()->count('orders.patient_id');
        $duplicateCount = (clone $ordersQuery)
            ->get(['orders.id', 'orders.patient_id', 'orders.fecha_orden', 'orders.attention_type'])
            ->groupBy(fn (Order $order) => implode('|', [
                $order->patient_id,
                $order->fecha_orden->toDateString(),
                $order->attention_type,
            ]))
            ->sum(fn ($group) => max(0, $group->count() - 1));

        $orders = $ordersQuery
            ->latest()
            ->paginate(15)
            ->appends($request->all()); // Muy importante para mantener filtros en la paginación

        return view('atenciones.ordenes.index', compact('orders', 'recordCount', 'patientCount', 'duplicateCount'));
    }

    public function multisectorialIndex(Request $request)
    {
        $type = $this->validatedMultisectorialType($request);
        $this->authorizeMultisectorial($request, $type, 'view');

        $status = $request->string('status')->upper()->toString();
        $orders = Order::query()
            ->with(['patient', 'sede', 'assignedProfessional', 'creator', 'fua'])
            ->where('attention_type', $type)
            ->when(CurrentSede::id(), fn (Builder $query, int $sede) => $query->where('sede_id', $sede))
            ->when($request->filled('professional_id'), fn (Builder $query) => $query
                ->where('assigned_professional_id', $request->integer('professional_id')))
            ->when($request->filled('date'), fn (Builder $query) => $query
                ->whereDate('fecha_orden', $request->input('date')))
            ->when($request->filled('turno'), fn (Builder $query) => $query
                ->where('turno', $request->input('turno')))
            ->when($request->filled('modulo'), fn (Builder $query) => $query
                ->whereHas('patient', fn (Builder $patient) => $patient->where('modulo', $request->input('modulo'))))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->whereHas('patient', fn (Builder $patient) => $patient
                    ->where('dni', 'like', "%{$search}%")
                    ->orWhere('medical_history_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%"));
            })
            ->when($status === 'REALIZADA', fn (Builder $query) => $query->where('status', MultisectorialOrderService::COMPLETED))
            ->when($status === 'VENCIDA', fn (Builder $query) => $query->where('status', '!=', MultisectorialOrderService::COMPLETED)
                ->whereDate('due_date', '<', today()))
            ->when($status === 'PROXIMA', fn (Builder $query) => $query->where('status', '!=', MultisectorialOrderService::COMPLETED)
                ->whereBetween('due_date', [today(), today()->addDays(30)]))
            ->when($status === 'PENDIENTE', fn (Builder $query) => $query->where('status', '!=', MultisectorialOrderService::COMPLETED)
                ->whereDate('due_date', '>', today()->addDays(30)))
            ->orderBy('due_date')
            ->paginate(20)
            ->withQueryString();

        return view('atenciones.ordenes.multisectorial_index', [
            'orders' => $orders,
            'type' => $type,
            'professionals' => $this->professionalsFor($type),
        ]);
    }

    public function createMultisectorial(Request $request)
    {
        $type = $this->validatedMultisectorialType($request);
        $this->authorizeMultisectorial($request, $type, 'create');

        $patients = Patient::query()
            ->when(CurrentSede::id(), fn (Builder $query, int $sede) => $query->where('sede_id', $sede))
            ->when($request->filled('secuencia'), fn (Builder $query) => $query->where('secuencia', $request->input('secuencia')))
            ->when($request->filled('turno'), fn (Builder $query) => $query->where('turno', $request->input('turno')))
            ->when($request->filled('modulo'), fn (Builder $query) => $query->where('modulo', $request->input('modulo')))
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(function (Builder $patient) use ($search) {
                    $patient->where('dni', 'like', "%{$search}%")
                        ->orWhere('medical_history_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('surname')->orderBy('last_name')->get();

        return view('atenciones.ordenes.create_multisectorial', [
            'type' => $type,
            'patients' => $patients,
            'professionals' => $this->professionalsFor($type),
        ]);
    }

    public function storeMultisectorial(Request $request, MultisectorialOrderService $service)
    {
        $type = $this->validatedMultisectorialType($request);
        $this->authorizeMultisectorial($request, $type, 'create');

        $data = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'assigned_professional_id' => ['required', 'integer', 'exists:users,id'],
            'fecha_orden' => ['required', 'date'],
        ]);
        $patient = Patient::query()->findOrFail($data['patient_id']);
        abort_if(CurrentSede::id() && (int) $patient->sede_id !== (int) CurrentSede::id(), 403, 'Paciente fuera de la sede activa.');

        $allowedProfessional = $this->professionalsFor($type)->contains('id', (int) $data['assigned_professional_id']);
        abort_unless($allowedProfessional, 422, 'El profesional no corresponde al tipo de atención o a la sede activa.');

        $service->create(
            $patient,
            $type,
            (int) $data['assigned_professional_id'],
            $data['fecha_orden'],
            (int) $request->user()->id,
        );

        return redirect()->route('orders.multisectorial.index', ['type' => $type])
            ->with('success', 'Orden multisectorial registrada correctamente.');
    }

    public function storeMultisectorialBulk(Request $request, MultisectorialOrderService $service)
    {
        $type = $this->validatedMultisectorialType($request);
        $this->authorizeMultisectorial($request, $type, 'create');

        $data = $request->validate([
            'patient_ids' => ['required', 'array', 'min:1'],
            'patient_ids.*' => ['integer', 'distinct', 'exists:patients,id'],
            'assigned_professional_id' => ['required', 'integer', 'exists:users,id'],
            'fecha_orden' => ['required', 'date'],
        ]);

        abort_unless($this->professionalsFor($type)->contains('id', (int) $data['assigned_professional_id']), 422,
            'El profesional no corresponde al tipo de atención o a la sede activa.');

        $patients = Patient::query()->whereIn('id', $data['patient_ids'])->get();
        abort_if(CurrentSede::id() && $patients->contains(fn (Patient $patient) => (int) $patient->sede_id !== (int) CurrentSede::id()),
            403, 'Uno de los pacientes está fuera de la sede activa.');

        DB::transaction(function () use ($patients, $type, $data, $request, $service) {
            foreach ($patients as $patient) {
                $service->create($patient, $type, (int) $data['assigned_professional_id'], $data['fecha_orden'], (int) $request->user()->id);
            }
        });

        return redirect()->route('orders.multisectorial.index', ['type' => $type])
            ->with('success', $patients->count().' órdenes generadas correctamente.');
    }

    /**
     * Formulario de creación (Individual o Bloque).
     * Vista: resources/views/atenciones/ordenes/create_bulk.blade.php
     */
    public function create(Request $request)
    {
        $patients = collect();
        $searchedPatients = collect();

        // Flujo de generación en bloque. Cada criterio es opcional para poder
        // listar toda la sede o combinar únicamente los filtros necesarios.
        if ($request->boolean('filter_patients') || $request->filled(['secuencia', 'turno', 'modulo'])) {
            $patients = Patient::query()
                ->when(CurrentSede::id(), fn ($q) => $q->where('sede_id', CurrentSede::id()))
                ->when($request->filled('secuencia'), fn ($q) => $q->where('secuencia', $request->secuencia))
                ->when($request->filled('turno'), fn ($q) => $q->where('turno', $request->turno))
                ->when($request->filled('modulo'), fn ($q) => $q->where('modulo', $request->modulo))
                ->orderBy('surname')
                ->orderBy('last_name')
                ->get();
        }

        // Flujo general para buscar cualquier paciente y generar orden individual.
        if ($request->filled('patient_search')) {
            $search = trim((string) $request->patient_search);

            $searchedPatients = Patient::query()
                ->when(CurrentSede::id(), fn ($q) => $q->where('sede_id', CurrentSede::id()))
                ->where(function ($query) use ($search) {
                    $query->where('dni', 'like', "%{$search}%")
                        ->orWhere('medical_history_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orderBy('surname')
                ->orderBy('last_name')
                ->limit(25)
                ->get();
        }

        return view('atenciones.ordenes.create_bulk', compact('patients', 'searchedPatients'));
    }

    /**
     * Almacenamiento Individual.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'     => 'required|exists:patients,id',
            'turno'          => 'required|string',
            'horas_dialisis' => 'required|numeric|min:0.5',
            'fecha_orden'    => 'required|date',
            'laboratory_period' => 'nullable|in:M,B,T,S',
        ]);

        try {
            DB::beginTransaction();

            // Lock the patient while checking/creating the daily order. This makes
            // two almost simultaneous submissions serialize instead of creating
            // two clinical records for the same session.
            $patient = Patient::query()->lockForUpdate()->findOrFail($validated['patient_id']);
            $currentSedeId = CurrentSede::id();
            if ($currentSedeId && (int) $patient->sede_id !== (int) $currentSedeId) {
                abort(403, 'Paciente fuera de la sede activa.');
            }

            $existingOrder = $this->dailyHemodialysisOrder($patient->id, $validated['fecha_orden']);
            if ($existingOrder) {
                DB::rollBack();

                return redirect()->route('orders.index', ['date' => $validated['fecha_orden']])
                    ->with('warning', $this->duplicateOrderMessage($existingOrder));
            }

            $order = Order::create(array_merge($validated, [
                'codigo_unico' => $this->generateCode(),
                'sala' => 'MODULO '.$patient->modulo,
                'sede_id' => $patient->sede_id,
                'attention_type' => Fua::HEMODIALYSIS,
            ]));

            $this->createRelatedRecords($order, $order->patient);
            $this->createMonthlyConsent($order, $patient, $request->user());
            app(FuaNumberService::class)->createForOrder($order);

            DB::commit();
            return redirect()->route('orders.index')->with('toastr', [
                'type' => 'success', 
                'message' => 'Orden individual y registros médicos generados.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('toastr', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * PROCESAMIENTO EN BLOQUE (Masivo).
     */
    public function storeBulk(Request $request)
    {
        $request->validate([
            'patient_ids'      => 'required|array|min:1',
            'patient_ids.*'    => 'integer|distinct|exists:patients,id',
            'fecha_orden'      => 'required|date',
            'horas_individual' => 'required|array', // Captura el array de la vista
            'laboratory_periods' => 'required|array',
            'laboratory_periods.*' => 'nullable|in:M,B,T,S',
        ]);

        try {
            DB::beginTransaction();

            // A stable lock order avoids deadlocks when two bulk requests contain
            // the same patients in a different order.
            $patientIds = collect($request->patient_ids)->map(fn ($id) => (int) $id)->sort()->values();
            $patients = Patient::query()->whereIn('id', $patientIds)->lockForUpdate()->get()->keyBy('id');
            $skippedOrders = collect();

            foreach ($patientIds as $id) {
                $patient = $patients->get($id);
                $currentSedeId = CurrentSede::id();
                if ($currentSedeId && (int) $patient->sede_id !== (int) $currentSedeId) {
                    abort(403, 'Paciente fuera de la sede activa.');
                }

                $existingOrder = $this->dailyHemodialysisOrder($patient->id, $request->fecha_orden);
                if ($existingOrder) {
                    $skippedOrders->push($existingOrder);
                    continue;
                }
                
                // 1. Capturar la hora individual (ej: 3.5)
                $horasHD = $request->horas_individual[$id] ?? 3.5;
                $laboratoryPeriod = $request->laboratory_periods[$id] ?? null;

                // 2. Crear la Orden (Tabla: orders)
                $order = Order::create([
                    'patient_id'     => $id,
                    'codigo_unico'   => $this->generateCode(),
                    // La sala pertenece al paciente, no a la selección global del lote.
                    'sala'           => 'MODULO '.$patient->modulo,
                    'turno'          => $patient->turno,
                    'es_covid'       => isset($request->covid_flags[$id]),
                    'laboratory_period' => $laboratoryPeriod,
                    'attention_type' => Fua::HEMODIALYSIS,
                    'horas_dialisis' => $horasHD, // Se guarda como decimal
                    'fecha_orden'    => $request->fecha_orden,
                    'sede_id'        => $patient->sede_id,
                ]);

                // 3. Crear registros clínicos relacionados (medicals, nurses y treatments)
                $this->createRelatedRecords($order, $patient, $horasHD);
                $this->createMonthlyConsent($order, $patient, $request->user());
                app(FuaNumberService::class)->createForOrder($order);

            }

            DB::commit();
            $createdCount = $patientIds->count() - $skippedOrders->count();
            $message = $createdCount.' órdenes nuevas guardadas.';
            if ($skippedOrders->isNotEmpty()) {
                $message .= ' Se omitieron '.$skippedOrders->count().' pacientes que ya tenían orden de hemodiálisis ese día; sus datos clínicos se conservaron.';
            }

            return redirect()->route('orders.index', ['date' => $request->fecha_orden])
                ->with($createdCount > 0 ? 'success' : 'warning', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Formulario independiente para generar FUA de consulta nefrológica.
     */
    public function createNephrology(Request $request)
    {
        $patients = Patient::query()
            ->when(CurrentSede::id(), fn ($query) => $query->where('sede_id', CurrentSede::id()))
            ->when($request->filled('secuencia'), fn ($query) => $query->where('secuencia', $request->secuencia))
            ->when($request->filled('turno'), fn ($query) => $query->where('turno', $request->turno))
            ->when($request->filled('modulo'), fn ($query) => $query->where('modulo', $request->modulo))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);
                $query->where(function ($patientQuery) use ($search) {
                    $patientQuery->where('dni', 'like', "%{$search}%")
                        ->orWhere('medical_history_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('surname', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('surname')
            ->orderBy('last_name')
            ->get();

        return view('atenciones.ordenes.create_nephrology', compact('patients'));
    }

    /**
     * Genera órdenes/FUA de consulta sin crear registros de hemodiálisis o laboratorio.
     */
    public function storeNephrology(Request $request)
    {
        $data = $request->validate([
            'patient_ids' => ['required', 'array', 'min:1'],
            'patient_ids.*' => ['integer', 'distinct', 'exists:patients,id'],
            'fecha_orden' => ['required', 'date'],
            'patient_dates' => ['nullable', 'array'],
            'patient_dates.*' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($data) {
            Patient::query()->whereIn('id', $data['patient_ids'])->get()->each(function (Patient $patient) use ($data) {
                if (CurrentSede::id() && (int) $patient->sede_id !== (int) CurrentSede::id()) {
                    abort(403, 'Paciente fuera de la sede activa.');
                }

                $individualDate = $data['patient_dates'][$patient->id] ?? null;
                $consultationDate = filled($individualDate) ? $individualDate : $data['fecha_orden'];

                $order = Order::create([
                    'patient_id' => $patient->id,
                    'codigo_unico' => $this->generateCode(),
                    'sala' => 'CONSULTA NEFROLÓGICA',
                    'turno' => $patient->turno ?? 'N/A',
                    'attention_type' => Fua::NEPHROLOGY,
                    'laboratory_period' => null,
                    'horas_dialisis' => 0.5,
                    'fecha_orden' => $consultationDate,
                    'sede_id' => $patient->sede_id,
                ]);

                app(FuaNumberService::class)->createForOrder($order);

                // La orden agenda la atención; debe quedar disponible de inmediato
                // en el módulo donde el nefrólogo completa la historia clínica.
                NephrologyConsultation::create([
                    'order_id' => $order->id,
                    'sede_id' => $patient->sede_id,
                    'patient_id' => $patient->id,
                    'consultation_date' => $consultationDate,
                ]);
            });
        });

        return redirect()->route('orders.index')->with('success', 'Se generaron las órdenes de consulta nefrológica y sus FUA.');
    }

    /**
     * Formulario de Edición.
     * Vista: resources/views/atenciones/ordenes/edit.blade.php
     */
    public function edit(Order $order)
    {
        if (CurrentSede::id() && (int) $order->sede_id !== (int) CurrentSede::id()) {
            abort(403, 'Orden fuera de la sede activa.');
        }

        return view('atenciones.ordenes.edit', compact('order'));
    }

    /**
     * Actualización de la Orden.
     */
    public function update(Request $request, Order $order)
    {
        if (CurrentSede::id() && (int) $order->sede_id !== (int) CurrentSede::id()) {
            abort(403, 'Orden fuera de la sede activa.');
        }
        $validated = $request->validate([
            'sala'           => 'required|string',
            'turno'          => 'required|string',
            'horas_dialisis' => 'required|numeric|min:0.5', // Cambiado de integer a numeric
            'fecha_orden'    => 'required|date',
            'laboratory_period' => 'nullable|in:M,B,T,S',
        ]);

        try {
            DB::beginTransaction();

            $order->update($validated);

            if ($order->nephrologyConsultation) {
                $order->nephrologyConsultation->update([
                    'consultation_date' => $order->fecha_orden,
                ]);
            }

            if ($order->laboratoryOrder && $order->laboratory_period
                && $order->laboratoryOrder->period !== $order->laboratory_period) {
                $order->laboratoryOrder->update(['period' => $order->laboratory_period]);
                $order->laboratoryOrder->items()->delete();
                $this->addLaboratoryItems($order->laboratoryOrder, $order->laboratory_period);
            }

            // Sincronizar con la tabla medicals
            if ($order->medical) {
                $order->medical->update([
                    'hora_hd' => $request->horas_dialisis
                ]);
            }

            DB::commit();
            return redirect()->route('orders.index')->with('success', 'Orden actualizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Eliminación de la Orden.
     */
    public function destroy(Order $order)
    {
        if (CurrentSede::id() && (int) $order->sede_id !== (int) CurrentSede::id()) {
            abort(403, 'Orden fuera de la sede activa.');
        }
        if ($order->hasRecordedClinicalData()) {
            return back()->with('toastr', [
                'type' => 'warning',
                'message' => 'Esta orden contiene datos clínicos y debe conservarse. Elimine solamente el duplicado identificado como vacío.',
            ]);
        }

        $order->delete(); // Cascade delete debe estar activo en la DB

        return redirect()->route('orders.index')->with('toastr', [
            'type' => 'error', 
            'message' => 'Orden y registros clínicos eliminados.'
        ]);
    }

    public function destroyBulk(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'distinct', 'exists:orders,id'],
        ]);

        [$deleted, $protected] = DB::transaction(function () use ($validated) {
            $orders = Order::query()
                ->whereIn('id', $validated['order_ids'])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();
            $deleted = 0;
            $protected = 0;

            foreach ($orders as $order) {
                if (CurrentSede::id() && (int) $order->sede_id !== (int) CurrentSede::id()) {
                    abort(403, 'Una de las órdenes está fuera de la sede activa.');
                }

                $duplicates = Order::query()
                    ->where('patient_id', $order->patient_id)
                    ->whereDate('fecha_orden', $order->fecha_orden)
                    ->where('attention_type', $order->attention_type)
                    ->count();

                // Never remove clinical information or the last order in a group.
                if ($duplicates < 2 || $order->hasRecordedClinicalData()) {
                    $protected++;
                    continue;
                }

                $order->delete();
                $deleted++;
            }

            return [$deleted, $protected];
        });

        $message = $deleted.' duplicado(s) vacío(s) eliminado(s).';
        if ($protected > 0) {
            $message .= ' Se conservaron '.$protected.' orden(es) por contener datos clínicos o ser la única ficha restante.';
        }

        return back()->with('toastr', [
            'type' => $deleted > 0 ? 'success' : 'warning',
            'message' => $message,
        ]);
    }

    /**
     * Lógica compartida para crear Medical, Nurse y Treatment.
     */
    private function createMonthlyConsent(Order $order, Patient $patient, User $creator): void
    {
        $attentionDate = $order->fecha_orden->copy();

        if (HemodialysisConsent::query()
            ->where('patient_id', $patient->id)
            ->whereBetween('consented_at', [
                $attentionDate->copy()->startOfMonth(),
                $attentionDate->copy()->endOfMonth(),
            ])->exists()) {
            return;
        }

        $profession = mb_strtolower((string) $creator->profession);
        $isPhysician = $creator->hasRole('medico')
            || str_contains($profession, 'medic')
            || str_contains($profession, 'nefro');

        HemodialysisConsent::create([
            'patient_id' => $patient->id,
            'sede_id' => $patient->sede_id,
            'physician_id' => $isPhysician ? $creator->id : null,
            'created_by' => $creator->id,
            // Automatic consents represent the order's clinical date, not the
            // moment the batch was prepared. Noon also keeps that calendar date
            // stable when timestamp values cross the application/DB timezone.
            'consented_at' => $attentionDate->startOfDay()->addHours(12),
            'version' => '02',
            'accepted' => true,
            'notes' => 'Generado automáticamente con la primera atención de hemodiálisis del mes.',
        ]);
    }

    private function createRelatedRecords($order, $patient = null, $horasHD = null)
    {
        // 1. Crear Medical
        Medical::create([
            'order_id' => $order->id,
            'hora_inicial' => now()->format('H:i'),
            'hora_hd' => $horasHD ?? $order->horas_dialisis,
            'peso_seco' => $patient->peso_seco ?? 0,
            'usuario_que_inicia_hd' => auth()->id(),
            'epo2000' => '0',
            'epo4000' => '0',
            'hierro' => '0',
            'vitamina_b12' => '0',
            'calcitriol' => '0',
        ]);

        // 2. Crear Nurse (Hoja de medicación y signos)
        Nurse::create([
            'order_id' => $order->id,
            'frecuencia_hd' => $patient->secuencia ?? null,
            'marca_modelo' => FuaConfiguration::global()->dialysis_equipment ?: 'FRESENIUS/4008S',
            'acceso_arterial' => $patient->acceso_arterial ?? null,
            'acceso_venoso' => $patient->acceso_venoso ?? null,
            'epo2000' => '0',
            'epo4000' => '0',
            'hierro' => '0',
            'vitamina_b12' => '0',
            'calcitriol' => '0',
        ]);

        // 3. Crear Treatment
        Treatment::create([
            'order_id' => $order->id,
            'pa' => '',
        ]);
    }

    private function generateCode()
    {
        return 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
    }

    private function dailyHemodialysisOrder(int $patientId, string $date): ?Order
    {
        return Order::query()
            ->where('patient_id', $patientId)
            ->where('attention_type', Fua::HEMODIALYSIS)
            ->whereDate('fecha_orden', $date)
            ->oldest('id')
            ->first();
    }

    private function duplicateOrderMessage(Order $order): string
    {
        return 'No se generó otra orden: el paciente ya tiene la orden '
            .$order->codigo_unico.' para esa fecha. La orden existente y todos sus datos clínicos se conservaron.';
    }

    private function addLaboratoryItems(LaboratoryOrder $laboratoryOrder, string $period): void
    {
        $includedFrequencies = match ($period) {
            'M' => ['M'],
            'B' => ['M', 'B'],
            'T' => ['M', 'B', 'T'],
            'S' => ['M', 'B', 'T', 'S'],
        };

        $items = Test::query()
            ->where('is_fissal', true)
            ->whereIn('frequency', $includedFrequencies)
            ->pluck('id')
            ->map(fn ($testId) => ['test_id' => $testId])
            ->all();

        $laboratoryOrder->items()->createMany($items);
    }

    private function validatedMultisectorialType(Request $request): string
    {
        $data = validator($request->only('type'), [
            'type' => ['required', Rule::in(ClinicalService::MULTISECTORIAL_TYPES)],
        ])->validate();

        return $data['type'];
    }

    private function authorizeMultisectorial(Request $request, string $type, string $action): void
    {
        $specificPermission = ClinicalService::permissionPrefix($type).'.'.$action;
        $generalPermission = 'orders.'.($action === 'view' ? 'view' : 'create');

        abort_unless($request->user()->can($specificPermission) || $request->user()->can($generalPermission), 403);
    }

    private function professionalsFor(string $type)
    {
        $definitions = match ($type) {
            ClinicalService::NUTRITION => ['nutricionista', 'NUTRIC'],
            ClinicalService::PSYCHOLOGY => ['psicologo', 'PSIC'],
            ClinicalService::SOCIAL_WORK => ['trabajo_social', 'SOCIAL'],
        };

        return User::query()
            ->when(CurrentSede::id(), fn (Builder $query, int $sede) => $query
                ->whereHas('sedes', fn (Builder $sedes) => $sedes->whereKey($sede)))
            ->where(function (Builder $query) use ($definitions) {
                $query->whereHas('roles', fn (Builder $roles) => $roles->where('name', $definitions[0]))
                    ->orWhere('profession', 'like', '%'.$definitions[1].'%');
            })
            ->orderBy('name')
            ->get();
    }

}
