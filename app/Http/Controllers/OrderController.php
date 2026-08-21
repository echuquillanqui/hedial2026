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

class OrderController extends Controller
{
    /**
     * Listado de Órdenes.
     * Vista: resources/views/atenciones/ordenes/index.blade.php
     */
    public function index(Request $request)
    {
        // Si no viene fecha en el request, usamos la de hoy por defecto
        $dateFilter = $request->get('date', date('Y-m-d'));

        $currentSedeId = CurrentSede::id();

        $orders = Order::with(['patient', 'medical', 'sede', 'fua'])
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
            ->when($request->turno, function ($query, $turno) {
                $query->where('turno', $turno);
            })
            ->when($request->sala, function ($query, $sala) {
                $query->where('sala', $sala);
            })
            ->latest()
            ->paginate(15)
            ->appends($request->all()); // Muy importante para mantener filtros en la paginación

        return view('atenciones.ordenes.index', compact('orders'));
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
            'sala'           => 'required|string',
            'turno'          => 'required|string',
            'horas_dialisis' => 'required|numeric|min:0.5',
            'fecha_orden'    => 'required|date',
            'laboratory_period' => 'nullable|in:M,B,T,S',
        ]);

        try {
            DB::beginTransaction();

            $patient = Patient::findOrFail($validated['patient_id']);
            $currentSedeId = CurrentSede::id();
            if ($currentSedeId && (int) $patient->sede_id !== (int) $currentSedeId) {
                abort(403, 'Paciente fuera de la sede activa.');
            }

            $order = Order::create(array_merge($validated, [
                'codigo_unico' => $this->generateCode(),
                'sede_id' => $patient->sede_id,
                'attention_type' => Fua::HEMODIALYSIS,
            ]));

            $this->createRelatedRecords($order, $order->patient);
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
            'sala'             => 'required|string',
            'fecha_orden'      => 'required|date',
            'horas_individual' => 'required|array', // Captura el array de la vista
            'laboratory_periods' => 'required|array',
            'laboratory_periods.*' => 'nullable|in:M,B,T,S',
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->patient_ids as $id) {
                $patient = Patient::findOrFail($id);
                $currentSedeId = CurrentSede::id();
                if ($currentSedeId && (int) $patient->sede_id !== (int) $currentSedeId) {
                    abort(403, 'Paciente fuera de la sede activa.');
                }
                
                // 1. Capturar la hora individual (ej: 3.5)
                $horasHD = $request->horas_individual[$id] ?? 3.5;
                $laboratoryPeriod = $request->laboratory_periods[$id] ?? null;

                // 2. Crear la Orden (Tabla: orders)
                $order = Order::create([
                    'patient_id'     => $id,
                    'codigo_unico'   => $this->generateCode(),
                    'sala'           => $request->sala,
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
                app(FuaNumberService::class)->createForOrder($order);

            }

            DB::commit();
            return redirect()->route('orders.index')->with('success', 'Órdenes guardadas con horas actualizadas.');

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
        ]);

        DB::transaction(function () use ($data) {
            Patient::query()->whereIn('id', $data['patient_ids'])->get()->each(function (Patient $patient) use ($data) {
                if (CurrentSede::id() && (int) $patient->sede_id !== (int) CurrentSede::id()) {
                    abort(403, 'Paciente fuera de la sede activa.');
                }

                $order = Order::create([
                    'patient_id' => $patient->id,
                    'codigo_unico' => $this->generateCode(),
                    'sala' => 'CONSULTA NEFROLÓGICA',
                    'turno' => $patient->turno ?? 'N/A',
                    'attention_type' => Fua::NEPHROLOGY,
                    'laboratory_period' => null,
                    'horas_dialisis' => 0.5,
                    'fecha_orden' => $data['fecha_orden'],
                    'sede_id' => $patient->sede_id,
                ]);

                app(FuaNumberService::class)->createForOrder($order);

                // La orden agenda la atención; debe quedar disponible de inmediato
                // en el módulo donde el nefrólogo completa la historia clínica.
                NephrologyConsultation::create([
                    'order_id' => $order->id,
                    'sede_id' => $patient->sede_id,
                    'patient_id' => $patient->id,
                    'consultation_date' => $data['fecha_orden'],
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
        if ($order->medical && $order->medical->hora_final) {
            return back()->with('toastr', ['type' => 'warning', 'message' => 'No se puede eliminar una atención finalizada.']);
        }

        $order->delete(); // Cascade delete debe estar activo en la DB

        return redirect()->route('orders.index')->with('toastr', [
            'type' => 'error', 
            'message' => 'Orden y registros clínicos eliminados.'
        ]);
    }

    /**
     * Lógica compartida para crear Medical, Nurse y Treatment.
     */
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

}
