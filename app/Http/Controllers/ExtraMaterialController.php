<?php

namespace App\Http\Controllers;

use App\Models\ExtraMaterial;
use App\Models\HemodialysisMaterial;
use App\Models\HemodialysisMaterialConsumption;
use App\Models\Order;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExtraMaterialController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        [$year, $monthNumber] = explode('-', $month);

        $materials = ExtraMaterial::with(['patient', 'order'])
            ->whereYear('usage_date', (int) $year)
            ->whereMonth('usage_date', (int) $monthNumber)
            ->when($request->filled('patient_id'), function ($query) use ($request) {
                $query->where('patient_id', $request->patient_id);
            })
            ->orderByDesc('usage_date')
            ->latest()
            ->paginate(15)
            ->appends($request->all());

        $patients = Patient::orderBy('surname')->orderBy('last_name')->get();

        $orders = Order::with('patient')
            ->whereYear('fecha_orden', (int) $year)
            ->whereMonth('fecha_orden', (int) $monthNumber)
            ->orderByDesc('fecha_orden')
            ->get();

        $summaryByPatient = ExtraMaterial::query()
            ->selectRaw('patient_id, SUM(total_cost) as total_amount, COUNT(*) as records')
            ->with('patient')
            ->whereYear('usage_date', (int) $year)
            ->whereMonth('usage_date', (int) $monthNumber)
            ->groupBy('patient_id')
            ->orderByDesc('total_amount')
            ->get();

        $totalMonth = (float) $summaryByPatient->sum('total_amount');

        $hemodialysisMaterials = HemodialysisMaterial::query()
            ->orderBy('name')
            ->get();

        $consumptionSummary = HemodialysisMaterialConsumption::query()
            ->selectRaw('patient_id, SUM(quantity) as total_quantity, COUNT(*) as records')
            ->with('patient')
            ->whereYear('consumed_at', (int) $year)
            ->whereMonth('consumed_at', (int) $monthNumber)
            ->groupBy('patient_id')
            ->orderByDesc('total_quantity')
            ->get();

        $consumptions = HemodialysisMaterialConsumption::query()
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'order_id' => 'nullable|exists:orders,id',
            'usage_date' => 'required|date',
            'material_name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

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

        $materials = ExtraMaterial::with('patient')
            ->whereYear('usage_date', (int) $year)
            ->whereMonth('usage_date', (int) $monthNumber)
            ->orderBy('usage_date')
            ->get();

        $fileName = 'reporte_materiales_extra_' . $month . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
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
}
