<?php

namespace App\Http\Controllers;

use App\Models\ExtraMaterial;
use App\Models\HemodialysisMaterial;
use App\Models\HemodialysisMaterialConsumption;
use App\Models\Order;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Support\CurrentSede;

class ExtraMaterialController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $view = $request->input('view', 'resumen');
        $allowedViews = ['resumen', 'extras', 'base', 'consumo'];

        if (!in_array($view, $allowedViews, true)) {
            $view = 'resumen';
        }

        [$year, $monthNumber] = explode('-', $month);

        $this->syncFinalizedConsumptions((int) $year, (int) $monthNumber);

        $currentSedeId = CurrentSede::id();

        $materials = ExtraMaterial::with(['patient', 'order'])
            ->when($currentSedeId, fn ($q) => $q->whereHas('order', fn ($oq) => $oq->where('sede_id', $currentSedeId)))
            ->whereYear('usage_date', (int) $year)
            ->whereMonth('usage_date', (int) $monthNumber)
            ->when($request->filled('patient_id'), function ($query) use ($request) {
                $query->where('patient_id', $request->patient_id);
            })
            ->orderByDesc('usage_date')
            ->latest()
            ->paginate(15)
            ->appends($request->all());

        $patients = Patient::query()->when($currentSedeId, fn ($q) => $q->where('sede_id', $currentSedeId))->orderBy('surname')->orderBy('last_name')->get();

        $orders = Order::with('patient')
            ->when($currentSedeId, fn ($q) => $q->where('sede_id', $currentSedeId))
            ->whereYear('fecha_orden', (int) $year)
            ->whereMonth('fecha_orden', (int) $monthNumber)
            ->orderByDesc('fecha_orden')
            ->get();

        $summaryByPatient = ExtraMaterial::query()
            ->when($currentSedeId, fn ($q) => $q->whereHas('order', fn ($oq) => $oq->where('sede_id', $currentSedeId)))
            ->selectRaw('patient_id, SUM(total_cost) as total_amount, COUNT(*) as records')
            ->with('patient')
            ->whereYear('usage_date', (int) $year)
            ->whereMonth('usage_date', (int) $monthNumber)
            ->groupBy('patient_id')
            ->orderByDesc('total_amount')
            ->get();

        $totalMonth = (float) $summaryByPatient->sum('total_amount');

        $hemodialysisMaterials = HemodialysisMaterial::query()
            ->withCount('consumptions')
            ->orderBy('name')
            ->get();

        $consumptionSummary = HemodialysisMaterialConsumption::query()
            ->when($currentSedeId, fn ($q) => $q->whereHas('order', fn ($oq) => $oq->where('sede_id', $currentSedeId)))
            ->selectRaw('patient_id, SUM(quantity) as total_quantity, COUNT(*) as records')
            ->with('patient')
            ->whereYear('consumed_at', (int) $year)
            ->whereMonth('consumed_at', (int) $monthNumber)
            ->groupBy('patient_id')
            ->orderByDesc('total_quantity')
            ->get();

        $consumptions = HemodialysisMaterialConsumption::query()
            ->when($currentSedeId, fn ($q) => $q->whereHas('order', fn ($oq) => $oq->where('sede_id', $currentSedeId)))
            ->with(['patient', 'order', 'material'])
            ->whereYear('consumed_at', (int) $year)
            ->whereMonth('consumed_at', (int) $monthNumber)
            ->when($request->filled('patient_id'), function ($query) use ($request) {
                $query->where('patient_id', $request->patient_id);
            })
            ->orderByDesc('consumed_at')
            ->latest()
            ->paginate(10, ['*'], 'consumptions_page')
            ->appends($request->all());

        return view('atenciones.materiales.index', compact(
            'view',
            'materials',
            'patients',
            'orders',
            'summaryByPatient',
            'totalMonth',
            'month',
            'hemodialysisMaterials',
            'consumptionSummary',
            'consumptions'
        ));
    }

    public function updateStock(Request $request, HemodialysisMaterial $material)
    {
        $currentSedeId = CurrentSede::id();

        $validated = $request->validate([
            'stock' => 'required|numeric|min:0',
            'quantity_per_order' => 'required|numeric|min:0.01',
            'is_active' => 'nullable|boolean',
        ]);

        $material->update([
            'stock' => $validated['stock'],
            'quantity_per_order' => $validated['quantity_per_order'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('toastr', [
            'type' => 'success',
            'message' => 'Material base actualizado.',
        ]);
    }

    public function storeBaseMaterial(Request $request)
    {
        $currentSedeId = CurrentSede::id();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hemodialysis_materials,name',
            'unit' => 'required|string|max:30',
            'stock' => 'required|numeric|min:0',
            'quantity_per_order' => 'required|numeric|min:0.01',
            'is_active' => 'nullable|boolean',
        ]);

        HemodialysisMaterial::create([
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'stock' => $validated['stock'],
            'quantity_per_order' => $validated['quantity_per_order'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('toastr', [
            'type' => 'success',
            'message' => 'Material base registrado correctamente.',
        ]);
    }

    public function destroyBaseMaterial(HemodialysisMaterial $material)
    {
        $hasConsumptions = $material->consumptions()->exists();

        if ($hasConsumptions) {
            $material->update([
                'is_active' => false,
            ]);

            return back()->with('toastr', [
                'type' => 'warning',
                'message' => 'El material tiene atenciones previas. Se desactivó para futuras sesiones y no se eliminó el historial.',
            ]);
        }

        $material->delete();

        return back()->with('toastr', [
            'type' => 'success',
            'message' => 'Material base eliminado correctamente.',
        ]);
    }

    public function store(Request $request)
    {
        $currentSedeId = CurrentSede::id();

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'order_id' => 'nullable|exists:orders,id',
            'usage_date' => 'required|date',
            'material_name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($currentSedeId) {
            $order = Order::find($validated['order_id'] ?? null);
            if ($order && (int) $order->sede_id !== (int) $currentSedeId) {
                abort(403, 'Orden fuera de la sede activa.');
            }
        }

        $validated['total_cost'] = round($validated['quantity'] * $validated['unit_cost'], 2);

        ExtraMaterial::create($validated);

        return redirect()->route('extra-materials.index', [
            'month' => Carbon::parse($validated['usage_date'])->format('Y-m'),
            'patient_id' => $validated['patient_id'],
        ])->with('toastr', [
            'type' => 'success',
            'message' => 'Material extra registrado correctamente.',
        ]);
    }

    public function destroy(ExtraMaterial $extraMaterial)
    {
        $month = Carbon::parse($extraMaterial->usage_date)->format('Y-m');
        $extraMaterial->delete();

        return redirect()->route('extra-materials.index', ['month' => $month])->with('toastr', [
            'type' => 'success',
            'message' => 'Registro eliminado.',
        ]);
    }

    public function monthlyReport(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        [$year, $monthNumber] = explode('-', $month);
        $this->syncFinalizedConsumptions((int) $year, (int) $monthNumber);

        $materials = ExtraMaterial::with('patient')
            ->whereYear('usage_date', (int) $year)
            ->whereMonth('usage_date', (int) $monthNumber)
            ->orderBy('usage_date')
            ->get();

        $fileName = 'reporte_materiales_extra_' . $month . '.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=' . $fileName,
        ];

        $callback = function () use ($materials, $month) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, ['Reporte de materiales extra por hemodiálisis']);
            fputcsv($file, ['Mes', $month]);
            fputcsv($file, []);
            fputcsv($file, ['Fecha', 'Paciente', 'Material', 'Cantidad', 'Costo Unitario', 'Costo Total', 'Observaciones']);

            $total = 0;

            foreach ($materials as $item) {
                $patientName = trim(implode(' ', [
                    $item->patient->surname ?? '',
                    $item->patient->last_name ?? '',
                    $item->patient->first_name ?? '',
                    $item->patient->other_names ?? '',
                ]));

                fputcsv($file, [
                    $item->usage_date,
                    $patientName,
                    $item->material_name,
                    number_format((float) $item->quantity, 2, '.', ''),
                    number_format((float) $item->unit_cost, 2, '.', ''),
                    number_format((float) $item->total_cost, 2, '.', ''),
                    $item->notes,
                ]);

                $total += (float) $item->total_cost;
            }

            fputcsv($file, []);
            fputcsv($file, ['TOTAL MENSUAL', '', '', '', '', number_format($total, 2, '.', ''), '']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function syncFinalizedConsumptions(int $year, int $monthNumber): void
    {
        $finalizedOrders = Order::query()
            ->when(CurrentSede::id(), fn ($q) => $q->where('sede_id', CurrentSede::id()))
            ->with([
                'medical:id,order_id,hora_final',
                'nurse:id,order_id,enfermero_que_finaliza_id',
            ])
            ->whereYear('fecha_orden', $year)
            ->whereMonth('fecha_orden', $monthNumber)
            ->whereHas('medical', function ($query) {
                $query->whereNotNull('hora_final');
            })
            ->whereHas('nurse', function ($query) {
                $query->whereNotNull('enfermero_que_finaliza_id');
            })
            ->whereHas('treatments', function ($query) {
                $query->whereNotNull('hora');
            })
            ->get(['id', 'patient_id', 'fecha_orden']);

        if ($finalizedOrders->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($finalizedOrders) {
            $materials = HemodialysisMaterial::query()
                ->where('is_active', true)
                ->where('quantity_per_order', '>', 0)
                ->lockForUpdate()
                ->get();

            if ($materials->isEmpty()) {
                return;
            }

            foreach ($finalizedOrders as $order) {
                foreach ($materials as $material) {
                    $quantity = (float) $material->quantity_per_order;

                    $consumption = HemodialysisMaterialConsumption::firstOrCreate(
                        [
                            'hemodialysis_material_id' => $material->id,
                            'order_id' => $order->id,
                        ],
                        [
                            'patient_id' => $order->patient_id,
                            'consumed_at' => $order->fecha_orden,
                            'quantity' => $quantity,
                            'notes' => 'Consumo automático por sesión finalizada',
                        ]
                    );

                    if ($consumption->wasRecentlyCreated) {
                        $material->decrement('stock', $quantity);
                    }
                }
            }
        });
    }
}
