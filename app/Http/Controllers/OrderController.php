<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Patient;
use App\Models\Medical;
use App\Models\Nurse;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $orders = Order::with(['patient', 'medical'])
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
        $patients = null;
        // Agregamos la validación del campo 'modulo'
        if ($request->filled('secuencia') && $request->filled('turno') && $request->filled('modulo')) {
            $patients = Patient::where('secuencia', $request->secuencia)
                                ->where('turno', $request->turno)
                                ->where('modulo', $request->modulo) // Nuevo filtro aplicado
                                ->get();
        }

        return view('atenciones.ordenes.create_bulk', compact('patients'));
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
            'horas_dialisis' => 'required|integer|min:1',
            'fecha_orden'    => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $order = Order::create(array_merge($validated, [
                'codigo_unico' => $this->generateCode()
            ]));

            $this->createRelatedRecords($order);

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
            'horas_individual' => 'required|array' // Captura el array de la vista
        ]);

        try {
            DB::beginTransaction();

            foreach ($request->patient_ids as $id) {
                $patient = Patient::findOrFail($id);
                
                // 1. Capturar la hora individual (ej: 3.5)
                $horasHD = $request->horas_individual[$id] ?? 3.5;

                // 2. Crear la Orden (Tabla: orders)
                $order = Order::create([
                    'patient_id'     => $id,
                    'codigo_unico'   => $this->generateCode(),
                    'sala'           => $request->sala,
                    'turno'          => $patient->turno,
                    'es_covid'       => isset($request->covid_flags[$id]),
                    'horas_dialisis' => $horasHD, // Se guarda como decimal
                    'fecha_orden'    => $request->fecha_orden,
                ]);

                // 3. Crear el registro médico (Tabla: medicals)
                // Se agregan temperatura y na_inicial porque NO son nullables en tu migración
                $order->medical()->create([
                    'hora_hd'               => $horasHD, // Guardamos la hora también aquí
                    'usuario_que_inicia_hd' => auth()->id(),
                    'epo2000' => '0',
                    'epo4000' => '0',
                    'hierro' => '0',
                    'vitamina_b12' => '0',
                    'calcitriol' => '0',
                ]);

                // 4. Crear registros vacíos necesarios para evitar errores de relación
                $order->nurse()->create(
                    [
                        'frecuencia_hd' => $order->patient->secuencia,
                        'epo2000' => '0',
                        'epo4000' => '0',
                        'hierro' => '0',
                        'vitamina_b12' => '0',
                        'calcitriol' => '0',
                    ]
                );
                $order->treatments()->create([
                    'pa' => '', // Insertamos una hora referencial o nula
                ]);
            }

            DB::commit();
            return redirect()->route('orders.index')->with('success', 'Órdenes guardadas con horas actualizadas.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Formulario de Edición.
     * Vista: resources/views/atenciones/ordenes/edit.blade.php
     */
    public function edit(Order $order)
    {
        
    }

    /**
     * Actualización de la Orden.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'sala'           => 'required|string',
            'turno'          => 'required|string',
            'horas_dialisis' => 'required|numeric|min:0.5', // Cambiado de integer a numeric
            'fecha_orden'    => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $order->update($validated);

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
    private function createRelatedRecords($order, $patient = null)
    {
        // 1. Crear Medical
        Medical::create([
            'order_id' => $order->id,
            'hora_inicial' => now()->format('H:i'),
            'peso_seco' => $patient->peso_seco ?? 0,
            'usuario_que_inicia_hd' => auth()->id(),
        ]);

        // 2. Crear Nurse (Hoja de medicación y signos)
        Nurse::create([
            'order_id' => $order->id,
            'epo2000' => 'NO',
            'epo4000' => 'NO',
            'hierro' => 'NO',
            'vitamina_b12' => 'NO',
            'calcitriol' => 'NO',
        ]);

        // 3. Crear Treatment
        Treatment::create([
            'order_id' => $order->id,
            'name' => 'Hemodiálisis',
        ]);
    }

    private function generateCode()
    {
        return 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
    }
}